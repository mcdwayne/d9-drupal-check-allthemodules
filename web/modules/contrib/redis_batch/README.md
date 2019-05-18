CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers
 
INTRODUCTION
------------

The Redis Batch module provides backend storage for drupal batch.
   
REQUIREMENTS
------------

This module requires the following modules:

 * [Redis](https://drupal.org/project/redis)

INSTALLATION
------------
 
Install as you would normally install a contributed Drupal module.
Visit: [https://www.drupal.org/docs/user_guide/en/extend-module-install.html]()
for further information.

CONFIGURATION
-------------

Override the batch storage backend service in `services.yml` by adding alias.
```
services:
  batch.storage:
    alias: batch.storage.phpredis
```

MAINTAINERS
-----------

Current maintainers:
 * [Luhur Abdi Rizal (el7cosmos)](https://www.drupal.org/u/el7cosmos)

This project has been sponsored by:
 * Sepulsa Teknologi Indonesia
