CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Similar modules
 * Installation
 * Configuration
 * Troubleshooting
 * Roadmap
 * Notes
 * Maintainers
 
INTRODUCTION
------------

Streamy is a storage module that allows you to store and serve files 
from different locations such as AWS, Dropbox, a CDN or a different folder 
on your local server.

Streamy works as a low-level Stream Wrapper leveraging on the powerful **flysystem** 
library, its settings can be also exported as configuration files. Optionally they can be
automatically loaded depending on the environment via the ##environmental_config## module.

**Key features**

- Mirroring the Drupal storage
- Fallback copy of a file from slave to master if missing
- CDN support
- Exportable configuration
- Easily extendable

### Mirroring the Drupal storage

The main purpose of Streamy is to maintain a mirror of a Drupal storage.

You can configure Streamy to have a local copy of your files as primary stream,
then AWS/Dropbox/Local as secondary stream.

On file addition/deletion the action will be performed on your local file storage
and mirrored to your secondary storage.
You can then chose if serving the file from the primary or secondary storage.

### Use case

**In a scalable environments with ephemeral storage**

If your site can be served via different containers through a Load Balancer,
Streamy can be set to use as primary stream the local storage and as a secondary 
stream a cloud location or a shared file system.

By enabling a fallback copy, Streamy will copy on request the file
from the secondary storage to the primary one in order to always serve
it from the local file system.

This becomes handy in case you don't have a persistent storage and your container gets 
destroyed. Your files are kept safe in the slave stream and copied back on request.

CDN support is also available.

### Available plugins and extensibility

- Local storage
  - Enables the use of the local storage
- AWS S3 storage
  - Enables the use of an S3 bucket as storage
- AWS CDN
  - Enables the use of an AWS CDN to serve your files
- Dropbox storage
  - Enables the use of Dropbox as storage (low performances)

Streamy can be further extended with any kind of custom plugin in order to use 
your favorite storage system.

REQUIREMENTS
------------

* Composer

SIMILAR MODULES
---------------

* flysystem
* filebrowser
* storage_api

INSTALLATION
------------

Install the module via *composer*.

Streamy comes out with two default stream wrappers out of the box:

- streamy://
  - Used to manage public files
- streamypvt://
  - Used to manage private files

CONFIGURATION
-------------

For a simple configuration, let's assume you need a public stream wrapper to store 
files on your local filesystem (master) and a backup copy on an AWS S3 bucket (slave).

### Configuring the Master stream

Go to the URL *admin/config/media/file-system/streamy/streams/local*, in the 
*Configuration for Master* under the *Streamy* wrapper settings, set the file path 
where to store your files locally, the path is relative to the local Drupal installation 
starting from *docroot*.

To avoid incurring in permission issues make sure to use a sub-folder of 
*sites/default/files/*, in this example let's use *sites/default/files/streamymaster*.

### Configuring the Slave stream

Go to the URL *admin/config/media/file-system/streamy/streams/awsv3*, in the 
*Configuration for Slave* under the *Streamy* wrapper settings, insert your
AWS details.

Note: make sure your AWS S3 bucket has correct read/write permissions!

### Enabling the Streamy stream

Go to the URL *admin/config/media/file-system/streamy* and under the *Streamy* 
wrapper settings, select *Local* as *Master Stream* and *Aws S3* as
*Slave Stream*.

Make sure to tick the checkbox *Enable Stream* in order to start using it.

On settings save, if everything is correct you will get a successful message.

### Use of the Streamy stream

Once the procedure above has been completed, we are ready to test that everything 
works as expected by creating for instance, a file field, then configure in the *Field Settings*
, *Upload destination* selecting *Streamy: Public Wrapper*.

By uploading a file through this field, you should be able to see the same file both in
*sites/default/files/streamymaster* folder and in your S3 bucket.

### Advanced configuration

In case you need further public or private stream wrappers because the default ones
are not enough for your needs, you have two options to register a new one: See developers section.

ROADMAP
-------

In order to become a stable release, Streamy needs to be tested on different systems
and for different user needs to become a more complete and useful module covering the 
majority of Drupal usages.

We kindly ask the community to report any issue/enhancement to the issue queue.

A good UnitTest coverage is also needed, we encourage the community to help us
achieve this goal.

NOTES
-----

This module is inspired to the Storage API module for D7 and flysystem for D8.

TROUBLESHOOTING
---------------

- Cannot save the *Local Stream* settings
  - Make sure the folder path you entered has the correct permissions set
- Cannot save the *AWS Stream* settings
  - Make sure the S3 credentials are right and that the S3 Bucket you entered has the correct permissions set
- Fallback copy never takes place
  - Make sure the checkbox is enabled for your stream wrapper, then check that your CRON job is set to run once in a while

MAINTAINERS
-----------

This module is sponsored by PwC's Experience Center - https://www.drupal.org/pwcs-experience-center

Developed by:

 * Alessio De Francesco (aless_io) - https://drupal.org/u/aless_io - https://github.com/aless-io
 
DEVELOPERS SECTION
------------------

Streamy can be easily extended via plugin to support:

- New storage systems
- New CDN systems

### Create a new storage plugin (StreamyStream)

Should return a Flysystem mount

### Create a new storage plugin (StreamyCDN)

Should return a valid CDN url

### Add a stream wrapper in settings.php

In your *settings.php* file just declare an array as follows:

```
$settings['streamy']['mycustomscheme'] = [
  'name'        => 'mycustomscheme: My test Wrapper',
  'description' => 'Stream Wrapper test for tutorial',
  'private'     => TRUE,
];
```

As you can notice the array key `mycustomscheme` is the schema name *(mycustomscheme://)*
while name and description are useful to easily target your stream wrapper in the
backend.

The `private` key is self explanatory and determines whether this stream
wrapper has to be treated as a public or private one.

### Programmatically declare a stream wrapper (discouraged)

In order to have a stream wrapper `mycustomscheme://` utilising the power of Streamy,
add in *yourmodule.services.yml* file the following service declaration:

```
  yourmodule.mycustomscheme.stream_wrapper:
    class: \Drupal\streamy\StreamWrapper\DrupalFlySystemStreamWrapper
    tags:
     - { name: stream_wrapper, scheme: mycustomscheme }
```

Then make sure that the configuration *streamy.schemes.yml* contains information regarding
your stream wrapper, have a look at *streamy.schemes.yml* for further info.

Important: tweak or remove the default stream wrappers *streamy* and *streamypvt* is highly 
discouraged, add your stream wrappers in your *settings.php* file instead.
