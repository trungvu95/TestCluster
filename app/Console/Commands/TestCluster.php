<?php

namespace App\Console\Commands;

use App\TestKubernetes;
use Illuminate\Console\Command;

class TestCluster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cluster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "Start running bots \n";
        $current = TestKubernetes::create([
            'is_running' => true,
            'run_count' => 0
        ]);
        $runCount = 0;
        for ($i = 0; $i < 10; $i++) {
            echo "Run $i time \n";
            TestKubernetes::where('_id', $current->id)->increment('run_count');
            sleep(5);
        }
        echo "Finished running bots \n";
    }
}
