<?php
return [
    'auth'=>'api',
    'super_admin_id' => 'admin',
    'base_uri'=>'/api/v1',
    'model_dir'=>app_path('/Resources'),
    'model_namespace'=>'App\\Resources',
    'controller_dir'=> app_path('/Http/Resources'),
    'controller_namespace'=>'App\\Http\\Resources',
    'factory_path'=> database_path('factories'),
    'white_ip' => ['211.226.180.203'],
    'except_ip_check_resource'=>  ['employee-payment', 'employee-payment-item', 'employer'],
    'default_actions' => [
        'index'=>'public',
        'show'=>'private',
        'update'=>'owner',
        'destroy'=>'admin',
        'store'=>'hasRole'
    ],
];

