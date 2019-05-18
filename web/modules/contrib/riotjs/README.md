About
=====
The RiotJS module loads the riot.min.js file so that developers can depend on it when building Riot.js (see http://riotjs.com/) components.

After installing (see INSTALLATION below),

  * create a library that has a dependency on riotjs/riotjs. See https://drupal.org/node/2274843#library for further information.
  * Then create your Riot components and add their pre-compiled tags (see http://riotjs.com/guide/compiler/#pre-compilation) to your library.


INSTALLATION
============

Manual
------

  * Download the minified Riot.js file from https://raw.githubusercontent.com/riot/riot/master/riot.min.js and make sure it's accessible from `/libraries/riotjs/riot.min.js`. (To use Composer instead, see instructions below)
  * Download and install the module as you would normally install a contributed Drupal module. See: https://drupal.org/documentation/install/modules-themes/modules-8 for further information.

Composer
--------
Composer may be used to download the library as follows:

1. Add the following to composer.json _require_ section
  
  ```
    "riot/riotjs": "^3.0"
  ```

2. Add the following to composer.json _installer-paths_ section
(if not already added)
  
  ```
    "libraries/{$name}": ["type:drupal-library"]
  ```

3. Add the following to composer.json _repositories_ section
(your version may differ)

  ```
    {
      "type": "package",
        "package": {
          "name": "riot/riotjs",
          "version": "3.0",
          "type": "drupal-library",
          "dist": {
            "url": "https://raw.githubusercontent.com/riot/riot/master/riot.min.js",
            "type": "file"
          }
        }
    }
  ```

4. Open a command line terminal and navigate to the same directory as your
composer.json file and run `composer update`.
