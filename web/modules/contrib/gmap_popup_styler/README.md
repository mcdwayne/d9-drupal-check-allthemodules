# Gmap Popup Styler module (for Drupal 8) #
-------------------------------
By default, you cannot change the styling/look and feel of a popup when clicking of a marker on a google map.
With this module, you can.

It works by adding classes that you can use to custom style your popups:
- .gmap-popup: a wrapper around the original popup that will include the old and new popup
  - .gmap-popup__original-bg: original default google maps popup background, hidden by default now
  - .gmap-popup__content: use this to style your popups now. There is a default css styling you can override in your own
theme.
  - gmap-popup__close-button: to enable the styling of the close button of the popup.

REQUIREMENTS
-------------
For this module to work, there are a few requirements:
- Add a class 'js-gmap' on your google maps element.
- That element needs to have a 'is-loaded' class when the google map is fully loaded. The data_attribute_gmap module does this by default.

INSTALLATION
------------
- Enable module
- Add the library on the twig file where needed (where your google map element is):
  ```
  {{ attach_library('gmap_popup_styler/gmap-popup-styler') }}
  ```