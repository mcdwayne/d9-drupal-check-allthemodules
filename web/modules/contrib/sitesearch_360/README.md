# INTRODUCTION
Integrate sitesearch360.com into your Drupal 8 website

## Supported features
* Standard search
* Suggestions
* Site index status

## REQUIREMENTS
* A [sitesearch360.com](sitesearch360.com) account with API access
* Search (in core)
* [Key](https://www.drupal.org/project/key)

## INSTALLATION
Install the module as usual, typically via Composer:
```
composer require 'drupal/sitesearch_360:1.x-dev'
````

## CONFIGURATION
1. Go to */admin/config/search/pages*, scroll to "Search pages"
and add the "Site Search 360" search page
2. Feel free to change the settings and Save
3. Disable other Search pages and set the newly created page as default
(optional but recommended to avoid confusion)

## API: Exposed hooks
### hook_sitesearch_360_query_params_alter()
Allows other modules to alter the query parameters sent to the API,
for the regular search.
```
function MYMODULE_sitesearch_360_search_query_params_alter(&$params) {
  $params['query']['limit'] = "100";
}
````

Allows other modules to alter the query parameters sent to the API,
for the suggestions search.
```
function MYMODULE_sitesearch_360_suggests_query_params_alter(&$params) {
  $params['query']['limit'] = "5";
}
````

### hook_sitesearch_360_results_alter()
Allows other modules to alter the original results, before they are prepared.
For example:
```
function MYMODULE_sitesearch_360_results_alter(&$results) {
  foreach ($results['suggests']['_'] as $index => $result) {
    $results['suggests']['_'][$index]['name'] = 'My name: ' . $result['name'];
  }
}
````

### hook_sitesearch_360_prepared_suggests_alter()
Allows other modules to alter the prepared suggestions.
For example:
```
function MYMODULE_sitesearch_360_prepared_suggests_alter(&$items) {
  foreach ($items as $index => $item) {
    $items[$index]['label'] = $item['label'] . ' My very own suggestion!';
  }
}
````

### hook_sitesearch_360_prepared_results_alter()
Allows other modules to alter the prepared results.
For example:
```
function MYMODULE_sitesearch_360_prepared_suggests_alter(&$items) {
  foreach ($items as $index => $item) {
    $items[$index]['title'] = $item['title'] . ' My very own result!';
  }
}
````

## Enhancing the display
### Images
In order to display the image associated with a search result, if present,
just override the template */content/search-result.html.twig* in your theme
and add something like this:
````
{% if image %}
  <img src="{{ image }}" alt="Something describing the image" />
{% endif %}
````

## Further improvements
The feature set is quite complete.
If you have any request please open an issue in [the tracker](https://www.drupal.org/project/issues/search/sitesearch_360).

## Credits
The development of this module is fully sponsored by
[Liip](https://www.liip.ch/en).
Although we are not affiliate in any way with
[sitesearch360.com](sitesearch360.com), we'd like to thank them for providing
us with the necessary, API-enabled account.
