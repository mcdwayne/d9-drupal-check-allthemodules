-- SUMMARY --

HammerJS is a Javascript plugin that supports touch gestures in the webpage.
This module registers HammerJS as a library for other modules to use in
Drupal 8.

-- INSTALLATION --

  1. Download and enable the module
  2. Download the HammerJS library (http://hammerjs.github.io/ version 2.0.8
     is recommended) and extract the file under "libraries/hammer.js" or run
     drush hammerjsplugin
  3. So we can find the hammer.min.js in /libraries/hammer.js/
  4. Find the status in /admin/reports/status

-- USAGE --

Simply add the HammerJS library in your render array:

    $build['element'] = array(
      '#attached' => array(
        'library' => array(
          'hammerjs/hammerjs',
        ),
      ),
    );
