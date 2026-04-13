<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Item;
use App\Models\Brand;
use App\Models\ItemVariant;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // Get Brands
    public function getBrands(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $brands = Brand::where('tenant_id', $tenantId)->get();
        return response()->json(['status' => 'success', 'data' => $brands]);
    }

    // Get Locations
    public function getLocations(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        // Return tenant locations OR any unassigned ones (legacy data safety)
        $locations = \App\Models\Location::where(function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
        })->get();
        return response()->json(['status' => 'success', 'data' => $locations]);
    }

    // Update stock quantity for a variant
    public function updateVariantStock(Request $request, $variantId)
    {
        $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'location_name' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $variant = ItemVariant::findOrFail($variantId);

            // Handle location
            $locationId = $request->location_id;
            if (!$locationId && $request->filled('location_name')) {
                $locationId = DB::table('locations')->insertGetId([
                    'tenant_id' => $request->user()->tenant_id,
                    'name' => $request->location_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (!$locationId) {
                // Use or create a default location for this tenant
                $defaultLocation = DB::table('locations')
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->first();
                if ($defaultLocation) {
                    $locationId = $defaultLocation->id;
                } else {
                    $locationId = DB::table('locations')->insertGetId([
                        'tenant_id' => $request->user()->tenant_id,
                        'name' => 'Default',
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $oldQty = (float) DB::table('stocks')
                ->where('variant_id', $variantId)
                ->where('location_id', $locationId)
                ->value('quantity') ?? 0;

            $newQty = (float) $request->quantity;
            $diff = $newQty - $oldQty;

            // Upsert stock row
            DB::table('stocks')->updateOrInsert(
                ['variant_id' => $variantId, 'location_id' => $locationId],
                ['quantity' => $newQty, 'updated_at' => now(), 'created_at' => now()]
            );

            // Log adjustment
            if ($diff != 0) {
                DB::table('stock_movements')->insert([
                    'variant_id' => $variantId,
                    'location_id' => $locationId,
                    'user_id' => $request->user()->id,
                    'type' => 'adjustment',
                    'quantity' => abs($diff),
                    'notes' => ($diff > 0 ? 'Stock increased by ' : 'Stock decreased by ') . abs($diff),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Stock updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // List Folders (Categories)
    public function getFolders(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        if ($request->query('all')) {
            $folders = Category::where('tenant_id', $tenantId)->get();
            return response()->json(['status' => 'success', 'data' => $folders]);
        }

        $parentId = $request->query('parent_id');

        $folders = Category::where('tenant_id', $tenantId)
            ->where('parent_id', $parentId)
            ->withCount(['children', 'items'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $folders
        ]);
    }

    // Create Folder
    public function storeFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'template_schema' => 'nullable|array'
        ]);

        $folder = Category::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'template_schema' => $request->template_schema,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $folder
        ]);
    }

    // List Items (grouped by folder)
    public function getItems(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $categoryId = $request->query('category_id');

        $query = Item::with(['variants' => function($q) {
            $q->with('stocks.location');
        }])->where('tenant_id', $tenantId);

        if ($request->query('all')) {
            // Return ALL items across every folder (used for global search)
            $items = $query->get();
        } elseif ($categoryId) {
            $items = $query->where('category_id', $categoryId)->get();
        } else {
            $items = $query->whereNull('category_id')->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $items
        ]);
    }

    // Create Item
    public function storeItem(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'category_name' => 'nullable|string',
            'base_sku' => 'nullable|string',
            'barcode' => 'nullable|string|unique:item_variants,barcode',
            'variant_specs' => 'nullable|array',
            'location_id' => 'nullable|exists:locations,id',
            'location_name' => 'nullable|string',
            'quantity' => 'nullable|numeric',
            'supplier_price' => 'nullable|numeric',
            'sales_price' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $categoryId = $request->category_id;
            
            // If they provided a name but no ID (new category typed in)
            if (!$categoryId && $request->filled('category_name')) {
                $category = Category::firstOrCreate([
                    'tenant_id' => $request->user()->tenant_id,
                    'name' => $request->category_name,
                ]);
                $categoryId = $category->id;
            }

            $category = $categoryId ? Category::find($categoryId) : null;
            
            // Update the category's template if it's currently empty, to be used for future items!
            if ($category && $request->filled('variant_specs') && empty($category->template_schema)) {
                $category->template_schema = $request->variant_specs;
                $category->save();
            }

            // Handle Brand
            $brandId = $request->brand_id;
            if (!$brandId && $request->filled('brand_name')) {
                $brand = Brand::firstOrCreate([
                    'tenant_id' => $request->user()->tenant_id,
                    'name' => $request->brand_name,
                ]);
                $brandId = $brand->id;
            }

            // Handle Location
            $locationId = $request->location_id;
            if (!$locationId && $request->filled('location_name')) {
                $location = \App\Models\Location::firstOrCreate(
                    ['name' => $request->location_name, 'tenant_id' => $request->user()->tenant_id],
                    ['tenant_id' => $request->user()->tenant_id]
                );
                $locationId = $location->id;
            }

            // Auto inherit template schema if specs are empty
            $specs = $category ? $category->template_schema : null;
            if ($request->has('specs') && $request->specs) {
                $specs = $request->specs;
            }

            $item = Item::create([
                'tenant_id' => $request->user()->tenant_id,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'name' => $request->name,
                'base_sku' => $request->base_sku,
                'specs' => $specs,
            ]);

            // Generate variants from cartesian product of attribute options
            $variantSpecs = $request->variant_specs ?? []; // [{attribute: 'Color', options: ['Red','Blue']}, ...]

            // Build cartesian product of all option values
            $combinations = [[]];
            foreach ($variantSpecs as $spec) {
                $newCombinations = [];
                foreach ($combinations as $existing) {
                    foreach ($spec['options'] as $option) {
                        $newCombinations[] = array_merge($existing, [$spec['attribute'] => $option]);
                    }
                }
                $combinations = $newCombinations;
            }

            // If no variant specs, create a single default variant
            if (empty($combinations) || $combinations === [[]]) {
                $combinations = [[]];
            }

            $variantIndex = 1;
            $firstVariant = null;
            foreach ($combinations as $combo) {
                $variantSku = $request->base_sku
                    ? ($request->base_sku . '_' . $variantIndex)
                    : ('SKU-' . strtoupper(str_pad(dechex(mt_rand()), 8, '0', STR_PAD_LEFT)));

                $variant = ItemVariant::create([
                    'item_id' => $item->id,
                    'sku' => $variantSku,
                    'barcode' => $variantIndex === 1 ? $request->barcode : null,
                    'supplier_price' => $request->supplier_price ?? 0,
                    'sales_price' => $request->sales_price ?? 0,
                    'min_stock_level' => $request->min_stock_level ?? 0,
                    'variant_specs' => empty($combo) ? null : $combo, // e.g. {"Color": "Red"}
                ]);

                if ($variantIndex === 1) $firstVariant = $variant;

                // Add Initial Stock for first variant only (or all if qty provided)
                $qty = (float) $request->quantity;
                if ($qty > 0 && $locationId) {
                    DB::table('stocks')->insert([
                        'variant_id' => $variant->id,
                        'location_id' => $locationId,
                        'quantity' => $qty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('stock_movements')->insert([
                        'variant_id' => $variant->id,
                        'location_id' => $locationId,
                        'user_id' => $request->user()->id,
                        'type' => 'in',
                        'quantity' => $qty,
                        'notes' => 'Initial Stock Entry',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $variantIndex++;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $item->load('variants')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Move Item
    public function moveItem(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $item = Item::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        $item->category_id = $request->category_id;
        $item->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Item moved successfully.',
            'data' => $item
        ]);
    }

    // Move Folder
    public function moveFolder(Request $request, $id)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $folder = Category::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        
        if ($request->parent_id == $folder->id) {
            return response()->json(['status' => 'error', 'message' => 'Cannot move a folder into itself.'], 400);
        }

        $folder->parent_id = $request->parent_id;
        $folder->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Folder moved successfully.',
            'data' => $folder
        ]);
    }

    // Update Item
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'base_sku' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'category_name' => 'nullable|string',
            'variant_specs' => 'nullable|array',
            'supplier_price' => 'nullable|numeric',
            'sales_price' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $item = Item::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);

            $categoryId = $request->category_id;
            if (!$categoryId && $request->filled('category_name')) {
                $category = Category::firstOrCreate([
                    'tenant_id' => $request->user()->tenant_id,
                    'name' => $request->category_name,
                ]);
                $categoryId = $category->id;
            }

            $brandId = $request->brand_id;
            if (!$brandId && $request->filled('brand_name')) {
                $brand = Brand::firstOrCreate([
                    'tenant_id' => $request->user()->tenant_id,
                    'name' => $request->brand_name,
                ]);
                $brandId = $brand->id;
            }

            $locationId = $request->location_id;
            if (!$locationId && $request->filled('location_name')) {
                $location = \App\Models\Location::firstOrCreate([
                    'tenant_id' => $request->user()->tenant_id,
                    'name' => $request->location_name,
                ]);
                $locationId = $location->id;
            }

            $item->name = $request->name;
            if ($request->has('base_sku')) {
                $item->base_sku = $request->base_sku;
            }
            $item->category_id = $categoryId;
            $item->brand_id = $brandId;
            $item->save();

            // Update first variant for simplicity
            $variant = $item->variants()->first();
            if ($variant) {
                if ($request->has('barcode')) {
                    $variant->barcode = $request->barcode;
                }
                if ($request->has('variant_specs')) {
                    $variant->variant_specs = $request->variant_specs;
                }
                if ($request->has('supplier_price')) {
                    $variant->supplier_price = $request->supplier_price;
                }
                if ($request->has('sales_price')) {
                    $variant->sales_price = $request->sales_price;
                }
                $variant->save();
            }

            if ($locationId) {
                // Update or merge stocks to the new location for all variants
                foreach ($item->variants as $variant) {
                    $stocks = DB::table('stocks')->where('variant_id', $variant->id)->get();
                    
                    if ($stocks->isEmpty()) {
                        // Create a default stock for the variant if it doesn't have any
                        DB::table('stocks')->insert([
                            'variant_id' => $variant->id,
                            'location_id' => $locationId,
                            'quantity' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } elseif ($stocks->count() === 1) {
                        DB::table('stocks')
                            ->where('variant_id', $variant->id)
                            ->update(['location_id' => $locationId, 'updated_at' => now()]);
                    } else {
                        // If they have multiple stocks across different locations, consolidate them into the new location
                        $totalQty = $stocks->sum('quantity');
                        DB::table('stocks')->where('variant_id', $variant->id)->delete();
                        DB::table('stocks')->insert([
                            'variant_id' => $variant->id,
                            'location_id' => $locationId,
                            'quantity' => $totalQty,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $item->load(['variants' => function($q) {
                    $q->with('stocks.location');
                }])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Update a single variant (SKU, barcode, prices, location, quantity adjustment)
    public function updateVariant(Request $request, $variantId)
    {
        $request->validate([
            'sku'            => 'nullable|string',
            'barcode'        => 'nullable|string',
            'supplier_price' => 'nullable|numeric',
            'sales_price'    => 'nullable|numeric',
            'location_id'    => 'nullable|exists:locations,id',
            'location_name'  => 'nullable|string',
            'quantity'       => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $variant = ItemVariant::with('item')->findOrFail($variantId);

            // Tenant-safety check
            if ($variant->item->tenant_id !== $request->user()->tenant_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
            }

            if ($request->filled('sku'))            $variant->sku            = $request->sku;
            if ($request->has('barcode'))           $variant->barcode        = $request->barcode;
            if ($request->has('supplier_price'))    $variant->supplier_price = $request->supplier_price;
            if ($request->has('sales_price'))       $variant->sales_price    = $request->sales_price;
            $variant->save();

            // Handle location
            $locationId = $request->location_id;
            if (!$locationId && $request->filled('location_name')) {
                $location = \App\Models\Location::firstOrCreate(
                    ['name' => $request->location_name, 'tenant_id' => $request->user()->tenant_id],
                    ['tenant_id' => $request->user()->tenant_id]
                );
                $locationId = $location->id;
            }

            // Update stock quantity if provided
            if ($request->has('quantity') && $locationId) {
                $newQty  = (float) $request->quantity;
                $oldQty  = (float) DB::table('stocks')
                    ->where('variant_id', $variantId)
                    ->where('location_id', $locationId)
                    ->value('quantity') ?? 0;

                $diff = $newQty - $oldQty;

                DB::table('stocks')->updateOrInsert(
                    ['variant_id' => $variantId, 'location_id' => $locationId],
                    ['quantity' => $newQty, 'updated_at' => now(), 'created_at' => now()]
                );

                if ($diff != 0) {
                    DB::table('stock_movements')->insert([
                        'variant_id'  => $variantId,
                        'location_id' => $locationId,
                        'user_id'     => $request->user()->id,
                        'type'        => 'adjustment',
                        'quantity'    => abs($diff),
                        'notes'       => ($diff > 0 ? 'Stock increased by ' : 'Stock decreased by ') . abs($diff),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            } elseif ($locationId) {
                // Only moving location (no qty change): reassign existing stock row
                $stocks = DB::table('stocks')->where('variant_id', $variantId)->get();
                if ($stocks->count() === 1) {
                    DB::table('stocks')
                        ->where('variant_id', $variantId)
                        ->update(['location_id' => $locationId, 'updated_at' => now()]);
                } elseif ($stocks->isEmpty()) {
                    DB::table('stocks')->insert([
                        'variant_id'  => $variantId,
                        'location_id' => $locationId,
                        'quantity'    => 0,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            DB::commit();

            $variant->load('stocks.location');
            return response()->json(['status' => 'success', 'data' => $variant]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
