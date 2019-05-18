Name: PageRank Widget (pagerank_widget)
Author: Martin Postma ('lolandese', http://drupal.org/user/210402)
Drupal: 8.x


-- SUMMARY --

A block showing the Google pagerank of the site (e.g. PageRank 3).

It is based on http://www.fusionswift.com/2010/04/google-pagerank-script-in-php/


-- INSTALL --

Extract the package in your modules directory, '/modules'.

Enable the module at 'admin/modules'.


-- CONFIGURE --
Configuration at 'admin/config/system/pagerank_widget/settings' and
'admin/structure/block' .


-- CUSTOMIZE --

To change the content in the widget (e.g. to put the ratio first):
1. Copy the pagerank_widget.html.twig file to your theme's template folder.
2. Make your changes.
3. Clear the site cache at 'admin/config/development/performance'.

To change the style of the widget (e.g. colors):
1. Copy-paste the code in pagerank_widget.css into your theme's custom CSS file.
2. Make your changes.
3. Clear both your browser and site cache.
