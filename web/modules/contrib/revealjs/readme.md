CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Functionality
* Maintainers

INTRODUCTION
------------

Reveal.js is a framework for create presentation using HTML. 
The module implements the framework with the module Drupal View.

REQUIREMENTS
------------

This module requires:

  * **Views** (https://drupal.org/project/views)
  * **Reveal.js** (https://github.com/hakimel/reveal.js)

CONFIGURATION
-------------
 
 - Before you install the module: you need to get the framework in your libraries.  
 Add this to your composer.json:
 ```
 {
   "name": "drupal/revealjs",
   "description": "Reveal.js integration for Drupal View",
   "type": "drupal-module",
   "repositories": [
     {
       "type": "package",
       "package": {
         "name": "hakimel/reveal.js",
         "version": "master",
         "type": "drupal-library",
         "dist": {
           "url": "https://github.com/hakimel/reveal.js/archive/master.zip",
           "type": "zip"
         }
       }
     }
   ],
   "require": {
     "hakimel/reveal.js": "master"
   }
 }
 ``` 
 And you can install the module.
 
 - Create a new view and apply the format "Reveal.js" and   
 choose your own settings for customize your presentation and save it.  
 
 - Add new contents (1 page/1 slide).
 
 - Go to your view and see the results.
 
FUNCTIONALITY
-------------

* **Features**:
  * ClassList
  * Markdown
  * Zoom (ALT+Click)
  * Speaker notes
  * Mathjax with a custom parameter
  * Highlight
  * Theme Beige, Blood, League, Serif, Simple, Sky, White
  * Presentation Size: custom width, height, margin, minScale and maxScale
  
* **Features not implemented**:
  * Socket.io controls
  * Custom parameter per slides
  * Print PDF
  * PostMessage API
  * Fragments
  * Custom Keyboard Bindings
  * Theme Black, Moon, Night, Solarized
  
MAINTAINERS
-----------

Drupal module:
  * B-Prod (https://www.drupal.org/u/b-prod)
  
Framework Reveal.js:
  * Hakimel (https://github.com/hakimel)




