<?php

namespace App\Console\Commands;

use Aws\AutoScaling\AutoScalingClient;
use Illuminate\Console\Command;
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
        switch ($this->argument('type')) {
            case 'multi-bot':
                $this->testMultiBot();
                break;
            case 'create-autoscaling-group':
                $this->createAutoScalingGroup();
                break;
            case 'delete-autoscaling-group':
                $this->deleteAutoScalingGroup();
                break;
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

            $this->deleteAllJobs($client);
        }
    }

    private function isAllJobDone(Client $client)
    {
        while (true) {
            $finishCount = 0;

            for ($i = 0; $i < env('JOB_COUNT', 10); $i++) {
                $response = $client->request('/apis/batch/v1/namespaces/default/jobs/test-' . ($i + 1) . '/status', 'GET');
                if (isset($response['status']['succeeded']) && $response['status']['succeeded'] == 1 || isset($response['status']['failed']) && $response['status']['failed'] == (int)env('JOB_RETRY', 4)) {
                    $finishCount++;
                }
                sleep(2);
            }
            if ($finishCount == env('JOB_COUNT', 10)) return true;
        }
    }

    private function deleteAllJobs(Client $client)
    {
        $response = $client->request('/apis/batch/v1/namespaces/default/jobs', 'DELETE', [], ClusterHelper::getDeleteData());
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
