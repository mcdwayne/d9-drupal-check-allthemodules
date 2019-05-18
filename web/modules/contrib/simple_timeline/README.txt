CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Customization
 * Maintainers

INTRODUCTION
------------

Simple timeline provide a Views style plugin to display views row as a timeline.
You can choose to display rows with fields and customize each field as  you want
with standard options provided by Views. Or you can choose to display row as
entities rendered (content/view mode).

INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/documentation/install/modules-themes/modules-8 for
further information.

CONFIGURATION
-------------

Create a new view and choose the style plugin Simple Timeline.
Settings provided by the views style plugin timeline are :

* Position of rows
you can set the position of rows related to the timeline : on the left, on the
right or alternated.
* Position of the marker
You can set the marker's vertical position : top, center or bottom of each row.

CUSTOMIZATION
-------------

To customize the timeline and/or marker look, you can override some simple css
rules in your theme.

For example.

The timeline color.
ul.timeline-list:after {
  background-color: #555555;
}

And the marker color and form.
ul.timeline-list li.timeline-item .timeline-item-wrapper span.timeline-marker {
  background: #fff;
  border: 3px solid #555555;
  border-radius: 0;
}

MAINTAINERS
-----------

Current maintainer for 8.x branch:
 * flocondetoile - https://drupal.org/u/flocondetoile
