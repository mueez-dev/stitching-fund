<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\ChartWidget;

class InvestmentPoolWidget extends ChartWidget
{
    protected ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 'full';
    
    public function getHeading(): string | null
    {
        return 'Available Pool Status';
    }
    
    public static function canView(): bool
    {
        return Auth::user()?->role === 'Investor';
    }
    
    protected function getData(): array
    {
        $user = Auth::user();
        
        // Get total pool amount for all investments
        $totalPool = DB::table('lats')
            ->where('user_id', $user->id)
            ->sum('initial_investment') ?? 0;
        
        // Get active pool amount (using payment_status = 'complete' for active investments)
        $activePool = DB::table('lats')
            ->where('user_id', $user->id)
            ->where('payment_status', 'complete')
            ->sum('initial_investment') ?? 0;
        
        // Get open pool amount (using payment_status = 'pending' for open/pending investments)
        $openPool = DB::table('lats')
            ->where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->sum('initial_investment') ?? 0;
        
        // Prepare data for donut chart
        $data = [
            $activePool,
            $openPool,
        ];
        
        $total = array_sum($data);
        
        return [
            'datasets' => [
                [
                    'label' => 'Available Pool Status',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',  // Green for active
                        'rgba(251, 191, 36, 0.8)', // Yellow for open
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(251, 191, 36)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => [
                'Completed Investments (' . number_format($activePool, 2) . ') - ' . ($total > 0 ? round(($activePool / $total) * 100, 1) : 0) . '%',
                'Pending Investments (' . number_format($openPool, 2) . ') - ' . ($total > 0 ? round(($openPool / $total) * 100, 1) : 0) . '%',
            ],
        ];
    }
    
    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 20,
                        'usePointStyle' => true,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => function ($dataset, $dataPoint) {
                            $label = $dataset['label'] ?? '';
                            $value = $dataset['data'][$dataPoint['dataIndex']] ?? 0;
                            return $label . ': ' . number_format($value, 2);
                        },
                    ],
                ],
            ],
        ];
    }
}
