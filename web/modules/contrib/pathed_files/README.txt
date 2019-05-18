== Introduction ==

The Pathed Files module allows content editors to manage miscellaneous
files that can be accessible from any path in the website. For example, if you
need a file at www.example.com/verify.xml, instead of uploading the verify.xml
file to your site root, you can create a "pathed file" in via the Drupal CMS.

To create a file, you define its URL path, a label, and the file's contents.

== Installation ==

Install the module as normal and configure permissions.

== Configuration ==

Go to the Pathed Files homepage: admin/structure/pathed-files to manage all the
pathed files on your site.

== Advanced ==
The file's content-type HTTP header is determined from the URL path's extension.
For example, if the URL path ends with ".html" or ".htm", the content type will
be "text/html".
