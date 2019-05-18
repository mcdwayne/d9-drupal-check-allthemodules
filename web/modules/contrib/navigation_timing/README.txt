Navigation Timing
-----------------

This module log front-end performance measurements with navigation timing API
exposed in compatible browsers.

There is no user-friendly reporting UI. Views integration is provided but
proper use of the data will require SQL knowledge.


INSTALLATION
------------

Enable the module and it will start logging navigation timing data.


API
---

You can add the following parameters in the query string of your Drupal page:
- 'no_js=1' to remove all JS present on the page
- 'no_css=1' to remove all CSS present on the page

There is no other PHP or JS API exposed by this module.


REPORTS
-------

There is no UI for reporting. You have to write your own SQL query to get the
informations you need from the data and group table.

A basic views integration is provided to help creating reports. Patches welcome
to improve the views integration.
