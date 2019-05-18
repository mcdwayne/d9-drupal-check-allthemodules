README
------

The MediaWiki API module provides an input filter which allows the conversion of content marked up using MediaWiki syntax to html
for display on your drupal site, by using the "parse" feature of the MediaWiki API.

Drupal configuration and supported syntax
-----------------------------------------

- italic, bold text, lists, sections and hr lines supported

- internal links are converted to href by title, i.e. [[Node 1]] converts to aliased path or /node/[nid] of node with title "Node 1".
If you change any titles, you will get broken links, because of Drupal nodes not re-rendered, so it's your responsibility.

- you need to remove any prefix for file system paths to made file system plain. For example, here
/admin/structure/types/manage/article/fields/node.article.field_image you need to make "File directory" field empty.

- if you use public images on site, you can use [[File:filename.png]] as a reference to the image. By default, no href is created for
the image, but if you enter $wgArticlePath setting (for example /w/$1) in the filter, your images will have href on the MediaWiki page
with corresponding image. As side effect, you will get workable links to MediaWiki pages, i.e. [[MediaWiki Title]] will be linked to
MediaWiki page with same title, IF you don't have a node with the same title (Drupal pages take precedence).

- to set up private files, you need to (as example):

1. add $settings['file_private_path'] = '../private'; to your settings.php
2. create 'private' dir on the same level where web root
3. set permission to write for www-data user
4. put appropriate .htaccess file to 'private' directory (see status page)
5. drush cr, check status page
6. enable private default file download method here /admin/config/media/file-system
7. reconfigure field storage /admin/structure/types/manage/article/fields/node.article.field_image/storage to use private files
8. move any existing files to private storage, and update attachment DB entries. This is a big task for big sites, so choose your
storage system before running!

- you also can use [[File:filename.png|200px]] to set up image width to 200px, [[File:filename.png|link=]] to remove href,
and two options together: [[File:filename.png|200px|link=]].

MediaWiki configuration
-----------------------

To make it work, you need to set up your own MediWiki installation on another domain. For example, if your Drupal site have domain
example.com, you can use sub-domain wiki.example.com for your MediaWiki. In LocalSettings.php enter this:

Common settings:

# Set in Apache .conf 'Alias /w [absolute path to your wiki installation]/index.php' to work
$wgArticlePath = '/w/$1';
$wgUseSharedUploads = true;
$wgHashedSharedUploadDirectory = false;
$wgCacheSharedUploads = false;
$wgCapitalLinkOverrides[NS_FILE] = false;
$wgUseImageResize = true;

For public Drupal files:

$wgSharedUploadDirectory = [absolute path to your Drupal public files directory];
$wgSharedUploadPath = [URL to your public files directory from browser];

For private Drupal files:

$wgSharedUploadDirectory = [absolute path to your Drupal private files directory];
$wgSharedUploadPath = [URL to your private files directory from browser];

KNOWN LIMITATIONS
-----------------

- avoid spaces in filenames. Use transliteration modules like https://www.drupal.org/project/transliterate_filenames.

BUGS
----

Please report any bugs using the bug tracker at http://drupal.org/project/issues/mediawiki_api

(C) Andrew Answer 2019
