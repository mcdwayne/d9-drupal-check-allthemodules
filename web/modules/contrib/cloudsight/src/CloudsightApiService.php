<?php

namespace Drupal\cloudsight;

use GuzzleHttp\Client;

/**
 * Class CloudsightApiService.
 */
class CloudsightApiService implements CloudsightApiServiceInterface {

  /**
   * Constructs a new CloudsightApiService object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public function sendImage($file) {
    $key = \Drupal\Core\Site\Settings::get('cloudsight_api_key');


    // Send the image using CURL and not Guzzle because the API is
    // funny about POST values.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudsight.ai/v1/images");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    curl_setopt($ch, CURLOPT_POST, TRUE);

    /* @TODO Use an image style so we don't send massive images */
    $path = \Drupal::service('file_system')->realpath($file->getFileUri());

    $cfile = curl_file_create($path, $file->getMimeType(), $file->getFilename());

    $data = [
      'image' => $cfile,
      'locale' => 'en_US',
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_INFILESIZE, $file->getSize());

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: CloudSight ${key}",
      'Content-Type: multipart/form-data',
    ));

    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

    $response = curl_exec($ch);
    curl_close($ch);

    if (is_string($response)) {
      $response = json_decode($response);

      // If we have a complete response then return it.
      if ($response->status == 'completed') {
        return $response->name;
      }
      else {
        return $this->getImage($response->token);
      }
    }

  }

  /**
   * Get result of an image.
   * https://cloudsight.docs.apiary.io/#reference/0/image/view-an-image-response
   *
   * @string $token
   *
   * @return mixed
   */
  private function getImage($token) {

    $key = \Drupal\Core\Site\Settings::get('cloudsight_api_key');

    $client = new Client([
      'base_uri' => 'https://api.cloudsight.ai/v1/images/',
    ]);

    // Add a delay to give time for the API to finish processing the image.
    $response = $client->get($token, [
      'delay'   => 1000,
      'headers' => [
        'Authorization' => "CloudSight ${key}",
      ],
    ]);


    $response_body = json_decode($response->getBody()->getContents());

    // If we have a response.
    if ($response_body->status == 'completed' || $response_body->status == 'skipped') {
      // We have some text, show it.
      if ($response_body->status == 'completed') {
        return $response_body->name;
      }
      else {
        // Things didn't quite work out.
        $notify_reasons = [
          'offensive',
          'blurry',
          'dark',
          'bright',
        ];
        if (in_array($response_body->reason, $notify_reasons)) {
          $replacements = [
            '@reason' => $response_body->reason,
          ];
          $notify_message = t('Sorry, there was a problem processing this image: @reason', $replacements);
          return $notify_message;
        }
        else {
          // The other reasons (close, unsure) all need us to repost.
          $this->repostImage($token);

          // Try again.
          $this->getImage($token);
        }
      }
    }
    else {
      // If we don't have a response check it again.
      $this->getImage($token);
    }
  }


  /**
   * Retry getting a description for an image.
   * https://cloudsight.docs.apiary.io/#reference/0/repost
   *
   * @string $token
   */
  private function repostImage($token) {
    $key = \Drupal\Core\Site\Settings::get('cloudsight_api_key');

    $client = new Client([
      'base_uri' => 'https://api.cloudsight.ai/v1/images/',
    ]);

    // Repost the image by using the token.
    $repost_path = "${token}/repost";
    $client->get($repost_path, [
      'headers' => [
        'Authorization' => "CloudSight ${key}",
      ],
    ]);
  }
}
