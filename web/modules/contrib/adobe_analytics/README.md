Adds the Adobe Analytics system to your website.

# Requirements
* Token module.


# Installation
Install like any other module via Drush or the web administration interface.

# Configuration
Navigate to Administration > Configuration > Adobe Analytics, where
the main configuration settings live. These apply to all
non-administration pages and support additional conditions based
on the role of the user making a request.

If you need to override or extend the settings in a particular
entity, then add a field of type Adobe Analytics to the entity bundle. Once
you have done that, the edit form of an entity will show an Adobe Analytics
section where you can override the global settings.

## API
Here is an example of module code that you can use to create variables more
suited to tracking your needs by utilizing hook_adobe_analytics_variables().
This code should go in your custom module's .module file and modified
accordingly.  For illustration purposes we are adding a setting to the
adobe_anlaytics administration form to allow site administrators to control
whether or not they want to track our custom "referring_search_engine"
variable.

Note: Do not forget to rename the functions.

```php

/**
 * @file
 */

/**
* Implements hook_adobe_analytics_variables().
*/
function mymodule_adobe_analytics_variables() {
  // Initialize a variables array to be returned by this hook.
  $variables = [];
  $config_var = \Drupal::config('adobe_anlaytics.settings')->get('track_search_engine', 0);
  if ($config_var) {
    $variables['referring_search_engine'] = 'none';

    // Create a list of possible search engines that my site cares about.
    $search_engines = ['google.com', 'yahoo.com', 'bing.com', 'ask.com'];

    // Get the referring URL.
    $referer = $_SERVER['HTTP_REFERER'];

    // Check the URL to see if the request is coming from a search engine
    // and if it is, change the value of my "referring_search_engine" variable.
    foreach ($search_strings as $engine) {
      if (stripos($referer, $engine) !== FALSE) {
        $variables['referring_search_engine'] = $engine;
        break;
      }
    }
  }

  // Lets assume we need a variable called "date" and that for some reason it
  // *must* come before all the other variables. Note that if you have a
  // variable that must come at the end you can use "footer" as well.
  $header = ['date' => date('Ymd')];

  return ['variables' => $variables, 'header' => $header];
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function mymodule_form_adobe_anlaytics_admin_settings_alter(&$form, &$form_state) {
  $form['general']['adobe_anlaytics_track_search_engine'] = [
    '#type' => 'checkbox',
    '#title' => t('Track the referring search engine for every request'),
    '#default_value' => \Drupal::config(adobe_anlaytics . settings)->get('track_search_engine', 0),
  ];
}

```
