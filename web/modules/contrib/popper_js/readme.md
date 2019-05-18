# Popper.js for Drupal 8

Provides popper.js and tooltip.js support in Drupal.

This won't do anything on its own, you have to call it via twig, the theme page attachments or in a module. This also supports the tooltip.js implementations of popper.

* [Popper.js official site with examples](https://popper.js.org/)
## Installation


Download the following library 
```
https://unpkg.com/popper.js
```
and put into 
```
/libraries/popper_js
```

## Call the popper in your JS



## Theming

### twig theming
```twig
{{ attach_library('popper_js/popper_js') }}
```

### preprocessor theming

todo

## More info

* Github: https://github.com/FezVrasta/popper.js
* Documentation: https://github.com/FezVrasta/popper.js/blob/master/docs/_includes/popper-documentation.md

## Credits

* popper_js for Drupal 8 by [Dan Feidt](https://drupal.org/u/hongpong)
* popper.js by Federico Zivolo [FezVrasta on GitHub](https://github.com/FezVrasta)