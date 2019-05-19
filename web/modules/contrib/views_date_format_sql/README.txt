Summary
=======

The Views Date Format SQL module allows to format date fields using SQL.
This enables group aggregation for date fields using the choosen granularity.

The core functionality is to remove the date formatting from render() and put
it in query(). I.e. format date values using SQL's DATE_FORMAT rather than
PHP's format_date.
This is achieved by assigning a new default handler to node 'created' and
'changed' date fields. This handler extends and overrides views's build in
views_handler_field_date.

The UI is completely unobtrusive, only a single checkbox "Use SQL to format
dates" is added to the handler configuration popup.

Written for and tested with Views 3. This code is rather simple and should
really go into views core some day.

Usage
=====

* Add or edit a view.
* Enable Aggregation.
* Add a date field or edit one by clicking the field name.
* In the "Configure Field" pop-up look for "Use SQL to format date"
* Try setting the date format to something like "Y-m" for monthly reports
