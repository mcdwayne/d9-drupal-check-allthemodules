-------------------------
INSTALLATION
-------------------------
1. Enable the Stacks Content Feed module.

2. Make sure your Drupal database config matches the config files (drush cex).
Then update stacks.settings.yml by adding:

widget_type_groups:
  contentfeed: 'Content Feed'

Make sure to import that config into your Drupal database (drush cim). This
config should be there by default, unless it is removed.

3. Copy the template files to your theme:

- Copy the "contentfeed" directory from the "stacks" directory into the "stacks"
directory in your theme. These are the default templates included with the
module.

Note: The template files under the "templates" directory control the top level
output for this widget (filters, wrappers, etc…). The template files under the
"ajax" directory control the output of the results and pagination. This is where
you can change which view mode the node is displayed with on the frontend.

- Copy the "pager" directory into our theme (in the "templates" directory). See
the pagination section below.

4. Go the form settings of the Stacks field and make sure to enable the Content
Feed widget. This works the same as it does for Basic Widgets.

5. Clear the Drupal cache.


------------------------------------
CREATING A NEW CONTENT FEED WIDGET
------------------------------------
Content feeds provide a way to display lists of content from content types or 
entities in a manner similar to Views.

An example would be you have a list of featured articles you want to display on 
the home page. You can specify the content type and the taxonomy and it will 
pull down those nodes. From there, you can control the html by outputting a 
view mode in the template.

The module comes with a default Content Feed class that looks for certain fields
and uses that to grab nodes from your site. If you want to add new options or
change the default behavior, you can create a new PHP class that does what you
need. If you want to go the custom route for a content feed, see the
stacks_examples module that is included.

Below I describe how to use the default Content Feed class.

1. Go to Structure -> Stacks -> Manage Widget Bundles and click the add new
button.

2. You can call the widget whatever you want, but make sure to select the proper
widget behavior (Content Feed) under the "Advanced Configuration" area below the
label field.

3. Add the fields from the default content feed that you want for the new
widget. Similar to other widgets, you can also add additional fields to the
bundle and they will be sent as variables to your template file, like a
description, etc...

4. Create widget directory under the “stacks” directory in your theme, with the
machine name of the new widget (replacing _ with -).

5. Next you need to create the template files based on the machine name of the
content feed widget you created. At a minimum, you need to define a "default"
variation, which would look like this.
templates/[widget_bundle]–default.html.twig
ajax/ajax_[widget_bundle]–default.html.twig

You need two template files defined for each variations of a content feed. One
template defines the area where the filters and pagination would be displayed.
The other template is used for each row in the results.

Take a look at the "contentfeed" directory under stacks directory in
stacks_content_feed module as an example. The image in the images directory is what is
displayed in the admin for each template variation. Refer to the readme file in
the main module.

6. Enable the new widget under Manage Form Display settings for each Stacks
field.

7. Clear Drupal cache.


-------------------------
PAGINATION OPTIONS
-------------------------
Copy the "pager" directory under templates in this module and put into your 
theme inside the “templates” directory. The pager.html.twig file controls the 
pagination templates that are loaded based on the pagination field. 
You can customize the html for the template files as needed. Make sure to 
review the contents of pager.html.twig file and that the includes on it points 
to the right place.

Bundled twig code should work for most themes.


-------------------------
SOLR CONFIGURATION
-------------------------
Make sure the search_api_solr module is up and running. For this you'll need to
run composer to install solr libraries. Check the install procedures of that
module.

Content Feed widgets will check two things in order to use solr as the backend
engine:

- search_api_solr module enabled
- Stacks module content feeds widget solr configuration ready.

Also, you will need two keys in the Stacks module yml config file
(stacks.settings.yml):

content_feed_search_api_index:
   widgets
content_feed_search_api_fulltext_field:
  rendered_item

In /admin/config/search/search-api you will have a solr server configured. If
not, please configure one. No special needs on this part.

In that server, you need to add a specific index for the widgets:

a- Click "add index"

b- Type an Index name. Make sure the machine name matches
what you specified on "content_feed_search_api_index" key of widgets config
file. ("widgets" in this example)

c- Data sources: Content.

d- Select the content types (bundles) you want to be searchable in this index.

e- Server: Choose the already configured solr server.

f- Make sure the "enabled" checkbox is on for this index.

g- Save, edit and go to the "Processors" tab

h- Check the options you want on this index. At a minimum you should check off
Content Access and Rendered item

i- In the "Processor settings" vertical tab at thebottom, make sure you specify
the view modes for the rendered item.

IMPORTANT:
The select view mode must render the information you want to be searchable
since solr will search within the text content of the selected view mode.

j- Save the form and go to "Fields" tab k- Click "Add fields" and make sure you
add:

  1- Under "General": Rendered HTML Output (if is not already there). This will
  add a machine name "rendered_item" in the General section. MAKE SURE: the
  machine name matches "content_feed_search_api_fulltext_field" key of widgets
  config file. Otherwise change the config file and import.


  2- Under "Content", add the following fields: - Publishing status (status) -
  uid - ID - title - Authored on - Promoted to front page - Sticky at top of
  lists - Body - Content type - And finally, all taxonomy fields that will be
  searchable in Content Feed widgets. (IE: All fields that can contain taxonomy
  vocabularies you select as filters when creating a content feed)

  3- Hit "Done", save changes and go to the "View" tab for the index.

l- Hit the link "Queue all items for reindexing".  After that make sure you
index all items (by clicking "Index now" button with a large number) or wait
for the cron to reindex all.

m- If both: Existence of index with machine name as config and search_api_solr
module are enabled, the content feed widget will attempt to use solr as backend
for   search. Otherwise, It will fall back to the database query search backend.


----------------------------------------
CREATING A CUSTOM CONTENT FEED WIDGET
----------------------------------------
In most cases, if you want to change the available variables for a content feed 
widget, you will want to use the "hook_widgets_output_alter" hook. However, you 
can also create a custom Plugin that extends the “ContentFeed” class.

The default Content Feed is meant to provide a basic example with all the
options available by default in the widget. However, if you want to change the
available options, or add new functionality, you can add custom PHP code.

To create a new custom content feed widget that has different behavior than the
default ContentFeed class, follow the steps below.

1. Take a look at the sample modules bundled in stacks:

- stacks/modules/stacks_example_code_grids
- stacks/modules/stacks_examples

The key to extend the "ContentFeed" plugin is to create a WidgetType Plugin on
your custom module. Look for the sample code:
i.e.: stacks/modules/stacks_examples/src/Plugin/WidgetType/ArticleContent.php.

3. Enable your module or clear cache if already enabled.

3. Create or edit a Widget Type
(i.e.: http://your-domain.com/admin/structure/stacks/widget_entity_type/contentfeed/edit).

4. Pick the new widget behavior (plugin) you created under "Advanced Configuration".

5. Save.
