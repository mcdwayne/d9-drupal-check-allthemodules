
Rut
===
This module defines a new element for RUN or RUT, these are unique numbers
assigned to natural or legal persons of Chile.

This version require [tifon/rut-php](https://github.com/Tifon-/rut-php) library.

Example
-------

You can use it on your custom forms like this:


```php
<?php
...
  $form['rut_client'] = array(
    '#type' => 'rut_field',
    '#title' => t('Rut'),
    '#required' => TRUE,
  );
...
?>
```

That was easy. With this you can forget validate the RUT because this element
will do it for you!.
Can also validate the RUT on the client side like this:

```php
<?php
  $form['rut_client'] = array(
    '#type' => 'rut_field',
    '#title' => t('Rut'),
    '#required' => TRUE,
    '#validate_js' => TRUE,
  );
?>
```

For this, the module uses the jquery plugin Rut created by José Joaquín Núñez.
Website: http://joaquinnunez.cl/jQueryRutPlugin/


Additional modules
------------------
This module has a submodule to implements the rut like a field and this have
integration with the devel generator.


Installation
-------------

1. Download [composer_manager](https://drupal.org/project/composer_manager) into your
   `modules` directory.

2. From the Drupal root directory, initialize composer_manager, and run it for the first time:

   ```sh
   php modules/composer_manager/scripts/init.php
   composer drupal-update
   composer dump-autoload
   ```
This will download the required libraries into the root vendor/ directory.

3. Enable the Rut module.

Notes:
- * Find out more about composer_manager usage [here](https://www.drupal.org/node/2405811).