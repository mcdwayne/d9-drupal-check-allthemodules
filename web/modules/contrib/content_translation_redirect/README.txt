Content Translation Redirect
-------
This module will be useful if you need to redirect users from pages of non-existent
translations of the content entity to a page with the original language.

It is important that the user will be redirected only if the entity translation URL
is different from the entity URL in the original language. Also, do not forget that
the entity must be translatable.


Requirements
--------------------------------------------------------------------------------
Content Translation Redirect for Drupal 8 requires the following:

* Content Translation
  Allows users to translate content entities.


Features
--------------------------------------------------------------------------------
The primary features include:

* An administration interface to manage redirect settings for each
  content entity type bundle. Each bundle settings includes a redirect
  status code and a message that can be displayed to the user after redirection.


Standard usage scenario
--------------------------------------------------------------------------------
1. Install the module.
2. Open admin/config/regional/content-translation-redirect and set the default
   settings if necessary.
3. Open admin/config/regional/content-translation-redirect/entity and configure
   redirects for those content entity bundles that you need.


Similar modules
--------------------------------------------------------------------------------
Modules that provide some others useful functionalities, similar
to Content Translation Redirect module:

* Content Language Access
  https://www.drupal.org/project/content_language_access
  This module helps when you have a content that needs to have
  access restriction by Drupal language.


Credits / contact
--------------------------------------------------------------------------------
Developed and maintained by Andrey Tymchuk (WalkingDexter)
https://www.drupal.org/u/walkingdexter

Ongoing development is sponsored by Drupal Coder.
https://www.drupal.org/drupal-coder-initlab-llc
