# spry-db
Default Database Class for Spry

### Configuration Settings

    $config->db = [
    'provider' => 'Spry\\SpryProvider\\SpryDB',
    'database_type' => 'mysql',
    'database_name' => '',
    'server' => 'localhost',
    'username' => '',
    'password' => '',
    'charset' => 'utf8',
    'port' => 3306,
    'prefix' => 'api_x_', // Should change this to be someting Unique
    'schema' => [
      'tables' => [
        'users' => [
          'columns' => [
            'name' => [
              'type' => 'string'
            ],
            'email' => [
              'type' => 'string'
            ],
          ]
        ]
      ]
    ]
  ];
  
  
