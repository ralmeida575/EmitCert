    <?php

    return [



        'default' => env('QUEUE_CONNECTION', 'sync'),



        'connections' => [

            'sync' => [
                'driver' => 'sync',
            ],

            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
                'after_commit' => false,
            ],

            'beanstalkd' => [
                'driver' => 'beanstalkd',
                'host' => 'localhost',
                'queue' => 'default',
                'retry_after' => 90,
                'block_for' => 0,
                'after_commit' => false,
            ],

            
'sqs' => [
    'driver' => 'sqs',
    'key' => env('AWS_ACCESS_KEY_ID', 'key'),
    'secret' => env('AWS_SECRET_ACCESS_KEY', 'secret'),
    'prefix' => env('AWS_SQS_ENDPOINT') . '/000000000000', 
    'queue' => env('AWS_SQS_QUEUE'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'suffix' => '',
    'endpoint' => env('AWS_SQS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
],



            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => env('REDIS_QUEUE', 'default'),
                'retry_after' => 90,
                'block_for' => null,
                'after_commit' => false,
            ],

        ],



        'batching' => [
            'database' => env('DB_CONNECTION', 'mysql'),
            'table' => 'job_batches',
        ],


        'failed' => [
            'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
            'database' => env('DB_CONNECTION', 'mysql'),
            'table' => 'failed_jobs',
        ],

    ];
