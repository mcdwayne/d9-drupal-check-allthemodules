Introduction
-------
The Domain Video Sitemap module. Provides possibility to create video sitemap
specific to each domain .
It Manages both videos uploaded to nodes and video added in youtube field. 
Videos added to block or other entity types are not indexed.
More information on domain video sitemaps can be found at
https://developers.google.com/webmasters/videosearch/sitemaps

Requirements
------------
This module requires the following modules:
1) Domain (https://www.drupal.org/project/file_entity)

Install
-------
Simply install Domain Video Sitemap like you would any other module.

1) Copy the domain_video_sitemap folder to the modules folder in your
installation.
2) Enable the module using Administer -> Modules (/admin/modules).

Configuration
-------------
Configuration page link is /admin/config/search/video-sitemap.
1) Select the content type which you want to include in sitemap. 
2) Set Cache timeout (minutes) of sitemap.
3) You can exclude file mimetype from indexing if you don't need it to be in the
sitemap.
4) Select the source of video to include in sitemap. For example you want 
sitemap only including youtube fields or videos uploaded in nodes. 

Use
-------
1) Domain Video sitemap will be available at /sitemap-video.xml.
