# REST Block Layout

The essence of decoupled Drupal.

## Introduction
REST Block Layout provides a single endpoint to retrieve the block layout for a
specified path. It allows Drupal to manage paths (and aliases) by allowing a
decoupled site to query Drupal and retrieve not only what a path represents, but
the layout of the page at a specific path.

## Purpose
This module allows a developer to build a decoupled site while maintaining
Drupal's Block Layout configuration and URL Aliases.

## Installation
Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.

## Usage:
The endpoint works in any format available and accepts a single "path" argument
which is a URL encoded path that starts with a forward slash.

## Example
https://example.com/block-layout?_format=json&path=%2Fnode%2F123

## Requirements
* [Block](https://www.drupal.org/docs/7/working-with-blocks/working-with-blocks-content-in-regions)
* [REST](https://www.drupal.org/documentation/modules/rest)

## Suggestions
* [REST UI](https://www.drupal.org/project/restui)

## Setup
Enable the "Black Layout" REST Endpoint.

## Maintainers
Current maintainers:
* David Barratt ([davidwbarratt](https://www.drupal.org/u/davidwbarratt))

## Sponsors
Current sponsors:
* [Golf Channel](https://www.drupal.org/node/2374873)
