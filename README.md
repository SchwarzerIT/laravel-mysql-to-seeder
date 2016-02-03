# MySQL to Laravel Seeder

I was in need of a tool to export Data from an existing MySQL DB to a Laravel Seeder.
I searched online for it, but found not the right tool I wanted. I stumbled upon the
[namelivia/mysql-to-laravel-seed](https://github.com/namelivia/mysql-to-laravel-seed).

But it ...
- ... used outdated functions
- ... forced me to create a export schema
- ... didn't create working Seeders (missing `use Illuminate\Database\Seeder;`)
- and the Seeders didn't look nice

I decided to create my own version of a MySQL to Laravel Seeder Tool.

#### works with

| PHP Version | MySQL Version |
|-------------|---------------|
| 5.6.17      | 5.6.25        |
| 5.6.17      | 5.7.10        |
| 7.0.2       | 5.6.25        |
| 7.0.2       | 5.7.10        |

I welcome all notifications about working environments.
Just mail me ( [info@schwarzer.it](info@schwarzer.it) ) and
I'll put it up here, or send a merge request.

## Simple Examples

Create a file named export.php in your Laravel root (*where your `.env` is*) and execute it by using `php ./export.php` . Done.

### Use without Composer autoload

```php

<?php

require_once __DIR__.'/path/to/schwarzer/laravel-mysql-to-seeder/src/Export.php';

$export = new Export('hostname','database','username','password');

$export->generateExport(
    null, // null equals "All Tables" ( $this->getAllTableNames() )
    './database/seeds', // if null './database/seeds' is applied
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
   null,  // null equals "All Tables" ( $this->getAllTableNames() )
   './database/seeds', // if null './database/seeds' is applied
   ['these','are','boolean','values'],
   ['timestamp_column','created_at', 'updated_at'],
   ['type','date']
);

```