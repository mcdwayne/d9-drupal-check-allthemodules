This module provides integration with the Snowplow event analtyics pipeleine.

The core module provides basic configuration and API integration. Features and
site functionality are provided by a set of submodules that depend upon the core
"snowplow" module. These are in the "modules" subdirectory: See their
respective README's for more details.

## Features

## Installation Notes
  * You need to have add an APP-ID or the module will use the sitename transliterated.

  * The Snowplow PHP library must exist in your libraries directory.

      - Download Composer if you don't already have it installed:
        https://getcomposer.org/download/

      - Download the latest version of the library:
        https://github.com/snowplow/snowplow-php-tracker/archive/master.zip

      - Extract the library archive to libraries/snowplow

      - Ensure the directory structure looks like this:

        - libraries/
          - snowplow/
            - src/
              - Emitters/
                - CurlEmitter.php
                - FileEmitter.php
                - SocketEmitter.php
                - SyncEmitter.php
              - Constants.php
              - Emitter.php
              - Payload.php
              - Subject.php
              - Tracker.php
            - composer.json
            - README.md

      - In the snowplow library directory, run:
        composer install

## Configuration

## Submodules

