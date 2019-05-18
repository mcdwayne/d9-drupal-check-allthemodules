A-Frame Extra
==============

This module provides several tools to create more complex VR/AR scenes than the basic drupal aframe module.

Features
--------
- Provides an interface to see the status of the A-Frame library and which components are installed.

Requirements
------------

* Drupal aframe module
You need the drupal aframe module: https://www.drupal.org/project/aframe

* Aframe
-To install aframe just download one the builds and put it into a folder named "aframe". Using composer is recommanded. The builds are available here: https://github.com/aframevr/aframe/releases 

* Aframe components - (Optional)
If you want to build aframe scenes with some components of the aframe community, download them and put them into a folder named aframecomponent inside the libraries folder.
You can add this line before the "drupal-library" one in your composer.json in the "installer-paths" entree, in order to download your aframe components:
"web/libraries/aframecomponent/{$name}": ["your_component_name"],

Installation
------------

Install the module as per [standard Drupal instructions](https://www.drupal.org/documentation/install/modules-themes/modules-8).
