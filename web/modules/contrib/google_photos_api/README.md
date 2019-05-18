# Google Photos API 
Using the Google Photos Library API your app can read, write, 
and share photos and videos in Google Photos.

## Requirements
* Google API PHP Client 
https://www.drupal.org/project/google_api_client

## Setup
Configure the `Google API PHP Client` with the following scope:
```
https://www.googleapis.com/auth/photoslibrary
```
See `Google API PHP Client` module for more setup details
https://www.drupal.org/project/google_api_client.

## Google Photos API Reference
https://developers.google.com/photos/library/guides/overview

## How to use the Google Photos API client
Example:
```
$GooglePhotosService = \Drupal::service('google_photos_api.client');

try {
  $albums = $GooglePhotosService->googleServicePhotosLibrary->albums->listAlbums();
  // ksm($albums);
}
  catch (Exception $e) {
  // ksm($e);
}
```
