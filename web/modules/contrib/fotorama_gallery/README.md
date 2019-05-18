## Introduction

The Fotorama Gallery  Module provides a display field formatter so the
site builders can easily apply the Fotorama jQuery gallery to their
image fields.

For more information on Fotorama jQuery gallery, visit the website at:
  http://fotorama.io/

To view the Fotorama source code, visit the repository on Github:
  https://github.com/artpolikarpov/fotorama

Visit the project page:
  https://www.drupal.org/project/fotorama_gallery

To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/fotorama_gallery
  
## Requirements

- fotorama library : Fotorama Gallery module requires fotorama library to works.

## Install using Composer (recommended)

If you use Composer to manage dependencies, edit `/composer.json` as follows.

1\. Run `composer require --prefer-dist composer/installers` to ensure that you have the `composer/installers` package installed. This package facilitates the installation of packages into directories other than `/vendor` (e.g. `/libraries`) using Composer.

2\. Add a library path (if not already exist) to  "installer-paths" section of `composer.json`, example:

```
"web/libraries/{$name}": ["type:drupal-library"],
```

3\. Add the following to the "repositories" section of `composer.json`:

```
{
    "type": "package",
    "package": {
        "name": "fotorama_gallery/fotorama",
        "version": "v4.6.4",
        "type": "drupal-library",
        "dist": {
            "url": "https://github.com/artpolikarpov/fotorama/releases/download/4.6.4/fotorama-4.6.4.zip",
            "type": "zip"
        }
    }
}
```
4\. Run `composer require --prefer-dist fotorama_gallery/fotorama:4.6.4` - you should find that new directories have been created
under `/libraries`

The version number ``4.6.4`` is just a example, you can replace to any version you need.

## Install manually

  - Create a `/libraries/fotorama/` directory below your Drupal root directory
  - Download fotorama files from http://fotorama.io/set-up/ into it

## Usage
* Visit the "Manage Display" task for your content time.
  E.g. /admin/structure/types/manage/article/display
* Under "Format" for the field, select "Fotorama"
* Change the Format settings as you like, see http://fotorama.io/customize/
  for further information.

## Maintainers:
* Maicol Lopez Mora (meickol) - https://www.drupal.org/u/meickol
