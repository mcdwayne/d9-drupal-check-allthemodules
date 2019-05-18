INTRODUCTION
------------

Purpose of this project is to make an amazon affiliate module which is simple and less complicated.
This module automatically imports amazon products to the drupal with keyword search. Please see 'How to use'
area for more information.

INSTALLATION
------------

Install as usual, see
Download the module and upload to the modules folder and make it active.

How to use
------------

There are three steps to follow.

1- Go to amazon affiliate and grab your associate keys.
2- Create a content type and create fields.
3- Create these field as it is.(field_asin,field_url,field_price,field_large_image,field_medium_image,field_small_image)
4- Create the taxonomy of 'Keywords' and add terms. This module selects a random term and search in amazon
catalogue and imports the products into drupal.

That's it.

Now run cron and it will create nodes.



MAINTAINERS
-----------

Current maintainers:

 * Naseem Sarwar (http://www.naseemsarwar.me)