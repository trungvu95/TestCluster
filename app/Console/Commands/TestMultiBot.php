<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use KubernetesClient\Client;
use KubernetesClient\Config;

class TestMultiBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:multi-bot';

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
        $config = Config::BuildConfigFromFile('/home/trungvl/.kube/kind-config-kind');
        $client = new Client($config);

        for ($i = 0; $i < env('JOB_COUNT', 10); $i++) {
            $response = $client->request("/apis/batch/v1/namespaces/default/jobs", 'POST', [], $this->getJobData($i + 1));

            if ($response['status'] == "Failure") {
                echo 'Job ' . ($i + 1) . " failed \n";
            }
            else {
                echo 'Job ' . ($i + 1) . " queued \n";
            }
        }

        if ($this->isAllJobDone($client)) {
            echo "All Jobs finished \n";

            $this->deleteAllJobs($client);
        }
    }

    private function getJobData($jobNumber)
    {
        return [
            'apiVersion' => 'batch/v1',
            'kind' => 'Job',
            'metadata' => [
                'name' => env('JOB_NAME') . "-$jobNumber"
            ],
            'spec' => [
                'template' => [
                    'spec' => [
                        'containers' => [
                            [
                                'name' => env('JOB_NAME') . "-$jobNumber",
                                'image' => 'trungvl6295/test-cluster:latest',
                                'env' => [
                                    [
                                        'name' => 'APP_KEY',
                                        'value' => "base64:E+kyxc1CQ52ZKu0w4mURaYeciEh2a+inD16W66UPO5o="
                                    ],
                                    [
                                        'name' => 'MONGO_DB_HOST',
                                        'value' => "10.106.0.30"
                                    ],
                                    [
                                        'name' => 'MONGO_DB_DATABASE',
                                        'value' => "test-kubernetes"
                                    ],
                                    [
                                        'name' => 'MONGO_DB_USERNAME',
                                        'value' => ""
                                    ],
                                    [
                                        'name' => 'MONGO_DB_PASSWORD',
                                        'value' => ""
                                    ]
                                ],
                                'imagePullPolicy' => 'Always',
                                'command' => ["php", "/var/www/artisan", "test:cluster"]
                            ]
                        ],
                        'restartPolicy' => 'Never'
                    ]
                ],
                'backoffLimit' => (int)env('JOB_RETRY', 4)
            ]
        ];
    }

    private function getDeleteData()
    {
        return [
            'apiVersion' => 'batch/v1',
            'kind' => 'DeleteOptions',
            'propagationPolicy' => 'Background'
        ];
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
        $response = $client->request('/apis/batch/v1/namespaces/default/jobs', 'DELETE', [], $this->getDeleteData());
    }
}
