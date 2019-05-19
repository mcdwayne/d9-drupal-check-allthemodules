<?php

namespace Drupal\twig_temp;

use Drupal\Component\PhpStorage\MTimeProtectedFileStorage;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\TemporaryStream;
use Drupal\Core\Template\TwigPhpStorageCache;

/**
 * Storage for Twig templates in temporary storage.
 */
class TwigTemporaryPhpStorageCache extends TwigPhpStorageCache {

  /**
   * {@inheritdoc}
   */
  protected function storage() {
    if (!isset($this->storage)) {
      $temporary_stream = new TemporaryStream();
      $config = [
        'bin' => 'twig',
        'secret' => Settings::getHashSalt(),
        'directory' => $temporary_stream->getDirectoryPath(),
      ];
      $this->storage = new MTimeProtectedFileStorage($config);
    }
    return $this->storage;
  }

  /**
   * Delete all temporary twig templates.
   *
   * If the temporary directory is not shared, this will not clear templates on
   * all web servers. Since template content is used for the hash in the file
   * name, we expect that those templates will be cleared over time by a reboot
   * or a deployment.
   *
   * Core's implementation hardcodes a call out to the PhpStorage class in
   * drupal_rebuild(), so this does not override an existing method.
   */
  public function deleteAll() {
    $this->storage()->deleteAll();
  }

}
