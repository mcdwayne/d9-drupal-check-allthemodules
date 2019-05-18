This is the Drupal 8 port for the https://drupal.org/project/royalslider project.

//@TODO update all the Readme text to the D8 version.

RoyalSlider
===============
This module provides integration with the excellent RoyalSlider library.

It contains the RoyalSlider module and an implementation for views in the Views Slideshow: RoyalSlider module.

Features of the RoyalSlider module:
 - Configuration stored in exportable "Option Sets" (Configuration Entities)
 - Field formatter for image fields
 - Full support for custom RoyalSlider Skins
 - API functions to manually include the RoyalSlider library and/or option sets and/or skins

Installation
=============
1) Create a directory within this module's js/lib folder named royalslider.
2) Locate/download the RoyalSlider plugin. http://dimsemenov.com/plugins/royal-slider/
3) Upload/install the RoyalSlider plugin: place it inside the royalslider
   directory.
4) Enable the RoyalSlider module
5) You should now see the new display format for image fields called "RoyalSlider"


Requirements
============
* RoyalSlider library

Description
===========

The RoyalSlider module will provide a new field display option for image fields: RoyalSlider.
If you select the display option, you can select which "Option Set" you want to use for that field.
You can configure Option Sets at 'admin/config/media/royalslider'.
For more information about the different options, see the documentation of RoyalSlider: http://dimsemenov.com/plugins/royal-slider/documentation/


Manually initialize example usage
===========

(function ($) {
  Drupal.behaviors.yourModuleTheme = {
    attach: function (context, settings) {

      if (myCustomConditions) {
        if (settings.royalslider) {
          if (settings.royalslider.instances) {
            for (var id in settings.royalslider.instances) {
              var $slider = $('#' + id, context),
                optionset_name = settings.royalslider.instances[id].optionset,
                optionset = settings.royalslider.optionsets[optionset_name];

              // Manually initialize.
              if (optionset.manuallyInit) {
                $slider.royalSlider(optionset);
              }
            }
          }
        }
      }

    }
  };
})(jQuery);


Authors/maintainers
===================

Original Author:

Alex Weber
http://drupal.org/user/850856

Co-maintainers:

thijsvdanker
http://drupal.org/user/234472/dashboard


Support
=======

Issues should be posted in the issue queue on drupal.org:

https://drupal.org/project/issues/royalslider