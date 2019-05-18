<?php

namespace Drupal\media_migration\EventSubscriber;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_plus\Event\MigrateEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Media migration event subscriber.
 */
class MediaMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Migrate prepare row event handler.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    $this->fileEntityFieldToMedia($event);
    $this->imageFieldToMedia($event);
  }

  /**
   * Migrates file entity fields to media ones.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   */
  private function fileEntityFieldToMedia(MigratePrepareRowEvent $event) {
    $row = $event->getRow();
    $source = $event->getSource();

    // Change the type from file to file_entity so it can be processed by
    // a migrate field plugin.
    // @see \Drupal\file_entity_migration\Plugin\migrate\field\FileEntity
    if (in_array($source->getPluginId(), [
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_per_view_mode',
      'd7_field_instance_per_form_display',
      'd7_view_mode',
    ])) {
      if ($row->getSourceProperty('type') == 'file') {
        $row->setSourceProperty('type', 'file_entity');
      }
    }

    // Skip migrating field instances whose destination bundle does not exist.
    if (in_array($source->getPluginId(), [
      'd7_field_instance',
      'd7_field_instance_per_view_mode',
      'd7_field_instance_per_form_display',
    ])) {
      if ($row->getSourceProperty('entity_type') == 'file') {
        // Don't migrate bundles which don't exist in the destination.
        $media_bundle = $row->getSourceProperty('bundle');
        if (!\Drupal::entityTypeManager()->getStorage('media_type')->load($media_bundle)) {
          $field_name = $row->getSourceProperty('field_name');
          throw new MigrateSkipRowException('Skipping field ' . $field_name . ' as its target is ' . $media_bundle . ', which does not exist in the destination.');
        }
      }
    }

    // Transform entity reference fields pointing to file entities so
    // they point to media ones.
    if (($source->getPluginId() == 'd7_field') && ($row->getSourceProperty('type') == 'entityreference')) {
      $settings = $row->getSourceProperty('settings');
      if ($settings['target_type'] == 'file') {
        $settings['target_type'] = 'media';
        $row->setSourceProperty('settings', $settings);
      }
    }

    // File types of type Document need to be mapped to the File media bundle.
    if (in_array($source->getPluginId(), ['d7_file_entity_type', 'd7_file_entity_item']) && ($row->getSourceProperty('type') == 'document')) {
      $row->setSourceProperty('type', 'file');
    }

    // Map path alias sources from file/1234 to media/1234.
    if (($source->getPluginId() == 'd7_url_alias') && (strpos($row->getSourceProperty('source'), 'file/') === 0)) {
      $source_url = preg_replace('/^file/', 'media', $row->getSourceProperty('source'));
      $row->setSourceProperty('source', $source_url);
    }

    // Map redirections from file/1234 to media/1234.
    if (($source->getPluginId() == 'd7_path_redirect') && (strpos($row->getSourceProperty('redirect'), 'file/') === 0)) {
      $redirect = preg_replace('/^file/', 'media', $row->getSourceProperty('redirect'));
      $row->setSourceProperty('redirect', $redirect);
    }

    // Map file menu links to media ones.
    if (($source->getPluginId() == 'menu_link') && (strpos($row->getSourceProperty('link_path'), 'file/') === 0)) {
      $link_path = preg_replace('/^file/', 'media', $row->getSourceProperty('link_path'));
      $row->setSourceProperty('link_path', $link_path);
    }
  }

  /**
   * Migrates image fields to media image fields.
   *
   * Changes the type from image to media_image so it can be processed by
   * a migrate field plugin.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   *
   * @see \Drupal\media_migration\Plugin\migrate\field\MediaImage
   */
  private function imageFieldToMedia(MigratePrepareRowEvent $event) {
    if (in_array($event->getSource()->getPluginId(), [
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_per_view_mode',
      'd7_field_instance_per_form_display',
      'd7_view_mode',
    ])) {
      $row = $event->getRow();
      if (($row->getSourceProperty('type') == 'image')) {
        $row->setSourceProperty('type', 'media_image');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PREPARE_ROW => ['onPrepareRow'],
    ];
  }

}
