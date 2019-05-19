INTRODUCTION
------------

Timed node page module provides plugin types for displaying certain
content on a page based on timing. For example if you have a content
type for homepages with this plugin you can create a page (controller)
that will always show the newest published one. It also supports time
ranges so that content can have end dates as-well.

Simply put: the module enables you to display the 'current' content of
type X on one page. __This module is intended for developers.__


FEATURES
--------

 * plugins for creation of timed content pages
 * support for start and end dates
 * support for fields of type date and datetime
 * automatic route creation based on plugin definition
 * custom responses for the controller through the plugins
 * integration with **metatag** for automatic metatags inheritance
 

REQUIREMENTS
------------

The only requirement is to have nodes.


CONFIGURATION
-------------

As of configuration it is required that for which content type the
plugins are implemented must have either or both a date field for start 
and a date field for end. This you must add to the node yourself at
the content field configuration page.


GETTING STARTED
---------------

First and foremost you should read through the annotation of the plugin
 (scr/Annotation/TimedNodePage.php). After that you can start
implementing plugins under **src/Plugin/TimedNodePage**. The plugin can be
implemented with only the definition (no methods) and work in default way.
Don't forget to configure the start/end fields on the content type.
