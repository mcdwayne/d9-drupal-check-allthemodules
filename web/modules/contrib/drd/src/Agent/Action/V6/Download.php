<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Offers a download file.
 */
class Download extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    $filename = self::$crypt->encryptFile($args['source']);

    if (ob_get_level()) {
      ob_end_clean();
    }
    drupal_set_header('Content-Type: text/plain; charset=utf-8');
    drupal_set_header('X-DRD-Agent: ' . $_SERVER['HTTP_X_DRD_VERSION']);
    drupal_set_header('X-DRD-Encrypted', $filename != $args['source']);

    if ($fd = fopen($filename, 'rb')) {
      while (!feof($fd)) {
        print fread($fd, 1024);
      }
      fclose($fd);
    }
    else {
      drupal_not_found();
    }
    exit();
  }

}
