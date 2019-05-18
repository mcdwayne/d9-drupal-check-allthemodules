# Google Cloud Vision
This module integrates the Google composer library with Drupal.
It makes the API slightly more simplistic to integrate with custom
logic with entities in Drupal.
You can configure your Json Key from Google Cloud and then use the API
in this module to execute requests to Google Cloud Vision.

## Google Cloud Vision Media
This submodule shows a basic implementation of the core module.
Currently only the google cloud labelling is integrated with the
module. This means that we send a image with the extension
```jpg, jpeg, gif, png``` to Google Cloud Vision.

The image is then analyzed and the resulting information is returned
to the media. These labels are then compared to existing tags that
are already available in the media entity, the ones that are missing are
then added.
