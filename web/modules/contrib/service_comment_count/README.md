# Service Comment Count

The Service Comment Count module makes it possible to fetch and store
the comment numbers from comment services like Disqus or Facebook Comments.

The common comment integrations are limited to show the number of comments
for a specific content entity via javascript-based requests. Scenarios like
"Most comment articles" view require the comment numbers to be stored
physically in the Drupal database. That's what the Service Comment Module
can be used for.

This module is an API module and does not provide any integration with a
comment service. Please use one of the available Service Comment Count
integrations or write a custom plugin for your favorite comment service.

Available comment services:
* [Service Comment Count Disqus]
(https://www.drupal.org/project/service_comment_count_disqus)
* [Service Comment Count Facebook]
(https://www.drupal.org/project/service_comment_count_facebook)

## Features

* Fetches and store the comment number

## Installation

1. Download with composer.
2. Enable the module.

## Configuration
1. Configure the module at `/admin/structure/services/service-comment-count`.

## Requirements

* A composer-based workflow (the integration modules will most likely depend on
other libraries/SDKs
