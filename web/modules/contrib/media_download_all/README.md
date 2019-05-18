### Description

The Media Download All module, it is a media entity reference field's display
formatter which allows you to download all files associated with one click
and which is compressed into a **.zip** file.

It inherited the default **Thumbnail** formatter and appended a download link
at the bottom.

It keeps security in mind by using Drupal's private file system to store those
temp .zip files. Using the public file system may result in private files being
leaked.

As per @Berdir's suggestion https://drupal.stackexchange.com/a/228260
stop using file entity, go for media if you also agree.

### Features

Working with:
 
 - All types of entities, not only node
 - Core Media Entity / Core Media module
 - Private file system
 
### Similar projects

- [Download all files](https://www.drupal.org/project/download_all_files)

Currently, it works with the node which contains the entity reference filed(s) of file
entity and does not work well with the non-English file name and used the public
file system to store the temp .zip files. 

### Sponsorship

This module is sponsored by:

- [InterGreat.com](http://www.intergreat.com) InterGreat develops products for international students. Visit the website to find out more.
