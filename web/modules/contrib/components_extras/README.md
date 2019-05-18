# Components Extras

Provides a Drupal 8 render element to use Twig components in render arrays.

## Dependency

* [components](https://www.drupal.org/project/components)

## How to use

This module provide a YAML plugin to register components (`components` only knows the directory where you can place twig components).

### Register components

`(my_module|my_theme).components.yml`:
```yaml
my-component:
  path: 'valid/template/path' # example: '@customer/header/header.twig'
  variables:
    - var1
    - var2
    - ...
```

**/!\Due to some restriction on Twig (dynamic block name not allowed), the registered components should use variables instead of blocks**

Example of using variables or blocks:
```twig
{% if var1 is defined %}
  {{ var1 }}
{% else %}
  {% block var1 %}
  {% endblock %}
{% endif %}
```

### Use as render element

One the component is defined in YML, you can use it as:
```php
<?php
[
  '#type' => 'component',
  '#component' => 'my-component',
  '#var1' => $var1,
  '#var2' => $var2,
];
```

## TODO

* [ ] Use a component as layout (layout_discovery + ds).