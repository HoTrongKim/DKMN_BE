<?php

namespace App\Console\Commands;

use App\Services\RevenueStatisticBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RebuildRevenueStats extends Command
{
    protected $signature = 'dkmn:build-revenue-stats
        {--from= : Optional start date (Y-m-d)}
        {--to= : Optional end date (Y-m-d)}';

    protected $description = 'Recalculate daily revenue statistics from existing booking/payment data.';

    public function handle(RevenueStatisticBuilder $builder): int
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : null;

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $count = $builder->rebuild($from, $to);

        $rangeText = sprintf(
            '%s → %s',
            $from?->toDateString() ?? 'min',
            $to?->toDateString() ?? 'max'
        );

        $this->info("Rebuilt {$count} revenue statistic row(s) for {$rangeText}.");

        return Command::SUCCESS;
    }
}
