## Icons Fontello

When creating a new icon set for fontello in drupal.

1) First go to http://fontello.com. Create your own icon set and download it to
a folder. From drupal standards I would recommend the libraries folder.

2) After you have done that make sure the icons module and icons fontello 
module are enabled.

3) Go to /admin/appearance/icon_set in drupal 8 and create a new icon set for 
the fontello plugin.

4) Next step after submitting the form is determining the location of the 
fontello icons folder. And enter this path in the configuration form of the 
icon set.

5) Submit and you are done. And you can use the notation below to use the added
icons in your output through render arrays. Or go to your menu and create a new
link.

```php
$icon = [
  '#type' => 'icon',
  '#icon_set' => 'icon_set_configuration_entity_id',
  '#icon_name' => 'icon_name',
];
```

Or you could use a combination of the configuration entity id with the icon
name like this:

```php
$icon = [
  '#type' => 'icon',
  '#icon_id' => 'icon_set_configuration_entity_id:icon_name',
];
```
