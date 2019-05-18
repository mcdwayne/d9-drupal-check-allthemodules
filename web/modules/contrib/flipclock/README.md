The FlipClock.js module integrates the FlipClock.js plugin with Drupal.
FlipClock.js is an extendible API to create any kind of clock or counter.
It can be used as a 

* Clock
* Timer
* Countdown

This module exposes a new FlipClock.js block type and 
exposes a new formatter that can be uqsed on date fields

## Installation

### Using Composer

 * Edit your project's `composer.json` file and add to the repositories section:
   ```
   "objectivehtml/FlipClock": {
       "type": "package",
       "package": {
           "name": "objectivehtml/FlipClock",
           "type": "drupal-library",
           "version": "0.7.7",
           "dist": {
               "url": "https://github.com/objectivehtml/FlipClock/archive/
                0.7.7.zip",
               "type": "zip"
           }
       }
   }
   ```
 * Execute `composer require drupal/flipclock`.

### Manually

 * Download the [FlipClock.js library]
   (https://github.com/objectivehtml/FlipClock)
   and place the resulting directory into the libraries directory. Ensure
   `/libraries/flipclock/compiled/flipclock.min.js` exists.
 * Download theFlipClock.js module and follow the instruction for
   [installing contributed modules]
   (https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8).

## Usage

### Blocks

* Navigate to `admin/structure/block`
* Click `place block`
* Add a new instance of the block "Clock" and configure it.

### Formatter

* Navigate to the Manage display tab of your entity and change to formatter
  to "FlipClock"
* Configure formatter and save
