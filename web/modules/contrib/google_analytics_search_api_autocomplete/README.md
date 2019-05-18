CONTENTS OF THIS FILE 
=====================
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION 
------------
This module integrates Search API Autocomplete widgets 
with Google Analytics Event Tracking system.


REQUIREMENTS 
------------

This module requires the following modules:

* Google Analytics module: 
  <https://www.drupal.org/project/google_analytics>
* Search API Autocomplete module: 
  <https://www.drupal.org/project/search_api_autocomplete>


INSTALLATION 
------------

Get the files:

* For manual installation copy the 'google_analytics_search_api_autocomplete' 
  module directory into your Drupal 'modules' directory as usual.
* While using composer, use 
  "composer require drupal/google_analytics_search_api_autocomplete"

Once you've got module files:

* Enable "Google Analytics Search API Autocomplete" module at /admin/modules
* With drush, type "drush en google_analytics_search_api_autocomplete"
* Composer: "composer require drupal/google_analytics_search_api_autocomplete"

See following page for more detailed information:
<https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules>


CONFIGURATION 
-------------

Once you have module installed visit configuration page:
  
* Using menu: Configuration > System > Google Analytics Search API Autocomplete
* or manually navigate to /admin/config/google_analytics_search_api_autocomplete

Under "Select tracking" section choose autocomplete events you'd like to track, 
if you need more details about any of events, see jQuery UI documentation: 
<http://api.jqueryui.com/autocomplete/>

There's also extra checkbox to set GA cookieDomain option to "none", 
this is useful to pass GA tracking information through when you work locally, 
check following documentation page for more details: 
<https://developers.google.com/analytics/devguides/collection/analyticsjs/cookies-user-id#automatic_cookie_domain_configuration>  
   
Once you've configured the module, clear the cache, 
then navigate to the Search API page and perform some search actions.
You should be able to see the effects immediately,
in Google Analytics Real time reports ( Real-time > Events).

Full reports will be available within a day, 
overview of GA Event tracking can be found at Behaviour > Events > Overview.

Behind the scenes, the module will perform following operations:
* Check if GA Tracking ID is provided and ga() function exists
* Create custom GA tracker which will handle search autocomplete events
* Listen to configured ui.atocomplete events

When passing event to GA, following attributes will be sent:
* eventCategory - ID of Search API search 
* eventAction - name of ui.autocomplete event
* eventLabel - here we pass the search value, 
  depending on event this can be user’s input or selection
* eventValue - none - this can be only integer value

Check following page to get more explanations about GA Event Tracking system: 
<https://developers.google.com/analytics/devguides/collection/analyticsjs/events>


TROUBLESHOOTING 
---------------

If you have problems with event tracking, install a browser extension, 
which will enable full debugging in developers console:
<https://chrome.google.com/webstore/detail/google-analytics-debugger/jnkmfdileelhofjcijamephohjechhna> 


MAINTAINERS 
-----------

Current maintainers: 
 * Marek Tyczyński <https://www.drupal.org/user/2187728>

This project has been sponsored by:
 * Dropsolid - Digital business agency 
   which helps organisations at every lever of their digital journey.
   Drospolid offers Drupal development, services, trainings
   and consulting to make your digital business easy. 
   Visit https://dropsolid.com/en for more information. 
 
 
CREDITS 
-------

Initial version of this module is an outcome of time spent, code written 
and feedback provided by following people:  
* Thomas De Beuckelaer <https://www.drupal.org/user/2945061>
* Alexander Hass <https://www.drupal.org/user/85918>
* Thomas Seidl <https://www.drupal.org/user/205582>
* Samir Kabir <https://www.drupal.org/user/3511507>
* Mattias Michaux <https://www.drupal.org/user/785804>
* Douglas Deleu @ Dropsolid
* Lex Van Nieuwenhuyse @ Dropsolid
