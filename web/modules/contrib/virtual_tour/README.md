## Overview

[Virtual Tour](https://pannellum.org/documentation/overview/) is built using
WebGL and JavaScript, with a sprinkling of HTML5 and CSS3.
It can run standalone or can be embedded using either an `<iframe>` or a
JavaScript API. The standalone method, which is used for `<iframe>` embedding,
is the easiest and simplest to use, but the JavaScript API is more powerful and
provides tighter integration. Internally, the standalone viewer parses URL
parameters to build a JSON-based configuration and then instantiates the viewer
using the JavaScript API. The standalone viewer accepts a subset of
configuration parameters as URL parameters;
the rest of the parameters can be set using a JSON configuration file specified
using the special config URL parameter.

## Panorama formats

Panoramic images can be provided in either equirectangular, cube map, or
multiresolution formats. This module provides an field formatter for
Equirectangular virtual tour effect (panorama effect). An image style is
selected for the default display image, and an additional style is selected
 to be used as the Pannellum image.


## Requirements

This module depends on the core Image module and Libraries API being enabled.
The Pannellum jQuery plugin is used for the Panoram effects.


## Install

Run `npm install` in Virtual Tour module.


## Configuration

To configure the Image Pannellum display, go to Administration > Structure >
Content types and select the content type you would want to use. If you
do not already have an Image field defined, add one by going to the Manage
Fields tab. After you have an Image field, go to the Manage Display tab. Change
the Format for your Image field to Pannellum Image. To change which styles are
displayed for the displayed image, select the desired image styles, and click
Update.
