# spry-db
Default Database Class for Spry

Spry's default Provider uses "Medoo" and you can find all the Documentation here: https://medoo.in/doc

Example Usage:
```php
$item = Spry::db()->get('items', '*', ['id' => 123]);

$items = Spry::db()->select('items', '*', ['date[>]' => '2020-01-01']);

$insertResponse = Spry::db()->insert('items', ['name' => 'test', 'date' => '2020-01-01']);

$updateResponse = Spry::db()->update('items', ['name' => 'newtest'], ['id' => 123]);

$deleteResponse = Spry::db()->delete('items', ['id' => 123]);
```
[See Full Documentation](https://medoo.in/doc) 
<br>
<br>


## Spry Config Settings
```php
$config->dbProvider = 'Spry\\SpryProvider\\SpryDB';
$config->db = [
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
```
  
  
## Schema
You can use this to build out or Modify your Database Schema.

Using Spry CLI your can run

    spry migrate
    spry migrate --dryrun     (Show what changes will be made without running anything)
    spry migrate --force      (Run Destructively. Will delete and change fields. You could loose precious data)
    

Scheme Settings
```php
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
```
### Various Column Types
    - bigint             BIGINT   21
    - bigstring          VARCHAR  255
    - bigtext            LONGTEXT
    - bool               TINYINT  1
    - date               DATE
    - datetime           DATETIME
    - decimal            DECIMAL  10,2
    - enum               ENUM
    - int                INT      10
    - number             FLOAT
    - string             VARCHAR  64
    - text               TEXT
    - time               TIME
    - tinyint            TINYINT  3
    - tinystring         VARCHAR  10
    
    
### Default Fields
By default the scheme will create an 'id', 'updated_at' and 'created_at' fields.

You can remove these by using 'use_id' and 'timestamps' in the table schema settings.
```php
'schema' => [
    'tables' => [
        'users' => [
            'use_id' => false,
            'timestamps' => false
        ]
    ]
]
```
    
### Unique Key
You can make any column 'unique' by adding the attribute:
```php
'email' => [
    'type' => 'string',
    'unique' => true
]
```
If you need to combine columns then use an array with the additional fields to be included in the unique key
```php
'email' => [
    'type' => 'string',
    'unique' => [
        'name',
        'phone'
    ]
]
```
    
