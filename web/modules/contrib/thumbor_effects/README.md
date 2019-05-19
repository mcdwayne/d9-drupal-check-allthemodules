# Thumbor Drupal module

## Introduction

Thumbor is a smart imaging service. It enables on-demand crop, resizing and
flipping of images. It features a very smart detection of important points in
the image for better cropping and resizing, using state-of-the-art face and
feature detection algorithms.

Thumbor Effects is a Drupal module to create image styles with Thumbor effects.
To use, you will need to enter a Thumbor Server and Thumbor key.

[Read more about Thumbor](http://thumbor.org)

## Requirements

- GD imagetoolkit
- Responsive Images

## Installation

Install this module as any other Drupal module, see the documenation on
[Drupal.org](https://www.drupal.org/docs/user_guide/en/extend-module-install.html).

## Configuration

Go to `/admin/config/media/thumbor-effects` and set the `Thumbor server URL`.
Then set your `Security key` or enable `Use unsafe URL's`. Warning! Using unsafe
URL's may open DDOS possibilities on your server.

Drupal tries to communicate with Thumbor via the server side by default. So the
Thumbor URL needs to be reachable by your webserver. In case of a Docker setup
you may need to use the internal networking name.

You may want to serve images directly from Thumbor instead of Drupal (different
domain) for performance reasons. Know that Drupal will also always request and
create it's own version of the image (via Thumbor) on the first request to the
image style. This is needed for getting the actual image dimensions.
Optionally you can use a different Thumbor URL for the client side (this again
helps in Docker setups).

It is wise move to combine this module with a cache warming strategy.

## Limitations

Currently Thumbor Effects only works for public images, so the private scheme
is not supported.

Drupal (CDN) modules that implement hook_file_url_alter() may not work as
expected when serving images directly from Thumbor.

## Thanks to

* [Synetic](https://www.drupal.org/synetic) for providing time to work on the
  Drupal 8 version of the module,
* [Daniël Smidt](https://www.drupal.org/u/dmsmidt), for creating a working D8
  port.
* [Vanessa Martins](https://www.drupal.org/u/vmartins), for creating the initial
  Drupal 7 version.
* [Hebert](https://www.drupal.org/u/hebertjulio), for improving the D7 version.
