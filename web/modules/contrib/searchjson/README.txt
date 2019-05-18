The Search JSON module helps to fetch and search the data from JSON file 
for a small amount of data.It will retrieve and search the data very fastly.
The pagination will work without page refreshing.

If you enable this module, you can use the following area.
 - Product listing with search
 - Search page for a small size of the website.
 - In the feature, I will release search JSON file with face set.

Currently, This search JSON module not depends on other drupal modules.
In the feature, it will work depends on Serialization and RESTful 
Web Services modules.

After installing this module, Please enable the Serialization and RESTful
Web Services modules and create the custom view for JSON URL

Configuration 

1. Creating View

 - Enable serialization and RESTful Web Services modules
 - Go to admin/structure/views/add and enter view name 
   then select "Provide a REST export" from REST EXPORT SETTINGS.
 - Enter REST export path
 - In the format change the field option. Then select the Title and Body filed. 
   Right now I used two filed statically. In the feature, 
   i will change dynamically.
 - The JSON URL like  [{"title":"hello","body":" welcome"}]

2. Export JSON file.

- Go to admin/config and click "Search JSON Export" from SEARCH AND METADATA
   or /admin/config/search_json/settings
- Then enter view JSON URL and submit the form. 
- The search_json.json file will store to your public folder
  [sites/default/files/search_json.json]
- Then clear all catch and goto (http://yourdomin.com/searchjson) and 
  see the output.

This module is working with help of angular js library. 
Currently, i used title and body field only, In the feature, 
I will add the images and links field etc.

I really appreciated your feedback. Please review this module.

Thank you.
