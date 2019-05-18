A-Frame
=======

This module provides integration with [A-Frame](http://aframe.io).

> A-Frame is a framework for creating virtual reality web experiences that work
> across desktop, mobile, and the Oculis Rift.
>
> Source: https://aframe.io



Features
--------

* Native A-Frame elements/primitives as Drupal elements.
* Views Style plugin: A-Frame scene.
* A-Frame scene [Display Suite](https://drupal.org/project/ds) layout.
* Field Formatter plugins for:
  * Image fields:
    * [<a-image>](https://aframe.io/docs/primitives/a-image.html)
    * [<a-curvedimage>](https://aframe.io/docs/primitives/a-curvedimage.html)
    * [<a-sky>](https://aframe.io/docs/primitives/a-sky.html)
  * File fields:
    * [<a-model>](https://aframe.io/docs/primitives/a-model.html)



Requirements
------------

* Aframe
To install aframe just download one the builds and put it into a folder named "aframe". Using composer is recommanded. The builds are available here: https://github.com/aframevr/aframe/releases 
The best way to do it is probably to include aframe as a library in your composer.json.

Example:

{
    "name": "drupal-composer/drupal-project",
    "description": "Composer example",
    "type": "project",
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
	{
	  "type": "package",
	  "package": {
            "name": "Aframe",
	    "version": "0.8",
	    "type": "drupal-library",
	    "dist": {
              "url": "https://github.com/aframevr/aframe/archive/v0.8.0.zip",
	      "type": "zip"
	    }
	  }
	}
    ],
    "require": {
        "aframe": "0.8",
    },
    "extra": {
        "installer-paths": {
            "web/libraries/{$name}": ["type:drupal-library"],
        }
    }
}


Installation
------------

Install the module as per [standard Drupal instructions](https://www.drupal.org/documentation/install/modules-themes/modules-8).



Usage
-----

There are multiple ways to use this module:

* Build an A-Frame scene as a Drupal render array.
* Render multiple entities as an A-Frame scene using the Views Style plugin and
  the Field Formatters.
* Render an entity type as A-Frame scenes using the [Display Suite](https://drupal.org/project/ds)
  layout and the Field Formatters.

Enable the A-Frame Example module to see more details.



Credits
-------

* Integration Drupal module developed by:
  - [Eleonel Basili (eleonel)](http://www.eleonelbasili.co.nz)
  - [Stuart Clark (Deciphered)](http://stuar.tc/lark)
* A-Frame is developed by [Mozilla](http://www.mozilla.org)
