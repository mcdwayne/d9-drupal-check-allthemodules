## Summary

A module wrapper integrating [Loft Data Grids](https://github.com/aklump/loft_data_grids) with Drupal.

## Installation

Loft Data Grids must be installed via Composer, in order to get the required libraries. The tarballs are provided for informative purposes only.

1. In a shell window, go to the root of Drupal
1. Tell composer where to find the module by adding the drupal repository:

        composer config repositories.drupal composer https://packages.drupal.org/8

1. Now that composer knows where to find it, require this module (still from the drupal root directory):

        composer require drupal/loft_data_grids

1. Now go to Administer > Site Building > Modules and enable this module.

## Module updates

To update this module, use composer, e.g. the following in a shell:

    `composer update aklump/loft_data_grids --with-dependencies`
    

_If you happen to have the [PHPExcel Drupal module](https://drupal.org/project/phpexcel) installed, be aware that this module may ignore it._

### User Permissions: UI Only

Drupal permissions are provided to limit exporter visibility in UI functions only.  The distinction is that any function in this module that provides UI elements (option list, etc) will respect these permissions, however api functions will not.

These permissions can be used globally to remove certain exporters from the UI for any dependent module that uses this module's UI functions.

## Code Example

To use any of the classes in [Loft Data Grids](https://github.com/aklump/loft_data_grids) in your own module, do something like this:

    <?php
    $data = new ExportData();
    $data->add('first', 'Aaron');
    $data->add('last', 'Klump');
    $exporter = new JSONExporter($data);
    $json = $exporter->export();
    ?>
    
Refer to the library for more info.

## Contact
* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _aim_: theloft101
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>

[phpexcel]: https://drupal.org/project/phpexcel
