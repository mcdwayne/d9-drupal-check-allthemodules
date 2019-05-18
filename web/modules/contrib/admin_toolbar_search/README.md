# ADMIN TOOLBAR SEARCH

This module adds a toolbar for finding links with the admin toolbar (and other
toolbars as well). This is helpful when you can't remember the location of an
admin link.

# INTRODUCTION

This module creates an addition toolbar with a search textfield. Then,
using javascript, it extract the links within the toolbars and leverages
[Jquery UI Autocomplete](https://jqueryui.com/autocomplete/) to handle the
autocompletion.

Since it parses the links within the toolbar, it doesn't need to make an ajax
request for the autocomplete suggestions.

## REQUIREMENTS

* Drupal 8
* [Admin Toolbar](https://www.drupal.org/project/admin_toolbar) module

## INSTALLATION

Admin Toolbar Search can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

* Install and enable Admin Toolbar module.
  [Admin Toolbar](https://www.drupal.org/project/admin_toolbar)
* Install and enable Admin Toolbar Search module.
  [Admin Toolbar Search](https://www.drupal.org/project/admin_toolbar_search)
* To exclude links in your toolbars from the search, you can add the class
"admin-toolbar-search-ignore" to the link.
