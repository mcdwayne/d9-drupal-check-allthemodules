Views Files Downloader provides an option to download all the files attached to a view and a node. It provides a dedicated link for every view to download files. Downloaded files are added to a Zip file , which can be used by site visitor.

INSTALLATION
=============

1. After downloading place the extacted archive contents in /modules folder.
2. Download and install libraries module first and then only install views file downloader.
3. Download PCLZip library using composer composer require pclzip/pclzip.
4. Make sure that machine name of the field containing file is "field_file".
5. Now create views and nodes with files field.
6. You can download all the files attached to a view by adding "download_view" to URL.

How to Work with Node Downloads :
_______________________________

1. Create field_file (machine name) in your content type.
2. Once now just add string "download_node" just before "/node/xx" eg  http://example.com/node/68  just make it http://example.com/download_node/node/68 and return
3. The file in the corresponding node will be compressed and downloaded.

How to Work with View Downloads :
_______________________________

1. Create field_file (machine name) in your content type and add this field in your view.
2. The path for the view must be same as the view Machine Name eg if the  Machine name  for your view "myview" is "my_cool_view" then the path must be same as the machine name  i.e /my_cool_view.
3. Once done now just add string "download_view" just before "/my_cool_view" eg  http://example.com/my_cool_view  just make it http://example.com/download_view/my_cool_view and return
4. The file in the corresponding view will be compressed and downloded.



ORIGINAL AUTHOR
===============
Module written by Gaurav Kapoor .
Gaurav Kapoor https://www.drupal.org/u/gauravkapoor


MAINTAINER
=============
2017: Gaurav Kapoor https://www.drupal.org/u/gauravkapoor
2018: Tanuj Sharma https://www.drupal.org/u/badmetevils
