<?php

namespace Drupal\file_downloader;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_downloader\Entity\DownloadOptionConfig;
use Drupal\file_downloader\Entity\DownloadOptionConfigInterface;

/**
 * Provides dynamic permissions for download option config entities.
 */
class DownloadOptionConfigPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of download option config entities permissions.
   *
   * @return array
   *   The download option config permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function downloadOptionConfigConfigPermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (DownloadOptionConfig::loadMultiple() as $mediaBulkConfig) {
      $perms += $this->buildPermissions($mediaBulkConfig);
    }

    return $perms;
  }

  /**
   * Returns a list of download option config entities permissions for a given
   * download option config entities.
   *
   * @param DownloadOptionConfigInterface $type
   *   The download option config.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(DownloadOptionConfigInterface $downloadOptionConfig) {
    $downloadOptionConfigId = $downloadOptionConfig->id();
    $type_params = ['%type_name' => $downloadOptionConfig->label()];

    return [
      "use " . $downloadOptionConfigId . " download option link" => [
        'title' => $this->t('%type_name : Use download option', $type_params),
      ],
    ];
  }

}
