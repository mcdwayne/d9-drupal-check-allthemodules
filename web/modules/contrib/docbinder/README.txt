DocBinder - collect attached documents & download them in a single file

INTRODUCTION

DocBinder lets your site users bundle files for download in a zip.

For example: you have a library of audio and image files associated with a topic, and
want to give users the possibility to select several files for download, then download them
in a single archive.

USAGE

There are some mandatory and optional steps to start using this module:

* Install/enable this module.

* [D7 only] (optional) Visit admin/config/media/docbinder and change the settings.

* You need to expose the "DocBinder Downloads" block via admin/structure/block
  This block provides the link for users to build the zip with their files and download it.

* Configure permissions for roles to access DocBinder downloads at admin/people/permissions

* [D7 only] (optional) Update your theme with download links to DocBinder, using theme('docbinder_download').
  This function simply wraps l(), so the parameters are similar: $html, $path, $options.
  Eg: You have a file at sites/default/files/file1.txt to make available for download:

    <?php print theme('docbinder_download', array('text' => 'Download file', 'path' => 'sites/default/files/file1.txt')); ?>

  You will probably only need this when writing your own custom file download links.


JAVASCRIPT ENHANCEMENTS [D7 only]

This module includes JavaScript to improve user experience. If JS is enabled, the user
will see an animation of the clicked element being copied to the DocBinder Downloads block,
and the download clicks will be interrupted. The block will also be updated with the
number of files for download. This JS is dependent on certain CSS classes being present,
and you may want to ensure the DocBinder Downloads block is always visible on screen, or
is fixed on screen if there are files to download.

If JS is not present or enabled, the module should continue to work as per normal, with
a message displayed to the user as each file is added to the cart.
