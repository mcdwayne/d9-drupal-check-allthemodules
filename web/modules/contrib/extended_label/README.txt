Extended Label module


INTRODUCTION
=============

The Extended Label module adds in the ability to create an alternative field label that is longer than the internal Drupal label and supports full markup.

Once enabled, an 'Extended Label' field will show up on the field config edit form underneath the normal label field. If this field is left blank, the usual label will be used.


REQUIREMENTS
============

Due to how required fields are styled, some hoops need to be jumped through to get extended labels for required fields styled. 

To help with this, a span with a class of "extended-required" is injected via js into the extended label. See the extended_label_form.js in the js directory and the example templates in the templates directory.

Note that:
- The following template suggestions are added:
  - form-element-label--extended
  - fieldset--extended
- The injected span requires a wrapping span with a class of
  'extended-label-inner' and it assumes that there are p tags in the extended label.
- Currently, the title_display is hijacked and '-extended-' is added in. This
  will affect code that is specifically looking for other values in title_display such as 'after' and 'before'.


INSTALLATION
============

Install as you would normally install a contributed Drupal module. See: https://www.drupal.org/documentation/install/modules-themes/modules-8


MAINTAINERS
===========

Current Maintainers:
* Michael Welford - https://www.drupal.org/u/mikejw

This project has been sponsored by:
* The University of Adelaide - https://www.drupal.org/university-of-adelaide
  Visit: http://www.adelaide.edu.au
