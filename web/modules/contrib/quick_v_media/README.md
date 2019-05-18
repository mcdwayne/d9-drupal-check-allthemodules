#What is Quick V Module 

Is a fork of the features module varbase_media from Varbase Distribution.

I'm a big fan of the Varbase Distribution and wanted the people who like some of the functionality but feels that Varbase is too heavy for some website to be able to install separate features.

#Purpose of this module
Is to easy add and save images.

#Quick Installation with Drush
Download the Dropzonejs https://github.com/enyo/dropzone/archive/v4.3.0.tar.gz and Blazy https://github.com/dinbror/blazy/archive/1.8.2.tar.gz libraries
drush en bootstrap -y
drush dl dropzonejs-1.0-alpha8 -y
drush en video_embed_field -y
drush en quick_v_media -y

#Usage
Go to /admin/config/content/formats
Choose the Editor that you want to enable the Media Browser
Drag the Media Library button to Ckeditor
Enable the  Display embedded entities


#Requirements
Bootstrap Theme needs to be enabled
https://www.drupal.org/project/bootstrap
Install first the modules
1.Dropzonejs https://www.drupal.org/project/dropzonejs (!Very Important to install the 8.x-1.0 and not 8.x-2.0)
2.Blazy https://www.drupal.org/project/blazy
3.Video Embed Field https://www.drupal.org/project/video_embed_field
4.Advanced_text_formatter
5.crop
6.ctools
7.ds
8.embed
9.entity_browser
10.entity_browser_enhanced  
11.entity_embed
12.focal_point
13.inline_entity_form  
14.media_entity
15.media_entity_document
16.media_entity_image
17.responsive_image
18.views
19.views_infinite_scroll


