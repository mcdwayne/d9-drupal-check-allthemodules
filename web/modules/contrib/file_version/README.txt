This module add simple token in files URLs based on file modified time. This is
very useful for caching files in CDNs and differentiate if the image changes
like a new resource.

Modules that crop images need this for integrate with CDNs. Because the image
file name is the same but the image could be different.

You can configure:
 -  Add token for image styles URLs
 -  Add token for all files URLs
 -  Define whitelist and blacklist of file extensions

HOW TO USE:
Go to /admin/config/media/file_version and check if you want use File Version
for "All Files" or only "Image Styles".
 - If you choose "All Files": All files that internal use file_create_url()
   will have file version token.
 - If you choose "Image Styles": All image styles follow system pattern
   '/styles/' will have file version token (included external file system
   integration like s3fs module).

ADVANCED USE:
You can define a comma separated list to exclude or force files with a
specified extension.
  - Blacklist: a extensions list to exclude, you must have checked File Version
    for "All Files" or for "Image Styles".
  - Whitelist: a extensions list to force include, is only thought for use with
    "Image Styles" checked and add for example pdf extension to include these
    files, or for use with none checkbox checked to choose exactly your
    file extensions.

Example file URL without File Version:
http://example.com/sites/default/files/2017-05/example.png

Example file URL with File Version:
http://example.com/sites/default/files/2017-05/example.png?fv=v-malxjm

IMPORTANT:
 -  The module will generate absolute URLs for avoid encoding conflicts
    with GET query parameters.
 -  The module use hook_file_url_alter(), so it works with core file/image
    workflow and all the files using file_create_url().
