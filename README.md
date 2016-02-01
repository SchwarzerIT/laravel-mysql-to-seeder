# MySQL to Laravel Seeder

## Simple Examples

### Use without Composer autoload

```php

<?php

require_once __DIR__.'/Export.php';
$export = new Export('hostname','database','username','password');

$export->generateExport(
    null, // null equals "All Tables"
    ['these','are','boolean','values'],
    ['timestamp_column','created_at', 'updated_at'],
    ['type','date']
);

```

### Use with Composer autoload

```php
sample
```