<?php

namespace Drupal\alt_stream_wrappers\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\Url;
use Drupal\Core\Site\Settings;

/**
 * Defines an alternative Drupal temporary (alt-temp://) stream wrapper class.
 */
class AltTempStream extends LocalStream {

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL_HIDDEN;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Alternative Temporary files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Alternative Temporary file storage (an alternative to the temporary:// scheme).');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return Settings::get('alt_stream_wrappers_alt-temp_path', file_directory_temp());
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return Url::fromRoute('alt_stream_wrappers.temporary', [], ['absolute' => TRUE, 'query' => ['file' => $path]])->toString();
  }

}
