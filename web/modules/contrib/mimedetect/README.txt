MIMEDETECT DRUPAL MODULE
========================

CONTENTS OF THIS FILE
---------------------

 * Summary
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Recommended modules
 * Troubleshooting
 * Contact
 * Credits


SUMMARY
-------

MimeDetect provides a MIME detection API. Detection itself is done following
these steps:

 1. MIME detection plugins
    If a detection plugin for the given filename extension exists, it is
    executed. When MIME detection by plugins succeeds, the detected MIME type
    is returned and no more methods will be tested.

 2. Fileinfo PHP extension
    Used if enabled (in config) and such PHP extension is available in your
    system. Detection will stop here when a result different from the generic
    "application/octet-stream" is obtained.

 3. UNIX file command
    Disabled by default, it is used when present.

 4. Core MIME type guesser is used as a fallback.
    This is the default MIME type dectection in Drupal and is based only on
    a mapping between filename extensions and the corresponding MIME types.

MimeDetect also includes a submodule for file upload protection against
inconsistent filename extension with its real content.


REQUIREMENTS
------------

At least one of these two supported tools based on MIME 'magic' detection has to
be present in your system:

 1. PHP File information extension
    Commonly included in the basic PHP installation.
    see: http://php.net/manual/en/book.fileinfo.php

 2. UNIX 'file' command
    Present on most UNIX/Linux systems.
    see: https://en.wikipedia.org/wiki/File_(command)


INSTALLATION
------------

Install as usual, see https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

Module configuration is available at Manage -> Configuration -> Media
(/admin/config/media/mimedetect).

By default, only the PHP fileinfo detection engine is enabled.


USAGE
-----

MimeDetect acts as an API, other modules can make usage of it by using the
'mimedetect' service.

A simple file upload validator is included in a separate module for illustration
purposes, basic functionality and backward compatibility with Drupal 6 & 7. It
rejects any file upload which detected MIME type doesn't match the filename
extension.


RECOMMENDED MODULES
-------------------

 * File MIME (https://www.drupal.org/project/filemime):
   Provides a way to alter the core MIME type mapping to file name extensions.
   Use it to add unrecognized extensions or to alter the MIME associated with
   some particular extension.


MIME DETECTION PLUGINS
----------------------

Not all file content types can be determined by MIME 'magic' method. In such
cases a programmatic method has to be implemented as a plugin. For example, CSV
files are detected as "text/plain" by the PHP fileinfo or the file UNIX
command. MimeDetect comes with a plugin to detect CSV files by examining their
real content.


TROUBLESHOOTING
---------------

File upload validation with unrecognized file name extensions:

Drupal core maps each file extension with a MIME type. Thats the way the
"file.mime_type.guesser" service guesses the MIME type for a given file name.

Some file name extensions are not present on such map or can be mapped with a
different MIME type than the detected by MimeDetect. For example, that's the
case for portable object (".po") files. PO files are plain text files that
contain translation strings. Drupal core returns the generic
'application/octet-stream' MIME type for them, whereas 'magic' MIME detection
engines (and therefore MimeDetect) usually returns 'text/x-po' as the detected
MIME type.

This mismatch will make file upload validator to block file uploading. The
right way to solve this is by altering the Drupal core file name extension map,
adding the unrecognized MIME types or overriding existing ones. And that's
exactly what the filemime recommended module does, so you will find this module
useful to solve these scenarios.


CONTACT
-------

Current maintainers:
* Manuel Adan (manuel.adan) - https://www.drupal.org/user/516420


CREDITS
-------

Ported to D8 by:
* Manuel Adan (manuel.adan) - https://www.drupal.org/user/516420

Ported to D6 & D7 by:
* andrew morton (drewish) - https://www.drupal.org/user/34869

Created by:
* Darrel O'Pry (dopry) - https://www.drupal.org/user/22202
