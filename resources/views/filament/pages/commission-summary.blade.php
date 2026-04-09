<x-filament-panels::page>
@vite(['resources/css/app.css'])
    {{-- ── Filters ─────────────────────────────────────────────── --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>

    @php
        $data    = $this->getSummaryData();
        $rows    = $data['rows'];
        $s       = $data['settings'];
        $fYear   = $data['fYear'];
        $fMonth  = $data['fMonth'];
        $fmt     = fn($v) => 'RM ' . number_format((float)$v, 2);
        $period  = $this->getSelectedMonthLabel() . ($fYear ? ' ' . $fYear : '');
    @endphp

    {{-- ── Totals Cards ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-4 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Grand Total</p>
                <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $fmt($data['grandTotal']) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $period ?: 'All time' }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">🎨 Design</p>
                <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">{{ $fmt($data['totalDesign']) }}</p>
                <p class="text-xs text-gray-400 mt-1">RM {{ number_format((float)$s->design_rate, 2) }}/design</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">📢 Ads Mgmt</p>
                <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">{{ $fmt($data['totalAds']) }}</p>
                <p class="text-xs text-gray-400 mt-1">RM {{ number_format((float)$s->ads_fee, 2) }}/client</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">💼 Sales</p>
                <p class="mt-1 text-xl font-bold text-amber-600 dark:text-amber-400">{{ $fmt($data['totalSales']) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format((float)$s->sales_rate, 2) }}% commission</p>
            </div>
        </x-filament::section>
    </div>

    {{-- ── Per-Staff Table ──────────────────────────────────────── --}}
    <x-filament::section heading="Staff Commission Breakdown">
        @if ($rows->isEmpty())
            <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                No commission entries found for the selected period.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Staff</th>
                            <th class="py-3 px-4 font-semibold text-blue-600 dark:text-blue-400 text-right">🎨 Design</th>
                            <th class="py-3 px-4 font-semibold text-green-600 dark:text-green-400 text-right">📢 Ads</th>
                            <th class="py-3 px-4 font-semibold text-amber-600 dark:text-amber-400 text-right">💼 Sales</th>
                            <th class="py-3 px-4 font-semibold text-gray-800 dark:text-gray-100 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($rows as $index => $row)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-white/5' }}">
                                <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                    {{ $row['name'] }}
                                    @if ($row['pendingCount'] > 0)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            {{ $row['pendingCount'] }} pending
                                        </span>
                                    @endif
                                </td>

                                {{-- Design --}}
                                <td class="py-3 px-4 text-right">
                                    @if ($row['design_qty'] > 0)
                                        <div class="text-gray-700 dark:text-gray-200 font-medium">{{ $fmt($row['design']) }}</div>
                                        <div class="text-xs text-gray-400">
                                            {{ $row['design_qty'] }} designs
                                            @php
                                                $bonusPct = $s->designBonusPercent($row['design_qty']);
                                            @endphp
                                            @if ($bonusPct > 0)
                                                · {{ number_format($bonusPct, 0) }}% tier
                                            @else
                                                · no bonus
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Ads --}}
                                <td class="py-3 px-4 text-right">
                                    @if ($row['ads_clients'] > 0)
                                        <div class="text-gray-700 dark:text-gray-200 font-medium">{{ $fmt($row['ads']) }}</div>
                                        <div class="text-xs text-gray-400">{{ $row['ads_clients'] }} client(s)</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Sales --}}
                                <td class="py-3 px-4 text-right">
                                    @if ($row['sales_value'] > 0)
                                        <div class="text-gray-700 dark:text-gray-200 font-medium">{{ $fmt($row['sales']) }}</div>
                                        <div class="text-xs text-gray-400">RM {{ number_format($row['sales_value'], 2) }} sold</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Total --}}
                                <td class="py-3 px-4 text-right font-bold text-primary-600 dark:text-primary-400">
                                    {{ $fmt($row['total']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-white/20 bg-gray-100 dark:bg-gray-800">
                            <td class="py-3 px-4 font-bold text-gray-800 dark:text-gray-100">Total</td>
                            <td class="py-3 px-4 text-right font-bold text-blue-700 dark:text-blue-300">{{ $fmt($data['totalDesign']) }}</td>
                            <td class="py-3 px-4 text-right font-bold text-green-700 dark:text-green-300">{{ $fmt($data['totalAds']) }}</td>
                            <td class="py-3 px-4 text-right font-bold text-amber-700 dark:text-amber-300">{{ $fmt($data['totalSales']) }}</td>
                            <td class="py-3 px-4 text-right font-bold text-primary-700 dark:text-primary-300">{{ $fmt($data['grandTotal']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- ── Design Bonus Tier Reference ─────────────────────────── --}}
    <x-filament::section heading="Current Design Bonus Tiers" class="mt-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            Commission = Total Monthly Qty × RM {{ number_format((float)$s->design_rate, 2) }} × Bonus %
        </p>
        @php
            $tiers = collect($s->design_tiers ?? [])->sortBy('min_qty');
            $colors = ['blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'amber', 'green', 'teal'];
        @endphp
        @if ($tiers->isEmpty())
            <p class="text-sm text-gray-400 italic">No bonus tiers configured. Go to Commission Settings to add tiers.</p>
        @else
            <div class="flex flex-wrap gap-3">
                @foreach ($tiers as $i => $tier)
                    @php
                        $min   = $tier['min_qty'];
                        $max   = (isset($tier['max_qty']) && $tier['max_qty'] !== null && $tier['max_qty'] !== '')
                            ? $tier['max_qty'] : null;
                        $pct   = $tier['bonus_percent'];
                        $range = $max ? "{$min} – {$max}" : "{$min}+";
                        $color = $colors[$i % count($colors)];
                    @endphp
                    <div class="rounded-lg border border-{{ $color }}-200 dark:border-{{ $color }}-800
                                bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 px-4 py-3 text-center min-w-[100px]">
                        <div class="text-sm font-semibold text-{{ $color }}-700 dark:text-{{ $color }}-300">
                            {{ $range }} designs
                        </div>
                        <div class="text-2xl font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400 mt-1">
                            {{ number_format((float)$pct, 0) }}%
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            e.g. {{ $min }}×RM{{ number_format((float)$s->design_rate,0) }}×{{ number_format((float)$pct,0) }}%
                            = RM{{ number_format((int)$min * (float)$s->design_rate * (float)$pct / 100, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>
