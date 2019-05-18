![Micon](https://cloud.githubusercontent.com/assets/638651/20756168/4640dd66-b6d7-11e6-80f2-0066d01fc012.png)

# Micon

Provides functionality for adding [IcoMoon](https://icomoon.io) icon packages to Drupal and exposing them for use via CSS, HTML classes, and programmatically.

## Adding IcoMoon packages

Visit <https://icomoon.io> and build an icon package. You can utilize either **font** packages or **image** packages. Download the zip file provided by IcoMoon.

Go to `/admin/structure/micon` and follow these steps:

1. Click the **Add Micon Package** button.
2. Give your package a **Name**.<br>
  <br>
  _**Note:** A class prefix is added automatically, but it is recommended to keep the class prefix as short as reasonably possible as it is used in both CSS files and within the icon markup. The shorter it is, the smaller your rendered code and dependencies will be._<br>
  <br>

3. Place the IcoMoon zip file you previously downloaded into the file upload field
4. Click **Save** and you are done.

Published packages are immediately available for use site-wide.

## Usage

### Via CSS and HTML

The Micon admin interface provides an overview of all icons along with information on how to use them via CSS and raw HTML.

### Use with twig syntax

```php
{{ micon('fa-user') }}
```

### Use within render array

```php
// Icon only.
$output['icon'] = array(
  '#theme' => 'micon_icon',
  '#icon' => 'fa-user',
);

// Icon with text.
$output['icon_with_text'] = array(
  '#theme' => 'micon',
  '#title' => t('Hello World'),
  '#icon' => 'fa-user',
  '#position' => 'after',
  '#icon_only' => FALSE,
);
```

### Use to attach icon to translatable text

```php
// Typical translatable text.
t('Hello World');

// Translatable text with icon.
micon('Hello World')->setIcon('fa-user');
```

### Use translatable icon trait

```php
use Drupal\micon\MiconIconizeTrait;

class myClass {
    use MiconIconizeTrait;

    protected $title = 'Hello World';

    public function getTitleWithIcon() {
        return $this->micon($this->title)->setIcon('fa-user');
    }
}
```

### Automatic icon replacement

Modules and themes can add a `NAME.micon.icons.yml` that can define text that will be matched to icons.

**Exact match**

```yml
user:
  text: hello world
  icon: fa-user
```

**Regular expression match**

```yml
user_loose:
  text: ^hello
  icon: fa-user
```

When icon definitions are defined this way, modules and themes can utilize any of the above methods of icon placement _without_ having to specify an icon in code.

## Installing

The Micon module can be installed the same way typical Drupal modules are installed. Below are a couple common examples of how to install with modern conventions (i.e., `composer`, `drush`). There are no external dependencies outside of Drupal core.

### Install via Composer

Refer to Drupal's guide on [_Using composer to manage Drupal site dependencies_](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies) for more details but generally you can use the following as examples.

It's also important that you tell composer where your contributed modules, themes, and profiles should be installed instead of the composer convention of `vendor`.

> **Define the directories to which Drupal projects should be downloaded**

> By default, Composer will download all packages to the "vendor" directory. Clearly, this doesn't jive with Drupal modules, themes, profiles, and libraries. To ensure that packages are downloaded to the correct path, Drupal uses the composer/installers package. Just add the following to your composer.json to configure the directories for your Drupal site:

```json
"extra": {
    "installer-paths": {
        "modules/contrib/{$name}": ["type:drupal-module"],
        "modules/custom/{$name}": ["type:drupal-custom-module"],
        "profiles/contrib/{$name}": ["type:drupal-profile"],
        "themes/contrib/{$name}": ["type:drupal-theme"],
        "themes/custom/{$name}": ["type:drupal-custom-theme"]
    }
}
```

#### Via Drupal.org

```bash
# configure composer to look up Drupal modules,
# themes, etc. from Drupal.org
$ composer config repositories.drupal composer https://packages.drupal.org/8

# Require the 'micon' project/package from Drupal.org
$ composer require drupal/micon

#
# OR specify a version of the module:
#
$ composer require drupal/micon:1.x-dev
```

#### Via Github

Add the following to the respective `repositories` and `require` sections of your `composer.json` file:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jacerider/micon.git"
        }
    ],
    "require": {
        "jacerider/micon": "dev-8.x-1.x-dev"
    }
}
```

### Install via drush

_**Note:** Composer is the preferred convention, but if you need to commit the contributed module files to your repository then `drush` is a good alternative._

```bash
# Download the module
$ drush dl micon

# enable the module
$ drush en micon
```
