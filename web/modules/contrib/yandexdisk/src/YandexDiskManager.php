<?php

namespace Drupal\yandexdisk;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\yandex_oauth\YandexOAuthTokens;

/**
 * Defines YandexDiskManager class.
 */
class YandexDiskManager {

  /**
   * Collection of Disk instances indexed by account owner name.
   *
   * @var YandexDiskApiWebdavHelper[]
   */
  protected $diskCollection = [];

  /**
   * The Yandex OAuth service.
   *
   * @var \Drupal\yandex_oauth\YandexOAuthTokens
   */
  protected $yandexOauth;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * YandexDiskManager constructor.
   *
   * @param \Drupal\yandex_oauth\YandexOAuthTokens $yandex_oauth
   *   The Yandex OAuth service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(YandexOAuthTokens $yandex_oauth, TranslationInterface $string_translation) {
    $this->yandexOauth = $yandex_oauth;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface::translate()
   */
  protected function t($string, array $args = [], array $options = []) {
    return $this->stringTranslation->translate($string, $args, $options);
  }

  /**
   * Creates a YandexDiskApiWebdavHelper class instance by an account name.
   *
   * @param string $name
   *   Yandex.Disk account name.
   *
   * @return \Drupal\yandexdisk\YandexDiskApiWebdavHelper
   *   Disk class instance.
   *
   * @throws \Drupal\yandexdisk\YandexDiskException
   *   If there is no valid access token for requested account.
   */
  public function getDisk($name) {
    if (!isset($this->diskCollection[$name])) {
      if ($token = $this->yandexOauth->get($name)) {
        $disk = new YandexDiskApiWebdavHelper('OAuth ' . $token->token);
        $disk->user = $name;

        $this->diskCollection[$name] = $disk;
      }
      else {
        throw new YandexDiskException($this->t('Access token missing for @account.', ['@account' => $name]));
      }
    }

    return $this->diskCollection[$name];
  }

  /**
   * Copies a resource with all included contents between two Disks.
   *
   * @param \Drupal\yandexdisk\YandexDiskApiWebdavHelper $src_disk
   *   Source Disk instance.
   * @param string $src_path
   *   Source path, relative to the root, and with a leading slash.
   * @param \Drupal\yandexdisk\YandexDiskApiWebdavHelper $dst_disk
   *   Destination Disk instance.
   * @param string $dst_path
   *   Destination path, relative to the root, and with a leading slash.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function copyRecursive(YandexDiskApiWebdavHelper $src_disk, $src_path, YandexDiskApiWebdavHelper $dst_disk, $dst_path) {
    if ($src_disk->isFile($src_path)) {
      $data = $src_disk->read($src_path, NULL, NULL);
      $properties = $src_disk->getProperties($src_path);
      return $dst_disk->write($dst_path, $data, $properties['d:getcontenttype']);
    }
    elseif ($dst_disk->mkdir($dst_path)) {
      foreach ($src_disk->scanDir($src_path) as $item_name) {
        if (!$this->copyRecursive($src_disk, $src_path . '/' . $item_name, $dst_disk, $dst_path . '/' . $item_name)) {
          return FALSE;
        }
      }
      return TRUE;
    }

    return FALSE;
  }

}
