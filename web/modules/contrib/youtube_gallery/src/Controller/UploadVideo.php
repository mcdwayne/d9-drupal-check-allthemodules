<?php

namespace Drupal\youtube_gallery\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Upload video processing.
 */
class UploadVideo extends ControllerBase {

  private $client;
  private $clientId;
  private $clientSecret;

  /**
   * Google API that uploads video.
   */
  public function youtubeUpload($title, $desc, $tags, $category, $video) {

    $lib = 'libraries/google-api-php-client/vendor/autoload.php';
    $path = drupal_realpath($lib);

    require_once $path;

    $this->clientId = $this->config('youtube_gallery.formsettings')->get('client_id');
    $this->clientSecret = $this->config('youtube_gallery.formsettings')->get('client_secret');

    $this->client = new \Google_Client();
    $this->client->setClientId($this->clientId);
    $this->client->setClientSecret($this->clientSecret);
    $this->client->setScopes('https://www.googleapis.com/auth/youtube');

    session_start();

    global $base_url;
    // Getting clientId and clientSecret from configuration.
    $redirect = $base_url . '/admin/config/youtube_gallery/upload-video';

    $this->client->setRedirectUri($redirect);

    $youtube = new \Google_Service_YouTube($this->client);

    // Check if an auth token exists for the required scopes.
    $tokenSessionKey = 'token-' . $this->client->prepareScopes();

    if (isset($_GET['code'])) {

      if (strval($_SESSION['state']) !== strval($_GET['state'])) {

        drupal_set_message($this->t('Your session has been lost please try again !'), 'error');
      }

      $this->client->authenticate($_GET['code']);
      $_SESSION[$tokenSessionKey] = $this->client->getAccessToken();

      $response = new RedirectResponse($redirect);
      $response->send();

    }

    if (isset($_SESSION[$tokenSessionKey])) {

      $this->client->setAccessToken($_SESSION[$tokenSessionKey]);
    }

    // Check to ensure that the access token was successfully acquired.
    if ($this->client->getAccessToken()) {

      try {
        // REPLACE this value with the path to the file you are uploading.
        $videoPath = $video;

        $addtags = explode(',', $tags);

        // Create a snippet with title, description, tags and category ID
        // Create an asset resource and set its snippet metadata and type.
        // This example sets the video's title, description, keyword tags, and
        // video category.
        $snippet = new \Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($title);
        $snippet->setDescription($desc);
        $snippet->setTags($addtags);

        // Numeric video category. See
        // https://developers.google.com/youtube/v3/docs/videoCategories/list
        $snippet->setCategoryId($category);

        // Set the video's status to "public". Valid statuses are "public",
        // "private" and "unlisted".
        $status = new \Google_Service_YouTube_VideoStatus();
        $status->privacyStatus = "public";

        // Associate the snippet and status objects with a new video resource.
        $video = new \Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        // Specify the size of each chunk of data, in bytes. Set a higher value
        // for reliable connection as fewer chunks lead to faster uploads. Set a
        // lower value for better recovery on less reliable connections.
        $chunkSizeBytes = 1 * 1024 * 1024;

        // Setting the defer flag to true tells the client to return a request
        // which can be called
        // with ->execute(); instead of making the API call immediately.
        $this->client->setDefer(TRUE);

        // Create a request for the API's videos.insert method to create and
        // upload the video.
        $insertRequest = $youtube->videos->insert("status,snippet", $video);

        // Create a MediaFileUpload object for resumable uploads.
        $media = new \Google_Http_MediaFileUpload(
          $this->client,
          $insertRequest,
          'video/*',
          NULL,
          TRUE,
          $chunkSizeBytes
          );
        $media->setFileSize(filesize($videoPath));

        // Read the media file and upload it chunk by chunk.
        $status = FALSE;
        $handle = fopen($videoPath, "rb");

        while (!$status && !feof($handle)) {

          $chunk = fread($handle, $chunkSizeBytes);
          $status = $media->nextChunk($chunk);

        }

        fclose($handle);

        // If you want to make other calls after the file upload,
        // set setDefer back to false.
        $this->client->setDefer(FALSE);

        drupal_set_message($this->t('Video uploaded successfully'), 'status');

      }
      catch (\Google_Service_Exception $e) {

        drupal_set_message($this->t('Service error occurred: Invalid Authentication.') . $e->getMessage(), 'error');

      }
      catch (\Google_Exception $e) {

        drupal_set_message($this->t('Client Authentication error occured') . $e->getMessage(), 'error');

      }

      $_SESSION[$tokenSessionKey] = $this->client->getAccessToken();

    }
    elseif ($this->clientId == '') {

      drupal_set_message($this->t('Client credential required'), 'error');

    }
    else {

      // If the user hasn't authorized the app, initiate the OAuth flow.
      $state = mt_rand();
      $this->client->setState($state);
      $_SESSION['state'] = $state;

      $authUrl = $this->client->createAuthUrl();

      $messageWithHtml = Markup::Create('<b>Authorization Required:</b> <a href=' . $authUrl . '> authorize access </a> before proceeding');
      drupal_set_message($messageWithHtml, 'error');

    }

  }

}
