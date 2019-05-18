Convert Media Tags to Markup for Drupal 8
=====

[![CircleCI](https://circleci.com/gh/dcycle/convert_media_tags_to_markup.svg?style=svg)](https://circleci.com/gh/dcycle/convert_media_tags_to_markup)

A Drupal 8 module which fixes legacy code such as:

    [[{"type":"media","view_mode":"media_large","fid":"403","attributes":{"alt":"","class":"media-image","height":"187","style":"display: block; margin-left: auto; margin-right: auto;","typeof":"foaf:Image","width":"480"}}]]

In this case, file ID 403 needs to exist.

This code is probably the result of an import from a Drupal 7 site which used [the Media module](https://drupal.org/project/media), itself defining a filter called "Convert Media Tags to Markup".

Usage
-----

You can install this module as any other Drupal 8 module and it should work once you add the "Convert Legacy Media Tags to Markup" text filter to your text formats.

If you want to evaluate this module before using it:

* Install Docker on your machine.
* Download this repo and navigate to its root in the command line.
* Type `./scripts/deploy.sh`
* After a few minutes, the script should give you a login link to a local development environment. Go there.
* In /node/add/article, create an article with an image, this will be your file entity.
* Go to /admin/content/files and hover over "1 place"; you will see something like "/admin/content/files/usage/1". In this example 1 is your file id, or fid. Keep note of the fid.
* Go to /node/add/page and enter some sample code with fid 1, this is meant to simulate what you would get after an import from Drupal 7: `[[{"type":"media","view_mode":"media_large","fid":"1","attributes":{"alt":"","class":"media-image","height":"187","style":"display: block; margin-left: auto; margin-right: auto;","typeof":"foaf:Image","width":"480"}}]]`
* Use the text format Full HTML.
* Save; we will assume this is /node/2.
* Go to /admin/config/content/formats/manage/full_html.
* Check "Convert Legacy Media Tags to Markup" and Save the Full HTML text format.
* Go back to /node/2 and you should see your image.

File objects must exist
-----

When you import files from Drupal 7, these files will have their own entries in /admin/content/files. Many will be said to exist in "0 places" once you import them; these are probably the ones which are embedded using the Media module in D7.

Note that [Files that have no remaining usages are no longer deleted by default](https://www.drupal.org/node/2891902), so this should not be a problem.

Permanently change code to images
-----

Some users might find the code daunting, so you might want to change all such code to actual images not in the text filter, but in the database itself. To do so:

* **Back up your database**;
* In `/admin/structure/types/manage/YOUR_CONTENT_TYPE`'s publishing options, check "Create new revision", this will allow you to revert changes in case something goes wrong;
* Run the following in _simulation mode_ (this is an example to change only pages, but you can apply it to any entity type and bundle):

    drush ev "\Drupal\convert_media_tags_to_markup\ConvertMediaTagsToMarkup\DbReplacer::instance()->replaceAll('node', 'page', TRUE)";

* Run the following in _live mode_ (this is an example to change only pages, but you can apply it to any entity type and bundle):

    drush ev "\Drupal\convert_media_tags_to_markup\ConvertMediaTagsToMarkup\DbReplacer::instance()->replaceAll('node', 'page', FALSE)";

* Make sure everything works; if not revert your database or revert to previous revisions of specific nodes.

Development
-----

The code is available on [GitHub](https://github.com/dcycle/convert_media_tags_to_markup) and [Drupal.org](https://www.drupal.org/project/convert_media_tags_to_markup).

Automated testing is on [CircleCI](https://circleci.com/gh/dcycle/convert_media_tags_to_markup).

Install Docker and run `./scripts/deploy.sh`.

Resources
-----

* See https://drupal.stackexchange.com/questions/146577
* [Converting Drupal 7 Media tags during a Drupal 8 migration, By John Ouellet, March 27, 2017, Kalamuna](https://blog.kalamuna.com/news/converting-drupal-7-media-tags-during-a-drupal-8-migration) provides an approach which converts during the migration. That approach is valid, but it is not compatible with the approach in this module; you'll have to choose one or the other.
