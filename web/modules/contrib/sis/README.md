# Smart Imaging Styles (SIS)

## Introduction

The goal of this module is to present users with the best possible image
derivative (thumbnail, crop, size, etc.) depending on the actual client side
context.

This module acts like Drupal's responsive images formatter on steroids.

Instead of just using breakpoints, viewport width and screen multiplier (PPI)
for selecting the best image to load, this module uses the parent HTML element
width of the to be loaded images.

## Requirements

- Responsive Images

## Installation

Install this module as any other Drupal module, see the documenation on
[Drupal.org](https://www.drupal.org/docs/user_guide/en/extend-module-install.html).

## Configuration

### 1. Drupal Image Styles

Before starting to configure this module make sure you have set up your standard
Drupal image styles at `/admin/config/media/image-styles`.
At a minimum you need two image styles, but users will benifit more when you
have more sizes for different screen sizes and screen multipliers (PPI).

Make sure you create one very small image style, e.g. 10x10px, or reuse the Tiny
style shipped with this module. When loading a page with Smart Imaging Styles
the smallest image style of every such SIS image is loaded and shown initially
until the best fit of the available styles is found and loaded.
When no small image style is present there is a risk of
loading too much data and parent HTML elements may get streched, resulting in
broken layouts. For the best experience create a small image style with the same
aspect ratio as the bigger versions.

### 2. Responsive Images

Further configuration happens via the Responsive Images module at
`/admin/config/media/responsive-image-style`.

For a basic setup create a new Responsive Image style with a breakpoint group
with at least one breakpoint (you can use the `Responsive Image` group). You can
offer different image styles per breakpoint, but this is certainly not required.
An example would be serving different sized portrait images for mobile and
landscape versions for desktop devices.

Select `Enable smart imaging`.

For each breapoint you want to use, select the type:
 `Select multiple image styles and use the sizes attribute.`. Select multiple
image styles that are available for selection on the client side. Make sure to
select your tiny image style and at least one other.

Set a reasonable fallback image style for old browsers and save.

### 3. Field formatter

To use the created Smart Imaging Style select the `Responsive image` formatter
on an image field and select the just created Responsive image style.

## Thanks to

* [Synetic](https://www.drupal.org/synetic) for providing time to work on this
  module,
* [Daniël Smidt](https://www.drupal.org/u/dmsmidt), for work on the module and
  putting it on DO.
