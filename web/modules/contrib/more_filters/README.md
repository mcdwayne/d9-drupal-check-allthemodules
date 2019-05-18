# More Filters

More Filters is a collection of useful Filter plugins. Currently, the module
includes:

* Ordinals Filter - Wraps ordinals (nd/st/th/rd) in <span> tags, so they can be
  superscripted with css.
* Phone Format Filter - Converts phone numbers in 555.222.1111 format to
  (555) 222-1111 format.

## Requirements

This module requires the core Filter module to be enabled.

## Installation

Install the module in the standard way. See [Installing contributed modules](https://www.drupal.org/documentation/install/modules-themes/modules-8).

## Configuration

The filters in this module can be configured and applied to content as you would
with any other filter (for a given text format).

1. Go to the "Text Formats and editors" configuration page:

   Administration > Configuration > Content authoring > Text formats and editors
   (or `/admin/config/content/formats`)

2. Click the "Configure" link for the Text Format you wish to add filter(s) to.

3. Scroll down to the "Enabled Filters" section, then click the checkbox for the
   filter(s) you wish to enable.

4. (Optional) Some of the filters in this module (such as "Ordinals") may have
   additional settings available in the "Filter Settings" section. Click the tab
   for the filter you would like to adjust settings for.

5. Click the "Save Configuration" button.

6. Create or edit a content entity, then add content to a field that uses the
   same text format you selected above (in Step 2).

7. Save the content entity - the saved content entity will be displayed with the
   filter(s) you selected above (in Step 3).

## Previous Versions

NOTE: This Drupal 8 module is a complete rewrite of the Drupal 6 more_filters
module; some filters from the original module have not been ported yet and will
be included in future updates.

## Maintainers
Rian Callahan, https://www.drupal.org/u/rc_100
