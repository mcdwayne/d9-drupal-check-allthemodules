<?php

namespace Drupal\file_download_token\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_download_token\DownloadTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class TokenDownloadController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\file_download_token\DownloadTokenManagerInterface
   */
  protected $downloadTokenManager;

  public function __construct(DownloadTokenManagerInterface $download_token_manager) {
    $this->downloadTokenManager = $download_token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_download_token.download_token_manager')
    );
  }

  public function download(RouteMatchInterface $route_match, $token = NULL) {
    if ($token != NULL && $file = $this->downloadTokenManager->getFile($token)) {
      $uri = $file->getFileUri();

      $mime = \Drupal::service('file.mime_type.guesser')->guess($uri);

      $headers = array(
        'Content-Type' => $mime . '; name="' . Unicode::mimeHeaderEncode(basename($uri)) . '"',
        'Content-Length' => filesize($uri),
        'Content-Disposition' => 'attachment; filename="' . Unicode::mimeHeaderEncode($file->label()) . '"',
        'Cache-Control' => 'private',
      );

      if (isset($contenttype)) {
        $headers['Content-Type'] = $contenttype;
      }

      return new BinaryFileResponse($uri, 200, $headers, TRUE);
    }
    else {
      return new Response($this->t('File not found.'), 404);
    }
  }

}