CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Improved Multi Select module replace the default multi-select boxes with two
panel list and search. The first panel holds the options that are available, and
the second panel holds the options that have been selected. Two panels are
separated by "add" and "remove" buttons. You can select an item from the first
box, click the "add" button, and add it to the second box. Likewise, you can
select an item from the second box, click the "remove" button, and it goes back
into the unselected box. Re-Ordering buttons allow users to move items that
have been selected up and down.

A search box allows users to quickly filter the left column to find items.
Admins can decide how precise users need to be when entering searches.
Options include Exact Match, All Words, and Any Words.
For each option, admins can decide if they want to allow partial words.

Grouping allows users to drill down by category to find items. This is
especially useful when the user has a large list of items to select from.

Grouping works with search to enable cross-filtering.


REQUIREMENTS
------------

No special requirements for this module.


INSTALLATION
------------

* Install as usual, see http://drupal.org/node/70151 for further information.


CONFIGURATION
-------------

Settings can be configured at admin/config/user-interface/ims

 * Replace all multi-select lists
   - If checked, all multi-select fields will be replaced by improved
     multi-select widgets.

 * Replace multi-select lists on specific page
   - When not using the "Replace all multi-select lists" option, you can specify
     the pages on which multi-select field will be replaced by improved
     multi-select widgets. Enter one path per line.

 * Replace multi-select with specified selectors
   - When not using the "Replace all multi-select lists" option, you can specify
     element selectors for multi-select fields that will be replaced by improved
     multi-select widgets. Enter one selector per line.

 * Filter functionality
   - Allows admins to decide how precise users need to be when filtering.
     Options include Exact Match, All Words, and Any Words.
     For each option, admins can decide if they want partial words to match.

 * Allow re-ordering of selected items
   - If unchecked, "Move up" and "Move down" buttons will not be included.
     Selected items will always be in the order they appear in the original
     select field.
   - If checked, "Move up" and "Move Down" buttons will be included to change
     the order of selected items.
     Also, selected items will appear in the order they are added, instead of
     in the order of the original select field.

 * Reset filter when selecting a group
   - If checked, the search box and groups will work independently of one
     another. In this case, clicking on a group will clear the current filter.
   - If unchecked, the search box and groups can be used together for
     cross-filtering.

 * Button Text
   - These settings allow admins to customize the text or symbols for the
     buttons on the improved multi-select widgets.

The administration settings for this module can be configured by users with the
"administer site configuration" permission.


MAINTAINERS
-----------

Current maintainers:
* Dmitrii Varvashenia (dmitrii) - http://drupal.org/user/411965
* Reuben Turk (rooby) - http://drupal.org/user/350381
