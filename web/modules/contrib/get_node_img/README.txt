README for get_node_img
-----------------------

Info
----
Drupal Version: 8.X
Last update: 2017-04-19 0200 +0000
Project page: http://drupal.org/project/get_node_img

Introduction
------------
The most convenient way to attach images to Drupal nodes is to use imagefields
The get_node_img module provides an easy way to fetch these attached images.
In the minimum, you need to know the node id and field machine name of the
image.  This is mostly a convenience module for Drupal-based Ajax programming.

Installation
------------
Installation is as usual.  Uncompress and drop the module directory inside
'modules' or your site specific module directory.  Then enable it
from 'admin/modules' and select appropriate permissions.

Permissions
-----------
This module defines two permissions - "get node img" and "set 404 img".  The
first permission decides who can fetch images using this module and the second
one determines who can select the 404 image (see below for details).

Usage
-----
Use URLs of the following form to fetch images:

node_resource/NODE_ID/IMAGEFIELD-MACHINE-NAME /[IMAGE_NUMBER[.EXTENSION]]

Here,
    NODE_ID is an integer.
    IMAGEFIELD-MACHINE-NAME is a string.  It is the machine readable label
        specified during the creation of the Image field.  Example: field_image.
    IMAGE_NUMBER is an integer and it is optional.  It defaults to 0, which refers
        to the first image attached to the image field.
    EXTENSION is a string.  It can be anything like png, jpeg, foo, bar, etc.
        The sole reason it is here is because some modules may expect a standard
        image extension (png/jpg/jpeg/gif) for all image URLs.

Now some example URLs:
    node_resource/10/field_star_image/
    node_resource/10/field_star_image/0
    node_resource/10/field_star_image/0.jpg
    node_resource/10/field_star_image/0.foo
    node_resource/10/field_star_image/foo (This also refers to the first image!)
    node_resource/10/field_star_image/1
    node_resource/10/field_star_image/4.jpg
    node_resource/11/field_star_image/3.jpg

HTTP 404
--------
What happens when the requested image is not found?  By default an HTTP 404
status response (File not found) is sent.  But it is also possible to send a
standard placeholder image instead of 404.  This image is selected/deselected
from "admin/config/media/get_node_img_404_selection".

Testing
-------
You will probably want to use this module while Ajax programming.  In that case,
my advice is this - try to fetch the image(s) first by typing the image URL(s)
in the browser's address bar.  If you get the expected image, assume that this
module is working fine and proceed to fetch the images through Ajax calls.
OTOH, if you cannot grab the image(s) directly from the browser, then make
sure you have got the URL structure right.  If nothing else works, submit an
issue at drupal.org.

License
-------
GPL v2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
