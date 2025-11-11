<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $widgets = $this->availableWidgets();

        $requestedWidgetIds = collect(
            array_filter(
                array_map('trim', explode(',', (string) $request->query('widgets', '')))
            )
        )->unique()->all();

        if (! empty($requestedWidgetIds)) {
            $widgets = array_values(array_filter(
                $widgets,
                fn (array $widget): bool => in_array($widget['id'], $requestedWidgetIds, true)
            ));
        }

        return response()->json([
            'data' => [
                'widgets' => $widgets,
                'lastUpdated' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Static widget payloads mapped to frontend mock structure.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availableWidgets(): array
    {
        return [
            [
                'id' => 'sales-overview',
                'type' => 'metric',
                'title' => 'dashboard.widgets.salesOverview.title',
                'size' => 'medium',
                'position' => ['row' => 0, 'col' => 0],
                'data' => [
                    'value' => 128450,
                    'label' => 'dashboard.widgets.salesOverview.label',
                    'change' => 12.5,
                    'changeType' => 'increase',
                    'period' => 'dashboard.widgets.salesOverview.period',
                    'unit' => 'THB',
                ],
                'config' => [
                    'icon' => 'insights',
                ],
            ],
            [
                'id' => 'top-products',
                'type' => 'table',
                'title' => 'dashboard.widgets.topProducts.title',
                'size' => 'medium',
                'position' => ['row' => 0, 'col' => 1],
                'data' => [
                    'headers' => ['Product', 'Category', 'Sales'],
                    'rows' => [
                        ['Royal Sofa', 'Living room', 48000],
                        ['Oakwood Desk', 'Office', 36250],
                        ['Nordic Lamp', 'Lighting', 21800],
                    ],
                ],
                'config' => [
                    'icon' => 'table_chart',
                ],
            ],
            [
                'id' => 'inventory-health',
                'type' => 'chart',
                'title' => 'dashboard.widgets.inventoryHealth.title',
                'size' => 'medium',
                'position' => ['row' => 1, 'col' => 0],
                'data' => [
                    'labels' => ['In Stock', 'Reserved', 'Backorder'],
                    'datasets' => [
                        [
                            'label' => 'Stock status',
                            'data' => [68, 22, 10],
                        ],
                    ],
                    'chartType' => 'doughnut',
                ],
                'config' => [
                    'icon' => 'inventory',
                ],
            ],
            [
                'id' => 'recent-activities',
                'type' => 'list',
                'title' => 'dashboard.widgets.recentActivity.title',
                'size' => 'medium',
                'position' => ['row' => 1, 'col' => 1],
                'data' => [
                    'items' => [
                        [
                            'id' => 'activity-1',
                            'title' => 'dashboard.widgets.recentActivity.items.orderCreated',
                            'time' => '10 minutes ago',
                        ],
                        [
                            'id' => 'activity-2',
                            'title' => 'dashboard.widgets.recentActivity.items.stockAdjusted',
                            'time' => '25 minutes ago',
                        ],
                        [
                            'id' => 'activity-3',
                            'title' => 'dashboard.widgets.recentActivity.items.newSupplier',
                            'time' => '1 hour ago',
                        ],
                    ],
                ],
                'config' => [
                    'icon' => 'history',
                ],
            ],
        ];
    }
}
