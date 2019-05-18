# BYU Hero Module
This is the start of a module to apply the byu-hero-banner web component.
This module creates an interface within Drupal to allow content types and fields to be presented through the BYU Hero Banner component.

## About the BYU Hero Banner Component
You can read all the documentation and see all customization / layout options for the component here:
https://github.com/byuweb/byu-hero-banner

## How to Use

1. Installation will create image styles, breakpoints, a responsive image style, and a view mode.
2. Go to the content type you wish to display through a BYU Hero Banner format, and go to Manage Display. At the bottom, enable the BYU Hero Image Style 2 format.
3. Edit that display mode, select the Layout 'BYU Hero' at the bottom of the screen and save. 
4. Drag your fields appropriately into the corresponding regions. Hide all labels.

Note: We recommend you use the module manage display to allow displaying the title and other native node features inside a region in the display mode.
     https://www.drupal.org/project/manage_display
     
### Field Types 
Recommended Field Types:
* Headline - This is likely your title. We recommend you use the module manage display to allow displaying the title inside a region in the display mode.
https://www.drupal.org/project/manage_display
* Intro Text - a text field or body summary.
* Read More - a link field, allowing one link
* Image Source - a single image field allowing one image
* Video - a single link field, allowing one external link, url only. Make sure your url isn't being trimmed.
* Classes - a text field with classes separated by a space. See the classes available through the byu-hero-banner component:
https://github.com/byuweb/byu-hero-banner

## Next Steps for This Module
* Define a hero content type with the fields we recommend pre-created. These fields could then be added to other content types as well.
* Define other layouts for other hero options available through the byu component.
* Define a default view displaying 1 node of type hero
