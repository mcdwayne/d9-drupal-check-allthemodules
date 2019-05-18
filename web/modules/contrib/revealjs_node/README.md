# Reveal.js Node

## INTRODUCTION

This module creates an new node bundle 'reveal_js_presentation' that
will be displayed as reveal.js slides.

## INSTALLATION

It's best install to this module via composer
```
composer require drupal/revealjs_node
```

This will also install the base-module [revealjs][https://www.drupal.org/project/revealjs]
See the readme of that module for instructions to install the
javascript-library.

Add this patch [patch][https://www.drupal.org/project/revealjs/issues/3004748] to your composer.json.
```
    "patches": {
      "drupal/revealjs": {
        "correct library path": "https://www.drupal.org/files/issues/2018-10-11/3004748-4.patch"
      }
```
This will correctly display the reveal.js status on your site's status
report.

## CONFIGURATION

The revealjs-module makes a configuration page (/admin/config/media/revealjs)
available, where you can include frontend-plugins (speakers-notes,
markdown etc.)

This module adds a text-format for ckeditor, where some basic styles are
included. (This definitely needs some more options)




## SIMILAR MODULES

[revealjs][https://www.drupal.org/project/revealjs] displays a view
as reveal.js presentation.

[slides presentation][https://www.drupal.org/project/slides_presentation]
creates a custom entity type, that will be displayed as presentation.


## MAINTAINERS

This module
  * mmbk (https://www.drupal.org/u/mmbk)

revealjs module:
  * B-Prod (https://www.drupal.org/u/b-prod)

Framework Reveal.js:
  * Hakimel (https://github.com/hakimel)
