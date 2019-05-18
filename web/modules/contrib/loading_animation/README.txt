README.txt
----------
Loading animation module shows a loading layer on page loading events.
These may be triggered by form submission or clicking a hyperlink.

Hyperlinks may be excluded from trigger by adding a ".noLoadingAnimation" class.
Further "javascript" and "#" hyperlinks are automatically excluded.
Loading and animation may be aborted by pressing "ESC".

There are settings for all these three type of triggers:
- Form submit
- Hyperlink Click

JavaScript API
------------
- Drupal.behaviors.loading_animation.LoadingAnimation.show()
- Drupal.behaviors.loading_animation.LoadingAnimation.show()

DEPENDENCIES
------------
- none -

INSTALLATION
------------
1. Download and enable this module.
2. Edit the settings ("admin/config/user-interface/loading_animation").
