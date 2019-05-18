***********
* README: *
***********

DESCRIPTION:
------------
This module implements ability to use MixItUp filtering and sorting for Views.
Plugin page: https://mixitup.kunkalabs.com

JQUERY COMPATIBILITY:
---------------------
MixItUp plugin requires jQuery v1.7 or greater.

INSTALLATION:
-------------
For Drupal composer version:
1. Add this module to your project using composer command:
For stable version:
composer require 'drupal/mixitup_views:^1.0'

For development version:
composer require 'drupal/mixitup_views:1.x-dev'

2. Copy mixitup library from vendor/patrickkunka to your
   libraries directory, default location web/libraries
3. Enable the module.
------------------------------------------------
For Drupal zip version:

1. Download MixItUp Views module.
2. Download MixItUp plugin from
   https://github.com/patrickkunka/mixitup/archive/v3.3.0.zip and extract its
   data to your project libraries directory, location <webroot>/libraries
   and rename the folder name to "mixitup"
3. Enable MixItUp Views module.
   That's it :)

Also, to manage libraries in your project, you can use the module:
	Libraries API.
Link: https://www.drupal.org/project/libraries

CONFIGURATION:
--------------
1. Go to edit view page
2. Select MixItUp at format section
3. Go to MixItUp format settings and change default animation settings to your
   own settings, if needed. All animation settings available here:
   https://mixitup.kunkalabs.com/docs/#group-animation
4. If you want enable sorting, You should enable "Use sorting" under
   "MixItUp Sorting settings". Don't forget to type labels there.
5. If you want to restrict vocabularies, You can do it under "MixItUp Vocabulary
   settings"
6. Select "Display all items" at Pager section for making all items available.
7. And you can customize all styles for your needs.


Author:
-------
Alexander Ivanchenko
alexsergivan@gmail.com
