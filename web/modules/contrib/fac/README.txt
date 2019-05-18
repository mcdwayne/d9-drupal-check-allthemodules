CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Fast Autocomplete module provides fast IMDB-like suggestions below a text
input field. Suggestions are stored as JSON files in the public files folder so
that they can be provided to the browser relatively fast without the need for
Drupal to be bootstrapped.

When the JSON file with the suggestions for the entered combination of
characters does not exist, Drupal kicks in and returns suggestions (and stores
in a JSON file in the public files folder for future use). The JSON files are
periodically deleted after a configurable expiration period.

The suggestions are retrieved using a search service/plugin. The search
services/plugin provided by the module can be altered through hooks or extended
in custom implementations of the service class (Drupal 7) or added by
implementing a custom plugin (Drupal 8).

Basic search services/plugins provided by the module are:
 * Basic title search: performs a LIKE query on node titles
 * Search API: can query Search API indexes

The output of the suggestions (when they are nodes) can be configured using view
modes. Combined with for instance the Display Suite module you can create really
nice formatted suggestions.

 * For a full description of the module visit:
   https://www.drupal.org/project/fac

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/fac


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Fast Autocomplete module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.
 * If you want to use highlighting of the search keys in the suggestions and you
   do not want to use the script from a CDN (by default the script is included
   from a CDN) then you need to download the mark.js script from
   https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.min.js
   and save it in the /libraries/mark.js/ folder in your codebase. You can
   disable the "use CDN" option in the general settings form of the module.


CONFIGURATION
-------------

Navigate to Administration > Configuration > Search > fac to create one or more
Fast Autocomplete configurations. For each configuration you can select which
search plugin is used to create the search suggestions. You are able to
configure the behavior of the Fast Autocomplete plugin like what input to enable
the plugin on, how many suggestions to show, what view mode per entity type to
use for the suggestions, etc.

Deleting Suggestion Files:

Besides the periodical cleanup of the JSON files that contain the suggestions
that you can configure you can also manually delete all generated JSON files per
configuration. There is an option "Delete json files" in the operations
dropdown of each configuration at /admin/config/search/fac.

Alter Hooks:

There is hook_fac_empty_result_alter(&$empty_result, $context) that you can use
to alter the empty result content. For example, you can create an "Empty results
suggestions menu" that an editor can maintain, load that menu and output that as
the empty result content using this hook.

Custom Search Plugins:

The Fast Autocomplete module uses the Drupal 8 plugin system to provide the
Search Plugins that can be used to create the suggestions list based on the user
input. You can add your own search plugin by creating a new plugin that extends
\Drupal\fac\SearchBase and implements \Drupal\fac\SearchInterface. Visit
\Drupal\fac\Plugin\Search\BasicTitleSearch and
\Drupal\fac\Plugin\Search\SearchApiSearch for reference.

How the Module Works:

Basically the module is a jQuery plugin that adds a behavior on inputs that uses
the input to retrieve a JSON file with suggestions for the term that is in the
input through AJAX and show them in a suggestion box. The JSON files are
requested from the public files directory. Only when a suggestion file doest not
exist, Drupal is bootstrapped and a controller will create the suggestions,
store the JSON file and return them in the AJAX response.

The suggestions are created using a configurable backend service. The backend
service can be basically anything that returns an array of suggestions with a
suggestion being an array of entity_type and entity_id. The configured view mode
is used to render the suggestion in the result list, so the presentation of
suggestions is configurable.

JavaScript Events:

The jQuery plugin triggers two custom events "fac:requestStart" and
"fac:requestEnd" that you can use to do something when the plugin starts a
request for suggestions. For instance you might want to show a throbber while
the plugin is retrieving suggestion by adding a class "throbbing" to the input
element. You can achieve this by adding the following example behavior in your
JavaScript:

```
  Drupal.behaviors.facExample = {
    attach: function(context, settings) {
      $(Drupal.settings.fac.inputSelectors).bind('fac:requestStart', function(e) {
        $(this).addClass('throbbing');
      });
      $(Drupal.settings.fac.inputSelectors).bind('fac:requestEnd', function(e) {
        $(this).removeClass('throbbing');
      });
    }
  };
```

Limitations:

Because of the architecture of the module to use public JSON files to provide
suggestions as fast as possible there is a security-based limitation that the
search query for suggestions is performed as an anonymous user by default.
Otherwise there could potentially be an information leakage issue because one
would be able to retrieve restricted information by requesting the public JSON
files.

If you deem the risk of information leakage to be mitigated or the effect of
leakage low or non-existent you are able to configure this behavior by
unchecking the configuration option "Perform search as anonymous user only". The
search will be performed with the permissions of the logged in user and the risk
of information leakage will be reduced by the role-based hash in the JSON files
URL that is periodically updated. The default interval is once a week.


MAINTAINERS
-----------

 * Heine Deelstra (Heine) - https://www.drupal.org/u/heine
 * Martijn Vermeulen (Marty2081) - https://www.drupal.org/u/marty2081
 * Baris Wanschers (BarisW) - https://www.drupal.org/u/barisw

Supporting organizations:

 * LimoenGroen - https://www.drupal.org/limoengroen
