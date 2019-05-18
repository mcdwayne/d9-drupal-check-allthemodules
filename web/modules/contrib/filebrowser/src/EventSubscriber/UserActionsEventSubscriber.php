<?php

namespace Drupal\filebrowser\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filebrowser\Events\UserActionsEvent;
use Drupal\filebrowser\Services\Common;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserActionsEventSubscriber.
 *
 * @package Drupal\filebrowser
 */
class UserActionsEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {

    $events['filebrowser.user_actions'][] = ['handler', 0 ];
    return $events;
  }

  public function handler(UserActionsEvent $event) {
    $actions= [];
    $fileData = $event->getFileData();
    $node = \Drupal::request()->attributes->get('node');

    if ($fileData['data']['stats']['files_count'] || $fileData['data']['stats']['folders_count'] ) {
      if (\Drupal::service('filebrowser.common')->canDownloadArchive($node) && function_exists('zip_open')) {
        $actions[] = [
          'operation' => 'download',
          'title' => $this->t("Download selected items as an ZIP archive (only files)")
        ];
      }
      if (\Drupal::currentUser()->hasPermission(Common::DELETE_FILES)) {
        $actions[] = [
          'operation' => 'delete',
          'title' => $this->t("Delete selected items")
        ];
      }
      if (\Drupal::currentUser()->hasPermission(Common::RENAME_FILES)) {
        $actions[] = [
          'operation' => 'rename',
          'title' => $this->t("Rename selected items")
        ];
      }
    }
    $event->setActions($actions);
  }

}