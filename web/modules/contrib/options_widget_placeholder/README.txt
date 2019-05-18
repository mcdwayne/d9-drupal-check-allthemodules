Options Widget Placeholder
==========================
So far this works with FieldAPI widgets.

Site Builders
-------------
The default presentation in Radio Buttons is N/A
The default presentation in Select element is "- None -"
This module does NOT force dashes to be included at the start and end, but you can include them yourself.

Developers
----------
This works by changing the human-readable string which accompanies the '_none' option from FormAPI.
Doesn't interfere in whether a '_none' option is presented. It simply changes it if present.
