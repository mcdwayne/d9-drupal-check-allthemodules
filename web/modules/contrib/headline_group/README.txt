CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Headline Group module provides new field types. This field provides a
mechanism to add subheads and "superheads" -- text above a headline -- to
headlines in a semantic and flexible manner. This module uses a pattern
described by this recommendation from the W3C:
 * https://www.w3.org/TR/html51/common-idioms-without-dedicated-elements.html#subheadings-subtitles-alternative-titles-and-taglines

Three fields are made available:
 * Superhead (Text above a headline)
 * Headline
 * Subhead (Text below a headline)

 * For a full description of the module visit:
   https://www.drupal.org/project/headline_group

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/headline_group


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Headline Group module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Content type > [Content type to
       edit] and add a new Headline Group field.
    3. Configuration options are provided to hide or show the superhead /
       subhead fields on content entry.
    4. Choose the Headline behavior: Do not use the entity title, Fall back to
       entity title if headline left empty, or Always use entity title. Save.

A formatter is provided that wraps spans in an outer headline tag, but other
patterns may be provided through additional formatters. The class name, root
tag, and BEM preference are set in the field display settings.

For example:

<div class="headline-group">
  <span class="headline-group__superhead">Hey! Ho! Let’s Go</span>
  <span class="headline-group__head">The Ramones</span>
  <span class="headline-group__subhead">I Wanna be Sedated</span>
</div>


MAINTAINERS
-----------

 * Andy Hebrank (ahebrank) - https://www.drupal.org/u/ahebrank
 * John Williams (thudfactor) - https://www.drupal.org/u/thudfactor

Supporting Organization:

 * NewCity - https://www.drupal.org/newcity
