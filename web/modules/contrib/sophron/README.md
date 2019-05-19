# Sophron

Sophron of Syracuse (Greek: _Σώφρων ὁ Συρακούσιος_, fl. 430 BC) was a _writer
of mimes_ ([Wikipedia](https://en.wikipedia.org/wiki/Sophron)).

Sophron of Drupal is a module to enhance MIME type management, based on the
[FileEye/MimeMap](https://github.com/FileEye/MimeMap) library.

## Features:

* Enhances Drupal's MIME type detection based on file extension to recognise
  1200+ MIME types from 1600+ file extensions (vs Drupal's 360 MIME types and
  475 file extensions).
* Provides an extensive MIME type management API through [FileEye/MimeMap](https://github.com/FileEye/MimeMap).
* Optionally replaces Drupal's core MIME type extension-based guesser.

## Requirements

* The module **must** be [installed using Composer](https://www.drupal.org/node/2718229).
* Drupal 8.4.0 or higher.
* [FileEye/MimeMap](https://github.com/FileEye/MimeMap) 1.1.1 or higher.

## Installation

* Install the required module packages with Composer. From the Drupal
  installation root directory, type
```
  $ composer require drupal/sophron:^1
```
  This will download both the module and any dependency.

* Enable the module. Navigate to _Manage > Extend_. Check the box next to the
  module and then click the 'Install' button at the bottom.

## Configuration

* Go to _Administration » Configuration » System » MIME Types_.

* ...

## Mapping commands

...

## Updating Sophron map

Sophron uses a MIME type map that is built from [FileEye/MimeMap](https://github.com/FileEye/MimeMap)
default map, with the adjustments needed to make it fully compatible with
Drupal's core MIME type mapping. This map is in the stored in the
```Drupal\sophron\Map\DrupalMap``` PHP class.

MimeMap provides an utility to update the code of the PHP map classes. Sophron's
map class can be updated starting from upstream's default one by running

```
$ cd [project_directory]
$ vendor/bin/fileeye-mimemap update --class=\\Drupal\\sophron\\Map\\DrupalMap --script=modules/contrib/sophron/resources/drupal_map_build.yml
```

The ```drupal_map_build.yml``` script instructs the utility to start the map
update from the ```FileEye\MimeMap\Map\DefaultMap``` class with the command

```
# We use the default MimeMap map as a starting point.
-
    - 'Starting from MimeMap default map'
    - selectBaseMap
    - [\FileEye\MimeMap\Map\DefaultMap]
```

then run the adjustments required to make the map compatible with Drupal core
with the command

```
# Then apply Drupal specific overrides.
-
    - 'Applying Drupal overrides'
    - applyOverrides
    -
        -
            - [addTypeExtensionMapping, [application/atomserv+xml, atomsrv]]
            - [addTypeExtensionMapping, [application/dsptype, tsp]]
            - [addTypeExtensionMapping, [application/hta, hta]]
            - ...
```

## Creating custom MIME type to extension maps

The ```fileeye-mimemap update``` utility can also be used to add new maps by
copy/pasting an existing class, renaming it, and running the utility with a
custom script that makes the required changes. The custom map can then be set
as the one to be used by Sophron's in the module configuration.
