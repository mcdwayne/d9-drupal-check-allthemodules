Simple XML Sitemap (Views integration)
--------------------------------------
This module provides integration of the Simple XML sitemap module [1] with the
Views module [2].


Current Features
--------------------------------------------------------------------------------
* Indexing view pages without arguments.

* Indexing view pages with arguments (the main purpose of creating this module).

The values of the view arguments are stored in the database after viewing the
view page. Saved values are used when generating a sitemap. You can choose
which arguments will be indexed, and also limit the number of view pages in
the sitemap. Values that no longer satisfy the current indexing conditions are
removed from the database by the garbage collector.

At this moment, the module doesn't work with wildcards in the view path (it
only works with the values of the contextual filters).


How to
--------------------------------------------------------------------------------
1. Install the module.
2. Create a view or use an existing one. Go to the view edit page.
3. Create a view display that uses the path, or use an existing one.
4. Open the Simple XML Sitemap block and set the indexing settings. If you need
   to index the arguments, select the ones you want from the list.
5. Wait for the argument values to be stored in the index.
6. Regenerate the sitemap.


Credits / contact
--------------------------------------------------------------------------------
Developed and maintained by Andrey Tymchuk (WalkingDexter)
https://www.drupal.org/u/walkingdexter

Ongoing development is sponsored by Drupal Coder.
https://www.drupal.org/drupal-coder-initlab-llc


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/project/simple_sitemap
2: https://www.drupal.org/project/views
