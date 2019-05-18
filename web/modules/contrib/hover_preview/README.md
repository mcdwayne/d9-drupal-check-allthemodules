# Hover Preview

This module provides a new series of ImageCache formatters. The overall goal is to enable the ability to provide a hover state of an image when a mouse rolls over it. This is similar to functionality you would find on many shopping websites.

The implementation of this module goes a bit further than just a javascript hover. It allows for hovering from any defined ImageCache preset to any other ImageCache preset. Since this is provided as a series of formatters, it is supported both in views and at the theme output layer.

The easiest way to see it in action is to click on the demonstration link. It's the javascript effect that hovers the thumbnail pictures to a larger picture. The thumbnail is one ImageCache preset and the larger hovered image is another preset. The Drupal 7 version of the module allows use of either imgPreview, or a simple image replacement.

D6 original module development by Thinkleft, maintained by Go Chic or Go Home.  
D7 module development by Rob Loach and Acquia.  
D8 module development by Andre Baumeier  

# Changes D8-Version

Fixed for D8-Port: No image show when set Hover preview style on NO original image.  
https://www.drupal.org/node/2271591