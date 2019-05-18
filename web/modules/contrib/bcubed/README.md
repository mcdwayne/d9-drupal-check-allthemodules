Introduction
------------

 * For a full description of the module, visit the project page:
   <https://drupal.org/project/bcubed>

 * To submit bug reports and feature suggestions, or to track changes:
   <https://drupal.org/project/issues/bcubed>


Requirements
------------

No special requirements for the main module. The Google Analytics submodule depends on the Google Analytics module: <https://drupal.org/project/google_analytics>


Installation
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   <https://drupal.org/documentation/install/modules-themes/modules-7>
   for further information.
   
 * Install submodules to utilize the event and action plugins they provide.
   *eg: install the Bcubed Google Analytics submodule in order to report actions
   through google analytics events*


Configuration
-------------
 
 * Configure user permissions in Administration » People » Permissions:

   - **Administer Bcubed**
   Users in roles with the "Administer Bcubed" permission will be able to
   modify the BCubed configuration for the site.

 * Configure the module in
   Administration » Configuration » System » BCubed.
   
 * Manage Condition Sets in 
   Administration » Configuration » System » BCubed » Condition Sets.
   
 * To get an overview of how to configure BCubed, click on Tour in the upper right hand
   corner of the BCubed settings page for a walkthrough.
   
 * The BCubed Examples module comes with several example condition sets covering multiple
   use cases, install it and play around with them to get an idea of how BCubed works.
   
Recommended Setup
-----------------

For basic reporting of AdBlocking statistics to Google Analytics, simply install the Google Analytics submodule.

A custom dashboard for Google Analytics is available here: <https://analytics.google.com/analytics/web/template?uid=0-vQuSsvSqyQuo4MG8xyNw>

**Note:** Some false positives may appear in the recorded statistics, due to Google Analytics capturing traffic from crawlers and bots.
To prevent these from being recorded, check the box in Google Analytics' view settings labeled "Bot Filtering".
   

Sample Integration Code
-----------------------

In order to allow bcubed to interact with the existing ads, the invocation code must be
modified to listen for an event before inserting the ad. Below is some sample code to do
this. Please note that this code will not run if BCubed is removed from the site - it will
need to be reverted.

**Note:** This is only necesary if bcubed will be used with special configuration for when an adblocker is not present. *(eg: replacing
every 3rd unblocked ad with a non-profit ad from bcubed)*
For existing ads to be shown using this code, the Show Existing Ads action (provided by the bcubed_adreplace submodule) must be enabled.

### AdSense

For integration with existing google adsense ads, replace the

    (adsbygoogle=window.adsbygoogle||[]).push({});

line at the end of each of your ad tags with:

    document.body.addEventListener("bcubedShowExistingAds",function(){(adsbygoogle=window.adsbygoogle||[]).push({})},!1);


### Revive (OpenX)

For integration with existing revive ads, replace

    OA_show(zone_id);
    
in your ad tags invocation code with:

    var id = zone_id; var tempid="bcubed-temp-"+Math.floor(1E3*Math.random()+1);document.write('<div id="'+tempid+'"></div>');document.body.addEventListener("bcubedShowExistingAds",function(){document.getElementById(tempid).outerHTML=OA_output[id];-1!==OA_output[id].indexOf("(adsbygoogle = window.adsbygoogle || []).push({})")&&(adsbygoogle=window.adsbygoogle||[]).push({})},!1);
    
replacing `zone_id` with the actual zone being called.


Custom Event / Condition / Action Plugins
-----------------------------------------

BCubed supports custom event, action, and condition plugins, which are implemented as standard D8 annotated plugins (see <https://www.drupal.org/docs/8/api/plugin-api/plugin-api-overview> for more info).

A BCubed plugin consists of a JS library and an annotated class which provides definition information such as plugin name, name of library, configuration form etc, as well as any custom logic. The JS library should contain a declaration of a new object of one of the following classes (corresponding to the plugin type): BCubedEventGeneratorPlugin, BCubedConditionPlugin, or BCubedActionPlugin. These classes are provided by the module's main JS library. For a simple example of an action plugin, see the JS Console Logger provided by the bcubed_console_logger submodule for reference. Event plugins are similar - see the AdBlocker Detected event plugin in the main module for an example.

BCubed condition plugins can supply either a pre-condition php function (evaluated before page load), or a library containing a JS plugin to check after the events have been triggered. For an example of the first type, see the RestrictPages condition provided by the main module. For an example of the second, see the NthPageView condition, also provided by the main module.

BCubed provides standard base classes which can be optionally be extended to ease the task of writing plugins: `Drupal\bcubed\EventBase`, `Drupal\bcubed\ActionBase`, and `Drupal\bcubed\ConditionBase`. Review the classes, annotations and interfaces (all found in the `src` directory) for further documentation.
