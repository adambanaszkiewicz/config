# Config

This library provides configurational files system, that alows You to combine multiple files and multiple formats into one object and accessing to it from simple array DOT notation.

1. Merging multiple files into one object
2. Imports files in multiple formats in the same way: **PHP**, **INI**, **YAML**
3. Import files from another files
4. Simple dot notation for accessing arrays
5. Cache system, for save parsed files into one

## Installation

### Via composer.json

```json
{
    "require": {
        "requtize/config": "^1.2.0"
    }
}
```

### Via Composer CLI

```cli
composer require requtize/config:^1.2.0
```

### Dot notation

Dot notation is created for accessing multidimentional arrays (PHP) in simple way. If we want to access to some value, we need separate each index by dot. Following code should to explain that:

```php
$array = [
    'one' => [
        'two' => [
            'three' => 'value'
        ]
    ]
];

// PHP access
$array['one']['two']['three'];

// Dot notation access
$config->get('one.two.three');
```

### Code

```php
// Without cache system
$config = new Config();
// With cache system
$config = new Config('config-filepath.php');

// Import files - multiple formats in the same way
$config->import('filename.php');
$config->import('filename.ini');
$config->import('filename.yaml');
// Or import as array
$config->import([ 'filename.php', 'filename.ini', 'filename.yaml' ]);

// Get value - each index in multidimensional array separate by dot
// Default value will be returned if given key(s) will not be existed
$value = $config->get('some.deep.index.in-config.file', 'default value');

// Set value in current request live (and in cache).
$config->set('key', 'some value to save');

// Key exists?
$config->has('key') ? 'yes' : 'no';

// Get all data in configs (in all files)
$config->all();
```

### Importing other files in config file

If you want to import other files, You musn't write any PHP to do this. Just use **imports.files** index in any file, and type files names in each index You want to import. Importing can be used for imports other formats in the same way. ***Remember that the files are searched relative to file you place importing rules!***

```php
return [
    // Other data...
    'imports' => [
        'filepath.php',
        '../../global-config/main.ini',
        './some-yaml.file.yaml'
    ]
    // Other data...
];
```

```yaml
# Other data...
imports:
    "filepath.php"
    "../../global-config/main.ini"
    "./some-yaml.file.yaml"
# Other data...
```

```ini
; Other data...
[imports]
0 = "filepath.php"
1 = "../../global-config/main.ini"
2 = "./some-yaml.file.yaml"
; Other data...
```


## @todo

- Prefixes for imported files to allow import files from different place: ***'%root%/config.file.php'***, ***'%server-config%/some.file.ini'***
- XML files support
- **You want some more...?**

## Licence

This code is licensed under MIT License.
