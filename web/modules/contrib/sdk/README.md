# SDK

An API to define configurations for specific Software Development Kit (SDK). API, as Drupal 8, based on Composer. This allows you to describe needed configuration for SDK by defining a form for particular kit and easily grab its sources via Composer. In addition there is a possibility to process result of OAuth callback.

API description can be found in [api.php](api.php) and inside of 4 implementations, provided out of the box:
 
- [Facebook](modules/sdk_facebook)
- [Instagram](modules/sdk_instagram)
- [LinkedIn](modules/sdk_linkedin)
- [Twitter](modules/sdk_twitter)
