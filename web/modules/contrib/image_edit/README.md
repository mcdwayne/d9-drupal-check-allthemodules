This module makes it possible to edit images on the website, with an overlay editing interface, and then save it.
It is based on CamanJS, and logged in users can adjust hue, contrast, vibrance, sepia, and apply several predefined filters on the image.

Currently it only works for img tags, but we are working on it to make it available for picture elements as well.

Install: 
1) Download this module
2) Download camanJS (http://camanjs.com/) library and place the minified js file (located in /dist/caman.full.min.js) into the libraries folder. So the final ath of the js file will be '/libraries/caman.full.min.js'. The module was built on version '4.1.1' of camanJS. 
3) Install the module the usual way

Usage:
1) Install the module as described above
2) Place an 'Image-edit block' anywhere on your page
3) Grant 'Use Image-Editor to edit images' permission to users who should be able to edit images. 

Part of the frontend-code is based on 
the demo of Monty Shokeen. 
https://www.sitepoint.com/manipulating-images-web-pages-camanjs