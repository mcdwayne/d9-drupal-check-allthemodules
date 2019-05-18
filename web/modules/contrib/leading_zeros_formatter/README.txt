INTRODUCTION
------------

The Leading Zeros Field Formatter extends the IntegerFormatter to add leading
zeros to integer fields. Settings for Thousand marker and Prefix / Suffix can
also be configured. This may be useful for printing product ids, serial numbers
or similiar numbers that need to conform to a certain pattern in the display.

The maximum length of the integer field is 10 digits, nonetheless you can set
a value of up to 19 for the formatter, to 'simulate' longer values for visual
reasons (just filled with zeros, of course).

Maybe it is useful with bigint module (https://www.drupal.org/project/bigint),
which supports 19 digit integer values. However i did not test this yet.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/sandbox/snte/leading_zeros_formatter

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/leading_zeros_formatter


REQUIREMENTS
------------

This module requires the following modules:

 * Field (Core)


CONFIGURATION
-------------

 * Configure integer field in Manage display in the content type:

   - Select Leading Zeros Formatter in the format dropdown of the field
   - Set the Format setting Minimum length to the desired length
   - Possible values range from '1' (basically no leading zeros) to '19'

   Example: With Minimum length set to '8', integer '12345' is padded with
   trailing zeros, and printed as '00012345'.

   The thousand marker is respected, and rendered for example as '00.012.345'.