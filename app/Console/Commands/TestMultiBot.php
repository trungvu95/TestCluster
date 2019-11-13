<?php

namespace App\Console\Commands;

use Aws\AutoScaling\AutoScalingClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use KubernetesClient\Client;
use KubernetesClient\Config;
use App\Helpers\ClusterHelper;

class TestMultiBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:eks {type}';

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
        try {
            switch ($this->argument('type')) {
                case 'multi-bot':
                    $this->testMultiBot();
                    break;
                case 'create-auto_scaling-group':
                    $this->createAutoScalingGroup();
                    break;
                case 'delete-auto_scaling-group':
                    $this->deleteAutoScalingGroup();
                    break;
            }
        }
        catch (\Exception $ex) {
            Log::error($ex->getMessage());
        }

    }

    private function testMultiBot()
    {
        $config = Config::BuildConfigFromFile('/home/trungvl/.kube/kind-config-kind');
        $client = new Client($config);

        for ($i = 0; $i < env('JOB_COUNT', 10); $i++) {
            $response = $client->request("/apis/batch/v1/namespaces/default/jobs", 'POST', [], ClusterHelper::getJobData(($i + 1)));

            if ($response['status'] == "Failure") {
                echo 'Job ' . ($i + 1) . " failed \n";
            } else {
                echo 'Job ' . ($i + 1) . " queued \n";
            }
        }

        if ($this->isAllJobDone($client)) {
            echo "All Jobs finished \n";

            $this->deleteJob($client);
        }
    }

    private function isAllJobDone(Client $client)
    {
        $finishCount = 0;

        while (true) {
            for ($i = 0; $i < env('JOB_COUNT', 10); $i++) {
                $jobName = env('JOB_NAME', 'test') . '-' . ($i + 1);
                $response = $client->request("/apis/batch/v1/namespaces/default/jobs/$jobName/status", 'GET');
                if (isset($response['status']['succeeded']) && $response['status']['succeeded'] == 1 || isset($response['status']['failed']) && $response['status']['failed'] == (int)env('JOB_RETRY', 4)) {
                    $this->deleteJob($client, $jobName);
                    $finishCount++;
                }
                sleep(2);
            }
            if ($finishCount == env('JOB_COUNT', 10)) return true;
        }
    }

    private function deleteJob(Client $client, $jobName = "")
    {
        $response = $client->request("/apis/batch/v1/namespaces/default/jobs/$jobName", 'DELETE', [], ClusterHelper::getDeleteData());
    }

    private function createAutoScalingGroup()
    {
        $client = new AutoScalingClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest'
        ]);
        $response = $client->createAutoScalingGroup(ClusterHelper::getCreateAutoScalingGroupData());

        dd($response);
    }

    private function deleteAutoScalingGroup()
    {
        $client = new AutoScalingClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest'
        ]);
        $response = $client->deleteAutoScalingGroup([
            'AutoScalingGroupName' => env('AUTO_SCALING_GROUP_NAME', 'test-group'),
            'ForceDelete' => true,
        ]);

        dd($response);
    }
}
