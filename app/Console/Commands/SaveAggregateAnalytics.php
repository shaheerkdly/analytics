<?php

namespace App\Console\Commands;

use App\Http\Controllers\AnalyticController;
use Illuminate\Console\Command;

class SaveAggregateAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:aggregate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save aggregate data for the analytics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AnalyticController $controller)
    {
        parent::__construct();
        $this->controller = $controller;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Saving aggregate analytics...');
        $now = now()->toDateTimeString();
        echo "Started at: " . $now . "\n";
        $save = $this->controller->saveAggregateDataForPreviousDay($now);
        echo "Finished";
        $this->info('Aggregate analytics saved!');

        return true;
    }
}
