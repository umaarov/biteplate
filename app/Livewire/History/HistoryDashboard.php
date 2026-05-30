<?php

declare(strict_types=1);

namespace App\Livewire\History;

use App\Application\Services\AnalyticsService;
use App\Models\OrderHistoryEntry;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The manager's reporting view (Scenario C & D). All figures come from
 * {@see AnalyticsService}, which traverses the order-history Singleton through
 * its Iterator — the dashboard never knows how the log is stored.
 */
#[Layout('layouts.app')]
final class HistoryDashboard extends Component
{
    public function render(AnalyticsService $analytics): View
    {
        return view('livewire.history.history-dashboard', [
            'm' => $analytics->dashboard(),
            'recent' => OrderHistoryEntry::latest('placed_at')->limit(25)->get(),
        ]);
    }
}
