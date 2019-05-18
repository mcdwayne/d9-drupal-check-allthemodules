Migrate Source XLS
==================

## Short description:
 
A source migrate plugin, to allow us to use XLS and XLSX files as a migration source.


## Requirements:

- Drupal 8.1.x
- PHPExcel library(>=1.8.1)
- Migrate module (>= 8.1)

## Usage:

To use this plugin you have to:

- install PHPExcel library via Composer
- install this module via default installation process

Next point it's specifying a source plugin in your migration
and all it's params:
```yml
source:
  # Specify the plugin ID.
  plugin: xls
  # Define the path to the XLS source file.
  path: public://mySources/source.xls
  # Define unique key(s).
  keys:
    - id
  # Header row(to figure out which row is should be parsed as a column titles/headers).
  header_row: 1
  # Optional param to prevent parser watching for a non-existent rows(optional).
  offset: 14
```
While you'll specify a fields mapping in your migration
you should keep in mind that your column names will be converted
to a machine readable names, e.g. "My super title" will be "my_super_title".
