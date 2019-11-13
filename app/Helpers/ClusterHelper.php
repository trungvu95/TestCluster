<?php


namespace App\Helpers;


class ClusterHelper
{
    public static function getJobData($jobNumber)
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

    public static function getDeleteData()
    {
        return [
            'apiVersion' => 'batch/v1',
            'kind' => 'DeleteOptions',
            'propagationPolicy' => 'Background'
        ];
    }

    public static function getCreateAutoScalingGroupData()
    {
        $data = [
            'AutoScalingGroupName' => env('AUTO_SCALING_GROUP_NAME', 'test-group'),
            'LaunchConfigurationName' => env('LAUNCH_CONFIGURATION_NAME', 'test-config'),
            'MaxSize' => env('AUTO_SCALING_GROUP_MAX_SIZE', 5),
            'MinSize' => env('AUTO_SCALING_GROUP_MIN_SIZE', 2),
            'VPCZoneIdentifier' => env('VPC_ZONE_IDENTIFIER'),
            'Tags' => [
                [
                    'Key' => 'k8s.io/cluster-autoscaler/enabled'
                ],
                [
                    'Key' => 'k8s.io/cluster-autoscaler/' . env('CLUSTER_NAME', 'test-cluster')
                ]
            ]
        ];
        if (env('AUTO_SCALING_GROUP_DESIRED_CAPACITY', "") !== "") {
            $data['DesiredCapacity'] = env('AUTO_SCALING_GROUP_DESIRED_CAPACITY');
        }

        return $data;
    }
}
