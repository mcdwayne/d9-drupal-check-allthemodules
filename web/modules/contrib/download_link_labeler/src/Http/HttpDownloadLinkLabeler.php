<?php
namespace Drupal\download_link_labeler\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Create new class that returns remote file size
 **/
class HttpDownloadLinkLabeler {
  use StringTranslationTrait;

  public function getFileSize($fileUrl) {
    $client = new \GuzzleHttp\Client();
    try {
      $response = $client->head($fileUrl, ['http_errors' => false]);
      $fileSize = $response->getHeader('Content-Length');
      return reset($fileSize);
    }
    catch (RequestException $e) {
      return($this->t('Unknown Size'));
    }
  }
}