# MySQL to Laravel Seeder

## Simple Examples

Create a file named export.php in your Laravel root and execute it by using `php ./export.php` . Done.

### Use without Composer autoload

```php

<?php

require_once __DIR__.'/vendor/schwarzer/laravel-mysql-to-seeder/src/Export.php';

$export = new Export('hostname','database','username','password');

$export->generateExport(
    null, // null equals "All Tables"
    './database/seeds', // Or just null. null equals './database/seeds', you can use a path of your choice
    ['these','are','boolean','values'],
    ['timestamp_column','created_at', 'updated_at'],
    ['type','date']
);

```

### Use with Composer autoload

```php

<?php

require __DIR__.'/vendor/autoload.php';

$export = new \Schwarzer\LaravelHelper\MySQLToSeeder\Export('hostname','database','username','password');

$export->generateExport(
   null, // null equals "All Tables"
   './database/seeds', // Or just null. null equals './database/seeds', you can use a path of your choice
   ['these','are','boolean','values'],
   ['timestamp_column','created_at', 'updated_at'],
   ['type','date']
);

```