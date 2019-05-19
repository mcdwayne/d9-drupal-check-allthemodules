## CONTENTS ##

 * Introduction
 * Installation
 * Configuration
 * Extending the module
 * Maintainers

 ## INTRODUCTION ##

 The Video Sitemap module. Provides possibility to create video sitemap based on videos added as Media entities. 
 More information on video sitemaps can be found at
 https://developers.google.com/webmasters/videosearch/sitemaps

 ## INSTALLATION ##

 See https://www.drupal.org/documentation/install/modules-themes/modules-8
 for instructions on how to install or update Drupal modules.

 ## CONFIGURATION ##

 Visit /admin/config/search/video_sitemap to add video sitemap configuration.
 Required configurations:
 * Media bundle
 * Video Description field (from Media bundle)
 * Video location plugin

 ### VIDEO LOCATION PLUGIN ###
 Video location plugin defines the source of video and thumbnail URIs added 
 to the sitemap and depends on source field used on media bundle used for video.
 By default the module provides Video File plugin (for videos stored locally).
 There is a submodule 'Video Sitemap video_embed_field provider integration' 
 which provides a location plugin for Videos embeded using video_embed_field module.

 ### SITEMAP RE-GENERATION ###
 The sitemap can be generated manually at /admin/config/search/video_sitemap. 

 ### SITEMAP PATH ###
 Once generated, sitemap is accessible at /sitemap-video.xml

 ## EXTENDING THE MODULE ##

 ### WRITING PLUGINS ###
 Video location plugin defines the source of video. You may need to create a custom
 plugin depending on video source used on Media video bundle.

 ## MAINTAINERS ##
 * https://www.drupal.org/u/andreyjan
