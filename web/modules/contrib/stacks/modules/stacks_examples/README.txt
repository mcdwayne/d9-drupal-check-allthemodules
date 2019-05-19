CONTENT FEED AND CONTENT LIST EXAMPLES
--------------------------------

The purpose of this example module is to demonstrate how to create a customized content feed 
with limited options. You will do this only if you want to limit what content editors can 
do without using the standard bundled grids, or if you have an use case where you need the 
standard grids but also a smaller, limited variation of it.

CONTENT FEED EXAMPLE:
---------------------

In this example, we will configure a content feed that displays items from the "Article" content 
type with a simple pagination, ordered by title.

When you install the example module, an "Article Content" stacks entity will be created with 
a limited set of fields, like description, pagination, sticky and title.

To enable the example content feed:

1- Enable the "Example: Grid Stacks" module
2- Create a directory in your theme named 'stacks'
3- In the module's directory (stacks_example), go to "stacks/widget-content-feed" and copy "article-content" 
   directory inside your theme's stacks directory.
4- Export your site configuration (you can do it using drush cex)
5- In your site configuration yml files, edit "stacks.settings.yml" and, under 
   "widget_type_groups" add an entry just like this: "article_content: 'Content Feed - Article Content'"
   (without double quotes)
6- Import your site configuration (drush cim) to make the change from step #4 active.
7- Go to "Manage form display" in any content type that has a stacks field, click the configure
   cogwheel icon to the right of your Stacks field (see main module readme for instructions on adding a stacks field).
8- You should see the "Article Content" stack available to select. Select the checkbox, then update and 
   save.
9- Done, now this content feed subtype is available for content editors to use.

You can read the source code her: 
stacks_examples/src/Plugin/WidgetType/ArticleContent.php
In this file, see how options are set in code for this feed, like content_types, etc.

In the standard, generic bundled content feed, those options are part of the UI for content editors to 
use, in this example, the options were removed from the UI and set in code.

You can use this ArticleContent class as a template to build other sets of fixed-option Plugins for content feeds
to use in your site.

CONTENT LIST EXAMPLE:
---------------------

The example is a very basic Photo Gallery. When you enable the module, a new stacks entity bundle 
will be created named "Photo Gallery".

Inside it, you will find the required "Add content" reference field for content list behavior and a 
sample headline field. The reference field is already configured with the required options 
(Inline entity form, complex)

Additionally, a "Photo Gallery Item" extend bundle will be created with an image and link fields.

You need to copy the twig template files into your theme. To do this, just copy 
"stacks_examples/stacks/photo-gallery" into your theme's "stacks" directory and
clear your site cache (drush cr)

In this simpler case, all you have to do is select the "Photo Gallery" entity as available in any content 
type having a "Stacks" field. Once you do this, you can start creating this variation of content lists.