<?php

namespace Drupal\vimeo_upload\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class VimeoUploadController.
 */
class VimeoUploadController extends ControllerBase {

  /**
   * Vimeo upload.
   *
   * @return string
   *   Return Vimeo Upload form driven by Javascript.
   */
  public function upload() {

    $config = \Drupal::config('vimeo_upload.settings');
    if ($config->get('access_token') !== '') {
      $build['vimeo_upload'] = [
        '#theme' => 'vimeo_upload',
        '#attached' => [
          'library' => [
            'vimeo_upload/init',
          ],
          'drupalSettings' => [
          // @todo decrypt
            'access_token' => $config->get('access_token'),
          ],
        ],
      ];
    }
    else {
      $url = Url::fromRoute('vimeo_upload.settings');
      $link = Link::fromTextAndUrl($this->t('configure it'), $url);
      $link = $link->toRenderable();
      $build['missing_access_token'] = [
        '#markup' => $this->t('Missing access token, @configure_link.', ['@configure_link' => render($link)]),
      ];
    }

    return $build;
  }

}
