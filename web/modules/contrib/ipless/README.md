
Module: Simple LESS
Author: Damien LAGUERRE <https://www.drupal.org/user/993490>


Description
===========
Simple Less makes it easy to compile Less style sheets.

Requirements
============

This module use the 


Installation
============

Use composer :
```
composer require drupal/ipless
```


Usage
=====

You must add your less files on the libraries.yml file.

Sample your_theme.libraries.yml

```
base:
  version: 1.0
  less:
    base:
      css/styles.less: { output: css/gen/styles.css }
      css/foo.less: {}
  css:
    component:
      css/gen/styles.css: {}
```


Enable and configure the module on the performance page:

* Go to Configuration > development > Performance
* Enable "Less compilation enabled"


