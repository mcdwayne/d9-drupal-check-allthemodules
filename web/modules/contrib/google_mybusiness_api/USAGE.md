# Google My Business API Usage Guide

## Google My Business API Reference
https://developers.google.com/my-business/reference/rest/index

## How to use the Google My Business API client
Example:
```
$gmbService = \Drupal::service('google_mybusiness_api.client');

try {
  $accounts = $gmbService->googleServiceMyBusiness->accounts->listAccounts();
  // ksm($accounts);
}
  catch (Exception $e) {
  // ksm($e);
}
```
