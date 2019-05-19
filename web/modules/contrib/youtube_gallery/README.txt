
This module help to retrive all videos from youtube channel on website.

-- GETTING STARTED --

1. Go to Administrator > Configuration > Web Services > Youtube Gallery

2. Go to configuration tab and add the google API key, Youtube channel Id 
and maximum no. of videos to be retrive.

3. On Manage Display it will display the youtube channel status like: 
	- Number of videos.
	- Configuration set by user like api key, channel id.
	- retrive the channel name.
	
4. Now add the block youtube gallery to any region wherever you want.

5. finished. 

-- OVERWRITE TEMPLATE --

Copy the module template from: 
modules/youtube_gallery/templates/youtube-gallery.html.twig
and paste it to your theme template folder.

you can see the available variables in doc comment. 

-- MAINTAINERS --

cmsMinds: https://www.drupal.org/cmsminds

Project: https://www.drupal.org/project/youtube_gallery

When use upload functionality, please set Authorized redirect URIs
path in google console client api as:
www.yourhost.com/admin/config/youtube_gallery/upload-video
