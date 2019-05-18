Drupal Real Estate (DRE) is a solution for the creation of a real estate and
property classifieds sites.

Note: the module is under active development.

INSTALLATION

  Run from drupal root:

    composer require "drupal/real_estate 3.*"
   
    or if installing separatelly:
   
    composer require "drupal/address ~1.0"
    composer require "troydavisson/phrets 2.*"
    composer require "drupal/geolocation ~2.0"
  
  Instead of using a Flex Slider module, can be used any other slider module.

  Instead of using the DRE GMap module, can be used more complex and
  powerful solution. Addressfield address can be automatically Geocoded into
  Geofield points for display on Openlayers Maps.


FUTURE DEVELOPMENT

  MLS integration
  Rental
