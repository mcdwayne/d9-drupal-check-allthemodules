# Lazy-load

This is a simple Drupal module to lazy-load all inline images and/or iframes
defined by content authors in entity content, usually the text-format-enabled
version of textarea fields. i.e. Node and Block body content.

The module currently depends on the [bLazy][1] image script.

There is another contributed module utilizing its namesake, [Blazy][2].
Make sure to check it out, especially if you need more advanced features and
support for many features out of the box.

This module focuses on the only area Blazy module lacks of; **inline-images**
and **inline-iframes**.

You can still use this module tandem with it, though that is not a requirement.


## Requirements

* **bLazy v1.8.2** script as a library item

## Installing Manually
- [Download bLazy][3] from https://github.com/dinbror/blazy
- Extract the downloaded file,
- rename *blazy-master* directory to *blazy*,
- copy the folder into the `/libraries` folder: i.e.: `libraries/blazy/blazy.min.js`


## Installing via Composer

- Run `composer require --prefer-dist composer/installers` to ensure that you have the `composer/installers` package installed. This package facilitates the installation of packages into directories other than `/vendor` (e.g. `/libraries`) using Composer.

- If your `composer.json` doesn't already have a definition for the `libraries` path, define one similar to the one below, depending on your setup:

``` json
"extra": {
    "installer-paths": {
        "web/libraries/{$name}": ["type:drupal-library"]
    }
}
```

- Add following to the "repositories" section of `composer.json`:

``` json
"repositories": [

    {
        "type": "package",
        "package": {
            "name": "dinbror/blazy",
            "version": "1.8.2",
            "type": "drupal-library",
            "extra": {
              "installer-name": "blazy"
            },
            "source": {
                "type": "git",
                "url": "https://github.com/dinbror/blazy",
                "reference": "1.8.2"
            }
        }
    }

]
```

- Install the required _Blazy_ library:
``` sh
composer require 'dinbror/blazy:1.8.2'
```

- Install this module:
``` sh
composer require 'drupal/lazy:^2.0'
```


## Usage
There are two options to set up for your site to lazy-load images. And both options share the same [settings](/admin/config/content/lazy).

1. Image fields
2. Inline images and Iframes managed in rich-text fields (ckeditor)

### Image fields
Repeat these steps for each image field you want to enable lazy-loading:

1. Go to **Manage display** page of the entity type you want to enable lazy-loading. *i.e. Article*
2. Select the **display** you want the change. *i.e. Teaser*
3. Click on the little cog icon to edit image display settings
4. Check **Enable lazy-loading** option, update the field settings, and save.

### Inline images and Iframes
1. **Configure** the [text format](/admin/config/content/formats) you want to enable lazy-loading. *i.e. Full HTML*
2. Check **Lazy-load images and IFRAMEs via bLazy** option to enable the filter, and save configuration.
3. Go to [Lazy-load settings](/admin/config/content/lazy).
4. Check the boxes for the inline elements (`<img>`, `<iframe>`) to be lazy-loaded via filter.
5. Save configuration

*Repeat steps 1-2 for each text-format you want to enable lazy-loading.*

To disable lazy-loading for specific image or iframes, add **skip-class** to the
class attribute. Default value (no-b-lazy) can be changed in the configuration.

``` html
<img class="no-b-lazy" src="this-image-will-load-normal" alt="">
```

### Blazy plugin can be manipulated in your theme/module javascript:

The options in **Blazy configuration** section of the settings form are the default options of the Blazy library. Refer to [Blazy plugin documentation][5] for each setting.

#### Get Blazy plugin options.
```js
let opt = drupalSettings.lazy;
```

#### Access Blazy's public functions:
| Function | Description |
|:--|:--|
| `Drupal.lazy.revalidate();` | Revalidates document for visible images. Useful if you add images with scripting or ajax |
| `Drupal.lazy.load(element(s), force);` | Forces the given element(s) to load if not collapsed. If you also want to load a collapsed/hidden elements you can add true as the second parameter. You can pass a single element or a list of elements. Tested with getElementById, getElementsByClassName, querySelectorAll, querySelector and jQuery selector. |
| `Drupal.lazy.destroy();` |  Unbind events and resets image array |


## Use Case

If you have numerous images and/or iframes in your content, it could become
a challenge to update that content to make compatible for lazy-loading. In
most cases those updates needs to be handled manually, because most of the time
if not all, the body content (HTML) doesn't follow a pattern to update
them programmatically.

This is the main reason I created this module, to avoid a need for altering body
content manually while making them easy to lazy-load.

**The *Lazy-load* filter doesn't make any changes to existing content.** It only
rewrites the `<img>` and/or `<iframe>` tags in already rendered output to have
them compatible for bLazy script to lazy-load. Since the filtered output is
cached, there should not be any changes in performance.

  [1]: http://dinbror.dk/blazy/
  [2]: https://www.drupal.org/project/blazy
  [3]: https://github.com/dinbror/blazy/archive/master.zip
  [4]: https://www.drupal.org/project/libraries
  [5]: http://dinbror.dk/blog/blazy/
