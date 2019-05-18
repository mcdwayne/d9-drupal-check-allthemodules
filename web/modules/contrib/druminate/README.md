# Druminate

The Druminate module simplifies the process on making calls to the 
[Luminate Online APIs](http://open.convio.com/api/#main). Developers can call
the api by creating a Drupal plugin for each endpoint without having to manage
caching, logging and error handling.

## Installation

Install as usual.

Place the entirety of this directory in the /modules folder of your Drupal
installation. Navigate to Administer > Extend. Check the 'Enabled' box next
to the 'Druminate' and then click
the 'Save Configuration' button at the bottom.

## Configuration

Navigate to Administer > Configuration > Druminate > Settings. Enter all of the
required settings and click the 'Save Configuration' button.

## Usage

This module allows developer to make calls to the Luminate API by way of
plugins.

### Creating a Plugin

Plugins can be created in one of two ways.

* Drupal Console
    1. Run `drupal generate:plugin:skeleton`.
    1. Select
    `druminate` when asked to enter the plugin id.
* Manual Installation: 
    1. Inside your custom module create the following path 
    `src/Plugin/DruminateEndpoint`
    1. Create a class using the following template. (The annotations will 
    be described in the next section)
  ```php
  <?php

  namespace Drupal\druminate\Plugin\DruminateEndpoint;

  use Drupal\druminate\Plugin\DruminateEndpointBase;
  use Drupal\druminate\Plugin\DruminateEndpointInterface;

  /**
   * Calls the getCompanies method.
   *
   * @DruminateEndpoint(
   *  id = "getCompanies",
   *  label = @Translation("The plugin ID."),
   *  servlet = "CRTeamraiserAPI",
   *  method = "getCompaniesByInfo",
   *  authRequired = FALSE,
   *  cacheLifetime = 3600,
   *  params = {
   *    "event_type3" = "CE_Live"
   *  }
   * )
   */
  class Companies extends DruminateEndpointBase implements DruminateEndpointInterface {

  }
  ```

  ### Configuring a Plugin

  Plugins are configured via annotation.

  * id: The machine name used to internally reference the plugin.
  * label: The human readable name of the plugin.
  * servlet: The clientside or server side servlet provide via the api. *Note:*
  Please see [Servlets](http://open.convio.com/api/#main.servlet.html) for a
  more in depth explanation.
  * method: The method to be invoked. Method names are case-sensitive, and by
  convention begin with a lower-case letter with camel-cased words.
  * authRequired: Boolean parameter the determines whether or not the login_name &
  login_password are going to be sent with the request. *Note:* This parameter
  must be true for all Server APIs i.e. `SRTeamraiserAPI`
  * cacheLifetime: How long in seconds the result from the call should be stored
  in the database. Once the cacheLifetime has passed new call will be made to 
  Luminate and the new result stored. To disable caching all together set this
  value to `0`.
  * params: Any additional parameter the need to be passed to the api as part of 
  the call.

  ### Making Calls to Luminate.

  The Druminate module allows users to make calls to the Convio Api on the
  server side using Drupal.

  #### Server Side

  1. Using dependency injection create an instance of `DruminateEndpointManager`
  1. Create an instance of the plugin using the pluginId created earlier and then call the `loadData()` function.
  ```php
  $params = [
    'isFrozen' => TRUE,
    'company_name' => '%dan%',
  ];

  $companies = $this->druminateEndpointManager->createInstance('getCompanies', $params);
  $companies->loadData();
  ```
  
  #### Client Side
  The Druminate module provides a drupal wrapper for the [Luminate Extend](https://github.com/noahcooper/luminateExtend)
  library. Make sure that this library has been added to your site at libraries/luminateExtend.
  
  Create a library and attach the druminate/core library.
  ```yml
  donation.forms:
  js:
    js/donation-forms.js: {}
  dependencies:
    - core/jquery
    - core/drupalSettings
    - druminate/druminate.core
  ```
  
  Attach the library to the page with the following settings.
  ```php
  // Add Api settings to call Luminate Extend from the Client Side.
  $js_settings = [];
  if (\Drupal::config('druminate.settings')->get('secure_url')) {
    $js_settings['secure'] = \Drupal::config('druminate.settings')->get('secure_url');
  }
  if (\Drupal::config('druminate.settings')->get('non_secure_url')) {
    $js_settings['nonsecure'] = \Drupal::config('druminate.settings')->get('non_secure_url');
  }
  if (\Drupal::config('druminate.settings')->get('api_key')) {
    $js_settings['api_key'] = \Drupal::config('druminate.settings')->get('api_key');
  }
    
  // Attach the Luminate and Druminate Libs to handle form submission.
  $form['#attached']['library'][] = 'druminate_webforms/donation.forms';
  $form['#attached']['drupalSettings']['druminate']['settings'] = $js_settings;
  ```

  You can send data to the api using the documentation in Luminate Extend.
  ```javascript
  (function ($, Drupal, settings) {

    var donationForm = new Drupal.druminateCore({
      api: 'donation',
    });

    /**
     * Success callback for API submission.
     */
    donationForm.success = function (data) {
      console.log(data)
    };

    /**
     * Success callback for API submission.
     */
    donationForm.error = function (data) {
      console.log(data);
    };

  })(jQuery, Drupal, drupalSettings);
  ```