# spry-db
Default Database Class for Spry

Spry's default Provider uses "Medoo" and you can find all the Documentation here: https://medoo.in/doc

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
  
  
### Schema
You can use this to build out or Modify your Database Schema.

Using Spry CLI your can run

    spry m
    spry m --dryrun     (Show what changes will be made without running anything)
    spry m --force      (Run Destructively.  Will delete and change fields. You could loose precious data)
    

Scheme Settings

	'schema' => [
		'tables' => [
			'users' => [
				'columns' => [
					'name' => [
						'type' => 'string'
					],
					'email' => [
						'type' => 'string',
						'unique' => true
					],
					'amount' => [
						'type' => 'number',
						'default' => 0
					],
					'status' => [
						'type' => 'enum',
						'options' => ['pending','active','completed','archived',''],
					],
					'start_date' => [
						'type' => 'datetime',
						'default' => 'CURRENT_TIMESTAMP'
					],
				]
			]
		]
	]
 
#### Various Column Types

    - tinystring         VARCHAR  10
    - string             VARCHAR  64
    - bigstring          VARCHAR  255
    - text               TEXT
    - bigtext            LONGTEXT
    - tinyint            TINYINT  3
    - int                INT      10
    - bigint             BIGINT   21
    - bool               TINYINT  1
    - number             FLOAT
    - decimal            DECIMAL  10,2
    - enum               ENUM
    - datetime           DATETIME
    - date               DATE
    - time               TIME
    
    
### Default Fields
By default the scheme will create an 'id', 'updated_at' and 'created_at' fields.

You can remove these by using 'use_id' and 'timestamps' in the table schema settings.

	'schema' => [
		'tables' => [
			'users' => [
				'use_id' => false,
				'timestamps' => false
			]
		]
	]
    
    
### Unique Key
You can make any column 'unique' by adding the attribute:

    'email' => [
        'type' => 'string',
        'unique' => true
    ]
    
If you need to combine columns then use an array with the additional fields to be included in the unique key

    'email' => [
        'type' => 'string',
        'unique' => [
            'name',
            'phone'
        ]
    ]
    
    
