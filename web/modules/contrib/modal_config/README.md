# MODAL CONFIGURATION

## Introduction

Drupal module allows to configure the links in operation drop down to open in modal.

Drupal 8 core provides a way to open any link to open in a modal across the site. However its used in very few places in core. Some of the core components like views have their own modal setup.
 
## Install
Download and install using drush/composer/drupal.org as usual.

## Configuration
1. Go to admin > Configuration > User interface > Modal configuration
2. Use 'Add new configuration' button to create a new one.
3. In add screen provide:
   3.1. Configuration name - Human readable label
   3.2. Type - Currently supports only route name (for now)
   3.3. Value - Full/partial route name (Just does `strpos()` for now) 

4. Save

**Note**: The module comes with default modal configuration that allows to open all route names with `.delete` to open in modal. 

## Future
1. Add more types
2. Better way to set value instead open text field.
3. Better way to handle value instead of `strpos()`

## Contributors

Feel free to open an [issue](https://github.com/vijaycs85/modal_config/issues/new) or [pull request](https://github.com/vijaycs85/modal_config/pulls) to improve, add new features and bug fixes.


## License

This project is distributed under the terms of the [GNU General Public License version 2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)