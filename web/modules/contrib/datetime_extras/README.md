Datetime Extras
===============

Extends core Datetime and Datetime Range functionality.

1) "Date and time range with duration" is a field widget for the core
datetime_range field type that allows content creators to select a start
date/time and then optionally define the absolute end time (like the core
widget) or to specify a duration (an offset relative to the start date/time). To
use this widget, a site must additionally install the duration_field module
(version 8.x-2.0-rc2 or higher).

2) "Select list, no time" is a field widget for the core datetime field type
that allows content creators to display datetime fields with only the date. Core
itself only supports displaying dates in a select list with time. This is
sometimes not convenient when the time is irrelevant and the date is what is
needed to be displayed.
