Search API View Modes
=========================================

This README assumes you have Search API and a search backend up and running. If not,
refer to their respective documentation to get going.

Configuration
=============

There is very little to configuring this module. Enabling it will add a new data
alteration callback in an index called "Rendered items". Select all the
view modes in its option list for the indexed entity. When processing the data,
Search API will render the entity item in each view mode, storing the HTML content in
the search backend item.

The advantage to this is that you can bypass hooking, loading, and rendering entities
for data because you know they are already cached/stored in the search backend. A good example of this
would be a search form with search presentation(s) or entities rendered in sidebars,
footers, or mobile devices.

How to Use
==========

In your code, when dealing with a response from the search backend, there should be new fields returned. Each
field will be called "view_mode_" with the view mode name affixed to the end.

For example, if you indexed Full, Search, List, and Mobile, you should see these in the
result response:

 - view_mode_full
 - view_mode_search
 - view_mode_list
 - view_mode_mobile
