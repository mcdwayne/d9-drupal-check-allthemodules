# Organic Groups : Site Menu
The Site Menu module extends the Organic groups Menu module's functionality. 


## Functionality
This modules provides paths within site context to administer site menus.


## Requirements
The Sites functionality is build upon [Organic Groups Menu][link-og-menu].

Following modules are required to use the Sites functionality:

* [Organic Groups Menu][link-og-menu]



## Installation
1. Enable the module.
2. Check the "Automatically create new menus" checkbox on the og_menu settings
   page (/admin/config/group/og_menu).
3. Place the site menu block in the fitting region. (/admin/structure/block).



## API

> *NOTE* : In the following examples the og_sm services are accessed directly for
sake of simplicity, however it is recommended to access these using [dependency 
injection][link-dependency-injection] whenever possible.

### Load the site menu by site.
Gets the site menu linked to the passes site.
```php
$menu = \Drupal::service('og_sm.site_menu_manager')->getMenuBySite($site);
```

### Load the current site menu
Get the site menu that matches the current site.
```php
$menu = \Drupal::service('og_sm.site_menu_manager')->getCurrentMenu();
```

### Create a site menu
Creates a site menu for the passed site.
```php
$menu \Drupal::service('og_sm.site_menu_manager')->createMenu($site);
```

### Get all site menus
Gets all site menus.
```php
$menu \Drupal::service('og_sm.site_menu_manager')->getAllMenus();
```



[link-og-menu]: https://www.drupal.org/project/og_menu
