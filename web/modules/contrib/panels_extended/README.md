Panels Extended
===============

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Blocks
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------
This module adds several features to the panels module like disabling and
scheduling blocks directly from the interface. It also allows panels to be
outputted as JSON.

Besides the new features, the panel content form has been optimized and it
now allows blocks to give more information about their configuration, disabled
and scheduling status and much more.

FEATURES
--------
A list of extra features compared to default Panels when using the panel type
'Extended panels' in combination with builder 'Extended':

 * Page title: browse available tokens.
 * Disable / enable blocks.
 * Schedule blocks by start and end date.
 * Allow blocks to be invisible by implementing the VisibilityInterface.
 * Optimized table with blocks:
   - Moved block plugin ID below block title
   - Show extra block information when implementing AdminInfoInterface.
   - When block is disabled, unscheduled or not visible, show indication by 
     background color and text.

Output panels to JSON by using panel type 'Extended panels' in combination
with builder 'JSON':

 * All features of builder 'Extended' are available.
 * The panel page is now also available as JSON by adding the format=json
   GET-parameter to the url.
 * Modify the output of a block by implementing JsonOutputInterface.

Other features:
 * Configure the default display_variant for new variants.
 * Configure which display_builders should be hidden when using extended_panels variant.
 * Configure the default display_builder when using extended_panels variant.
 * Configure which layouts should be hidden when choosing a layout.
 * Configure which blocks should be hidden when selecting a block.
 * Fix showing validation errors on Add/Edit block forms.

REQUIREMENTS
------------
This module requires the following modules enabled:

 * Panels (https://www.drupal.org/project/panels)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/documentation/install/modules-themes/modules-8

CONFIGURATION
-------------
Configure the panel pages as you would normally do, except you now have the
extended features available when selecting panel type 'Extended panels'
in combination with builder 'Extended' or 'JSON'.

BLOCKS
------
In my projects, I'm creating several custom blocks which are the same over and
over again: add configurable settings, do not render when no content, request
data from the database, etc etc. Recently we've been outputting our panels
and blocks to JSON, so I've added a basic implementation for this within
this module and a more extended example in the submodule
`panels_extended_blocks`.

### [Drupal\panels_extended\BlockConfig\BlockConfigBase](src/BlockConfig/BlockConfigBase.php)
A basic class which you can extend to implement your own block configurations.
The goal of these configurations are to:
1. change the block configuration form
2. change the information displayed on the panels content form
3. change the JSON output
4. change the block visibility

Implement one of the supplied interfaces in src/BlockConfig for this.

Examples are in the submodule.

### [Drupal\panels_extended\JsonBlockBase](src/JsonBlockBase.php)
A basic block implementation for outputting to JSON, using the BlockConfigBase
configurations to output data. 

### Submodule panels_extended_blocks
See the [README](panels_extended_blocks/README.md) of this submodule for details.

TROUBLESHOOTING
---------------

  * No option to disable, enable or schedule? 
    Did you use the 'Extended panels' variant?
  * Disabled / unscheduled blocks are still displaying?
    Did you use one of the supplied builders?
  * JSON output doesn't work?
    Did you select the 'JSON' builder?

MAINTAINERS
-----------
Current maintainers:

 * Remko Klein (remkoklein) - https://www.drupal.org/u/remkoklein
