Contextual Filter Range Validator
=================================

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Use Cases
 * Maintainers
 * License

INTRODUCTION
------------

Contextual Filter Range Validator adds a Views contextual filter validator that
can evaluate a numeric filter value based on user-supplied constraints.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/contextual_filter_range_validator
   
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/contextual_filter_range_validator

REQUIREMENTS
------------

No special requirements.

INSTALLATION
------------

1. Download and uncompress the module manually or via Composer.
1. Enable the module from `/admin/modules`.

CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration.

Range validation can be configured on any regular Views contextual filters. See
**USE CASES** for a detailed example of how to assign validation criteria to a
filter.

USE CASES
---------

### Display a view attachment on the first page of a view only.

The example steps below can be used to display a view attachment on the first
page only:

1. Create a page view with an attachment.
1. Add a contextual filter to the attachment (*Attachment* -> *Advanced* ->
*Contextual Filters* -> *Add*)
1. From the "Add contextual filters" popup:
    - Select "This attachment (override)" from the **For** menu.
    - Select "Global" from the **Category** menu.
    - Enable the checkbox for the "Null" filter.
    - Click **Apply (this display)**.
1. Under **When the filter value is NOT available**, select *Provide a default
value* and set the following options:
    - **Type**: Query parameter
    - **Query parameter**: page
    - **Fallback value**: 0
1. Under **When the filter value IS available or a default is provided**, select
*Specify validation criteria* and set the following options:
    - **Validator**: Range
    - **Minimum value**: (blank)
    - **Maximum value**: 0
    - **Action to take if filter value does not validate**: Hide view
1. Click **Apply (this display)**.
1. Click **Save** for the full view.

Once this view is saved, the view page should only show the attachment on the 
first page because this module's validator is set to hide the attachment 
whenever the `page` URL parameter is not empty or zero (the first page/default
value).

MAINTAINERS
-----------

Current maintainers:
 * Christopher Charbonneau Wells (wells) - https://www.drupal.org/u/wells

This project is sponsored by:
 * [Cascade Public Media](https://www.drupal.org/cascade-public-media) for 
 [KCTS9.org](https://kcts9.org/) and [Crosscut.com](https://crosscut.com/).
 
LICENSE
-------

All code in this repository is licensed 
[GPLv2](http://www.gnu.org/licenses/gpl-2.0.html). A LICENSE file is not 
included in this repository per Drupal's module packing specifications.

See [Licensing on Drupal.org](https://www.drupal.org/about/licensing).
