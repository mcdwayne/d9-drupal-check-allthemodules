CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Twig Typography module provides a Twig filter that integrates the PHP
Typography library and applies it to strings. An example use case is for
protecting against widowed words in titles. This could be applied in the
page-title.html.twig template like this:

```<h1{{ title_attributes.addClass('page-title') }}>{{ title|typography }}</h1>```

The PHP Typography library can do the following transformations:

Hyphenation — over 50 languages supported

Space control, including:
 * Widow protection
 * Gluing values to units
 * Forced internal wrapping of long URLs & email addresses

Intelligent character replacement, including smart handling of:
 * Quote marks (‘single’, “double”)
 * Dashes ( – )
 * Ellipses (…)
 * Trademarks, copyright & service marks (™ ©)
 * Math symbols (5×5×5=53)
 * Fractions (1⁄16)
 * Ordinal suffixes (1st, 2nd)

CSS hooks for styling:
 * Ampersands
 * Uppercase words
 * Numbers
 * Initial quotes & guillemets

 * For more information on the PHP Typography library visit:
   https://github.com/mundschenk-at/php-typography

 * For a full description of the module visit:
   https://www.drupal.org/project/twig_typography

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/twig_typography


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Twig Typography module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Within the module directory there is a file called
      typography_defaults.example.yml. This file can be copied and renamed to
      typography_default.yml and placed in the root of your custom theme. You
      can then modify the YML file to provide your own defaults for the
      typography filter to use. See class-settings.php for possible options.


A typography filter is provided and is used on strings with the pipe character:
```{{ title|typography }}```

As it only operates on strings (or objects which cast to strings) it may be
necessary to render a render array first using the render Twig function:
```{{ content|render|typography }}```

The typography filter can accept parameters to modify the defaults. For example,
to render the page title with typographic enhancements but without the de-widows
functionality:
```{{ title|typography({'set_dewidow': FALSE}) }}```


MAINTAINERS
-----------

 * Tancredi D'Onofrio (tanc) - https://www.drupal.org/u/tanc
