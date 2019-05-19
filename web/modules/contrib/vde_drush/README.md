Overview
--------------------------------------------------------------------------------

This module provides drush command for views_data_export module.
Command exports only views with export method standard. Otherwise `\Exception` will be thrown

Update Notes 8.x-1.0-beta2
--------------------------------------------------------------------------------
Now module supports Queue API. It allows to continue interrupted operation.

File format handling made with Plugin API. To add your own format handler, implement FormatManipulatorInterface or extend FormatManipulatorDefault.

Update Notes 8.x-1.0-beta1
--------------------------------------------------------------------------------
Now module supports exporting views with export method set to batch. Technically it not uses batch, but the approach made in this module is to use the lesser amount of memory. So we do not use batch api to create batched export, but in future we're planning to add queue api so that user could continue operation.

To see the time needed for exporting one chunk of a content and numbers of items per chunks add --verbose or simple -v option to the end of a command (right after filename). The output will look like this:

> [info] Chunk exporting is done, took 0.05s.

> [info] Exporting records 2800 to 2900.


###### Plans for the next releases.
 - Add proper file format handling (currently we use format handling from [views data export module support batch operations patch](https://www.drupal.org/project/views_data_export/issues/2789531));
 - Solve memory usage problem.

Requirements
--------------------------------------------------------------------------------

* views_data_export

Installation
--------------------------------------------------------------------------------

To install this module make sure you have views_data_export module installed.

###### Basic installation:
> Download module from [module's drupal.org page](https://www.drupal.org/project/vde_drush) and paste it into modules directory.
###### Composer installation:
> run `composer require 'drupal/vde_drush'`

Usage
--------------------------------------------------------------------------------

This command executes views_data_export display of a view and writes the output to file, provided into the third
 parameter.

###### Command

`vde_drush:views-data-export`

###### Alias
`vde`

###### Parameters
1. *view_name* (The name of the view)
2. *display_id* (display id of data_export display)
3. *filename* (filename to write the result of a command. Could be absolute path into the system. If no absolute path
 provided file will be saved into project directory)

**BEWARE**: If file already exists it will be overwritten.

###### Usage examples
`drush vde_drush:views-data-export my_view_name views_data_export_display_id output.csv`

`drush vde my_view_name views_data_export_display_id output.csv`

Errors
--------------------------------------------------------------------------------

module throws `\Exception` in the next cases:
1. *my_view_name* does not exist;
2. *my_view_name* does not have provided *display_id*;
3. *display_id* does not exist;
4. Unable to create file *filename* into provided directory. In this case you have to check directory permissions or
 provide valid path.

Authors/Maintainers
--------------------------------------------------------------------------------
Alexander Domasevich, Artyom Ilyin, Pavel Vashchyla, Sergei Semchuk, Anton Lupov from [Drupal Teams](http://drupalteams.com)