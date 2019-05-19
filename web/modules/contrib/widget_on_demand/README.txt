CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

 INTRODUCTION
 ------------
The Widget On Demand module provides widgets for form elements which should be
loaded on demand. It also ships with a trait to act as a framework for easy
transforming any widget to a widget on demand.

When you edit a content entity all the entity's fields have widgets assigned
which generate the form elements for each field. But there are use cases where
the user simply wants to only edit a single element or add a new element and
does not need all the form elements on the page loaded. Having that in mind and
having complex widget form elements like text_format widgets which after render
are replaced by an editor e.g. ckeditor makes the page really heavy and full of
form elements which might not even be touched by the user but slow down the
initial page load. That is the use case where Widget On Demand might be used.
Now instead of loading e.g. all the form elements for the text editors and
shipping at the initial request all the libraries for the editor replace to
happen we only ship the view of the elements, but transformed in such a way
that on submission it acts as it was just normally loaded form element.
Multiple elements could still be rearranged without the need to load their form
elements.

The magic happens when the user click on a single form element. In this moment
an ajax callback will be executed to load the real form element inclusive all
the libraries needed for it.

At the moment this module provides widget on demand implementations of the
following widgets:
-Core
--Number field
--Textfield - Text (plain)
--Text area (multiple rows) - Text (plain, long)
-Text module
--Text field - Text (formatted)
--Text area (multiple rows) - Text (formatted, long)
--Text area with a summary - Text (formatted, long, with summary)

REQUIREMENTS
------------
No other modules required

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. See:
  https://drupal.org/documentation/install/modules-themes/modules-8
  for further information.

CONFIGURATION
-------------
On the "Manage form display" page of your entity type or sub-type, you can
select the widget to use for editing. Widgets on demand have additional
settings next to the main widget settings.

MAINTAINERS
-----------
Current maintainers:
 * Hristo Chonov (hchonov) - https://drupal.org/user/2901211
