# WIDGET ENGINE
Provides Widget engine module for Drupal 8.  

## INTRODUCTION

This module allows to create your own widgets and integrates them into IEF 
(Inline Entity Form) and Entity Browser modules functionality. The main 
idea of that module is providing skeleton of widget engine, that you can use 
in your own way:
 * Create different type of widgets
 * Configure each widget according to its type (bundle)
 * Theme widget using provided TWIG templates
 * Attach widget to needed entities using simple Field API or UI
 * See live preview of widget in IEF after its creating or editing
 * Work with widgets on your own way: using table inline form interface or modal 
 (popup) view for selecting and creating widgets.

Also, module supports Libraries API for simple including html2canvas
library.

## REQUIREMENTS

 * **Inline Entity Form** (https://www.drupal.org/project/inline_entity_form)
 * **html2canvas** js-library (https://github.com/niklasvh/html2canvas)
 * **es6-promise** js-library (https://github.com/stefanpenner/es6-promise)
 * **Entity Browser IEF** (optional). It is required if you want integrate 
 Widget engine into Entity Browser. That module is part of _**Entity Browser**_ 
 (https://www.drupal.org/project/entity_browser)

## INSTALLATION

 1. Install library:
     1. **html2canvas**
         * Download html2canvas library from  https://github.com/niklasvh/html2canvas
         * Extract the file and rename the folder to "html2canvas" (pay attention to the
          case of the letters)
         * Put the folder to /libraries directory (or any libraries directory if you're 
         using the Libraries module)
     2. **es6-promise**
          * Download es6-promise library from  https://github.com/stefanpenner/es6-promise
          * Extract the file and rename the folder to "es6-promise" (pay attention to the
           case of the letters)
          * Put the folder to /libraries directory (or any libraries directory if you're 
          using the Libraries module)    
 2. Install module:
     * Install that module as you would normally install a contributed drupal module. See:
      https://www.drupal.org/documentation/install/modules-themes/modules-8
      for further information.
  
That's it!

## COMPOSER

### html2canvas and es6-promise libraries

1. Add the following to composer.json _require_ section
  `
    "html2canvas": "v0.5.0-beta4",
    "es6-promise": "v4.1.0"
  `

2. Add the following to composer.json _installer-paths_ section
(if not already added)
  `
    "libraries/{$name}": ["type:drupal-library"]
  `

3. Add the following to composer.json _repositories_ section
(your version may differ)

```json
    {
      "type": "package",
      "package": {
        "version": "v0.5.0-beta4",
        "name": "html2canvas",
        "type": "drupal-library",
        "source": {
          "url": "https://github.com/niklasvh/html2canvas.git",
          "type": "git",
          "reference": "v0.5.0-beta4"
        }
      }  
    }
```

```json
    {
      "type": "package",
      "package": {
        "version": "v4.1.0",
        "name": "es6-promise",
        "type": "drupal-library",
        "source": {
          "url": "https://github.com/stefanpenner/es6-promise.git",
          "type": "git",
          "reference": "v4.1.0"
        },
        "dist": {
          "url": "https://github.com/stefanpenner/es6-promise/archive/v4.1.0.zip",
          "type": "zip"
        }
      }
    }
```
4 . Open a command line terminal and navigate to the same directory as your
composer.json file and run
  `
    composer install
  `

### Widget engine module

1. Add the following to composer.json _require_ section
(your version may differ, please review the latest tag for that module in repo)
  `
    "widget_engine": "0.3.0"
  `

2. Add the following to composer.json _installer-paths_ section
(if not already added)
  `
    "docroot/modules/contrib/{$name}": ["type:drupal-module"]
  `

3. Add the following to composer.json _repositories_ section
(your version may differ, please review the latest tag for that module in repo)

```json
    {
      "type": "package",
      "package": {
        "version": "0.1",
        "name": "widget_engine",
        "type": "drupal-module",
        "source": {
          "url": "https://code.adyax.com/Widget/widget_engine.git",
          "type": "git",
          "reference": "0.1"
        }
      }
    }
```

4 . Open a command line terminal and navigate to the same directory as your
composer.json file and run
  `
    composer install
  `

## CONFIGURATION

After installation, for a quick start, just install the **Node Type Custom Page** 
module provided with this project. This will automatically set up a
using widgets inside new **Custom page** content type.

Otherwise, you can add widget engine support to any content type manually.

Module provides next components:
 * Widget entity with bundling support
 * Entity reference selection plugin for Widgets
 * Widget inline form handler
 * IEF complex widget with Widget live-preview support
 
To start using full widget engine functionality (with live-preview, IEF support) 
in some content type, you should:
 1. Add new "Entity reference" field to CT with "Other..." option
 2. Select "Widget" in "Type of item to reference" list
 3. Set "Unlimited" in "Allowed number of values"
 4. Save field
 5. Change "Manage display form" option for new field with 
 "Widget reference IEF complex" widget

### Integration with Entity Browser

For managing Widgets with Entity Browser functionality, that module provides 
next components:
 * Entity browser widget for _Widget_ entities
 * New type of Entity browser widget selector plugin that provides view and 
 add functionality
 * Entity browser live-preview support
 * Basic View for managing existing Widgets
 
To start using widget engine functionality with Entity Browser and live-preview
support, you should:
 1. Create new Entity Browser:
  1.1. Choose _Modal_ in _Display plugin_
  1.2. Choose _Select&Add tabs_ in _Widget selector plugin_
  1.3. Choose _No selection display_ in _Selection display plugin_
  1.4. Provide your settings for popup size on _Display_ step
  1.5. On _Widgets_ step add _View_ and _Entity form_ plugins (_View_ plugin 
  should be positioned at the beginning)
 2. Add new "Entity reference" field to CT with "Other..." option
 3. Select "Widget" in "Type of item to reference" list
 4. Set "Unlimited" in "Allowed number of values"
 5. Save field
 6. Change "Manage display form" option for new field with 
 "Widget entity browser" widget. Choose created Entity browser (on step 1) in
 _Entity browser_ selection and set _Rendered entity_ value on _Entity display plugin_
 settings. Set "View mode" to _Widget form_ display.

 
 
If you want use Entity Browser for managing widgets 
