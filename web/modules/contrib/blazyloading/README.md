Blazy Loading
===================

Blazy Loading that will help to enable the lazy loading in your site.

INSTRUCTIONS:
--------------

1. Enable the module blazyloading.
2. Do the blazy loading setting at the admin setting. 
3. Flush the drupal cache.

CONFIGURATION:
--------------
Blazy Loading module have below configuration setting page

1. Using this URL you can enable the module and do the some setting which will
use during the blazy loading.
	/admin/config/services/blazyloading/configuration
2. In the admin configuration below is some point for enable the CDN Server
setting.
    a. Add the URL of CDN Server like below
https://res.cloudinary.com/project_name/image/fetch/c_limit,w_cdn_server_width,
h_cdn_server_height/source_image_url
    b. Here is "cdn_server_height" parameter will replace by height of
    image.
    c. Here is "cdn_server_width" parameter will replace by width of image.
    d. And source_image_url will replace by the original image url if URL
    contains the either https or http.
