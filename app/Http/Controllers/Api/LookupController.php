<?php

namespace App\Http\Controllers\Api;

use App\Models\Lookup;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class LookupController extends Controller
{
    /**
     * Get lookups by parent name
     */
    public function getByParent(Request $request, string $parentName): JsonResponse
    {
        $cacheKey = "lookups.parent.{$parentName}";
        
        $lookups = Cache::remember($cacheKey, 86400, function () use ($parentName) {
            $parent = Lookup::where('name', $parentName)
                ->whereNull('parent_id')
                ->whereNull('tenant_id') // Global lookups
                ->first();

            if (!$parent) {
                return [];
            }

            return Lookup::where('parent_id', $parent->id)
                ->whereNull('tenant_id') // Global lookups
                ->orderBy('name')
                ->get(['id', 'name', 'label'])
                ->toArray();
        });

        return response()->json(['data' => $lookups]);
    }

    /**
     * Get all countries
     */
    public function getCountries(): JsonResponse
    {
        $cacheKey = 'lookups.countries.all';
        
        $countries = Cache::remember($cacheKey, 86400, function () {
            return Country::orderBy('name')->get(['id', 'name'])->toArray();
        });

        return response()->json(['data' => $countries]);
    }

    /**
     * Get states by country
     */
    public function getStatesByCountry(Request $request, int $countryId): JsonResponse
    {
        $cacheKey = "lookups.states.country.{$countryId}";
        
        $states = Cache::remember($cacheKey, 86400, function () use ($countryId) {
            return State::where('country_id', $countryId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        });

        return response()->json(['data' => $states]);
    }

    /**
     * Get state details with country information
     */
    public function getStateDetails(int $stateId): JsonResponse
    {
        $cacheKey = "lookups.states.{$stateId}";
        
        $stateData = Cache::remember($cacheKey, 86400, function () use ($stateId) {
            $state = State::with('country')->findOrFail($stateId);
            
            return [
                'id' => $state->id,
                'name' => $state->name,
                'country_id' => $state->country_id,
                'country_name' => $state->country ? $state->country->name : null,
            ];
        });

        return response()->json(['data' => $stateData]);
    }

    /**
     * Get cities by state
     */
    public function getCitiesByState(Request $request, int $stateId): JsonResponse
    {
        $cacheKey = "lookups.cities.state.{$stateId}";
        
        $cities = Cache::remember($cacheKey, 86400, function () use ($stateId) {
            return City::where('state_id', $stateId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        });

        return response()->json(['data' => $cities]);
    }

    /**
     * Get all cities with their state and country information
     */
    public function getAllCities(): JsonResponse
    {
        $cacheKey = 'lookups.cities.all';
        
        $citiesData = Cache::remember($cacheKey, 86400, function () {
            $cities = City::with(['state.country'])
                ->orderBy('name')
                ->get(['id', 'name', 'state_id']);
            
            return $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'state_id' => $city->state_id,
                    'state_name' => $city->state ? $city->state->name : null,
                    'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
                    'country_name' => $city->state && $city->state->country ? $city->state->country->name : null,
                ];
            })->toArray();
        });
        
        return response()->json(['data' => $citiesData]);
    }

    /**
     * Get city details with state and country
     */
    public function getCityDetails(int $cityId): JsonResponse
    {
        $cacheKey = "lookups.cities.{$cityId}";
        
        $cityData = Cache::remember($cacheKey, 86400, function () use ($cityId) {
            $city = City::with(['state.country'])->findOrFail($cityId);
            
            return [
                'id' => $city->id,
                'name' => $city->name,
                'state_id' => $city->state_id,
                'state_name' => $city->state ? $city->state->name : null,
                'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
                'country_name' => $city->state && $city->state->country ? $city->state->country->name : null,
            ];
        });

        return response()->json(['data' => $cityData]);
    }

    /**
     * Get marketers (users) for the current tenant
     */
    public function getMarketers(): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        // Cache with shorter TTL since user data changes more frequently
        $cacheKey = "lookups.marketers.tenant.{$tenantId}";
        
        $marketers = Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            return User::whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->toArray();
        });

        return response()->json(['data' => $marketers]);
    }
}

