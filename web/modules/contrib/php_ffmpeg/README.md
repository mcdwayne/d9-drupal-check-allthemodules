## PHP-FFmpeg

This project is an API module that integrate with the [PHP FFmpeg library](admin/config/development/php-ffmpeg).
This module doesn't do anything by itself and is usually extended by other projects that do something useful with
FFmpeg.

[![Build Status](https://travis-ci.org/FloeDesignTechnologies/drupal-php-ffmpeg.svg?branch=8.x-1.x)](https://travis-ci.org/FloeDesignTechnologies/drupal-php-ffmpeg)

## Installation

Follow the standard module installation guide (https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules)
to install PHP FFmpeg. This module has a composer dependency (php-ffmpeg/php-ffmpeg), see https://www.drupal.org/node/2404989 for more information.

After installation, visit the setting page at `admin/config/development/php-ffmpeg` to set the path to

## Usage

The module provides an administrative UI for the various configuration options exposed by PHP FFmpeg library. To
instantiate the PHP FFmpeg classes populated with the configuration options, call `$ffmpeg = Drupal::service('php_ffmpeg');` or
`$ffprobe = Drupal::service('php_ffmpeg_probe');` in your module. Refer to the PHP FFmpeg library's documentation for details on how to use
the library.

Adapters are provided so the PHP FFmpeg library will use Drupal for caching. The logger adapter that was part of Drupal 7
was removed, as Drupal 8 natively supports the PSR LoggerInterface.

The PHP FFMpeg library uses `ffmpeg` and `ffprobe` CLI executable, all its method accepting file paths expect paths
usable are arguments for these executables. When using the library to process Drupal managed files, developer have to ensure
usage of local files paths or URL supported by `ffmpeg` and `ffprobe` as sources, and local file paths as destinations.  
