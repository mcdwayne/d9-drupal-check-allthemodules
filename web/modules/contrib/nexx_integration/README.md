## About Nexx integration

The nexx integration module integrates videos uploaded to nexx.tv
(http://www.nexx.tv/) video CMS - called Omnnia - into drupal. If you connect
nexx.tv and drupal with this module, Videos updated to nexx.tv will
automatically be created on your drupal site as media entites. If you render
those entitites on your article, a video player with this will be shown. 

To be able to use the nexx module, you will first have to register at
http://www.nexx.tv/thunder first. The registration is for free and you can
stream up to 100.000 videos per month for free. You can buy more streams per
month if needed.

# Installation
After you registered at nexx.tv/thunder and activated the module, you will
need the domain ID and the API key (THOR), that you have to configure on the
admin/config/media/nexx page in your installation. On this page you can also
create new token wich has to be provided to Omnnia
(https://omnia.nexx.cloud/domains) as the notification endpoint.
After installation of the module a new media entity provider is created called
"Nexx Video" you can add more fileds to it and map available metadata to those
fields on the media bundles settings page
(admin/structure/media/manage/nexx_video) but you can use the module without
additional mappings of data from Omnia to the video entity. 
Interesting mappings are:

* Description field: This will contain a description, that was given to the
  video in Omnnia.
* Channel-, actor- and tag taxonomy mappings: Three different kinds of taxonomy
  fields can be mapped. Channel is used for a general categorisation of the
  video, actor can be used for people that are presenred in the video and tags
  can be arbritary tagging. If You provide this kind of mapping, every tag of
  the mapped vocabulary will be exported to Omnia and can be connected in Omnia
  to the video. To export every existing term of those vocabuklaries to Omnia
  at once, you can use a provided drush command (see below)
* Teaser image: map a media image bundle that can be used as preview image.

# Drush integration
To export existing taxonomy terms to Omnia use the following drush command:

* drush nexx-export-taxonomy vocabulary name

When the given taxonomy vocabulary is mapped to an Omnia taxonomy, then all
terms of this vocabulary will be pushed to Omnia.

Delete and unpublish old videos which should be unpublished or deleted with
this command:

* drush nexx-perform-unpublish-delete

Unpublish and delete actions are implemented into module but it does not
handle old existing videos, to check them and unpublish and delete proper one
please use this command, there is no update hook for it.
