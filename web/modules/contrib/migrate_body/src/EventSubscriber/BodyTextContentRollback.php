<?php

namespace Drupal\migrate_body\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\migrate\Event\MigrateEvents;
use Sunra\PhpSimple\HtmlDomParser;
use Drupal\neiu_migration_common\Plugin\migrate\process\NeiuBodyTextMigration;

/**
 * Event Subscriber for body text rollback.  This will delete the entity and
 * delete the file.
 */
class BodyTextContentRollback implements EventSubscriberInterface {

  /**
   * Code that should be triggered on event specified.
   */
  public function onRollback(MigrateRowDeleteEvent $event) {
    $nid = $event->getDestinationIdValues()['nid'];

    $process_plugin = $event->getMigration()->getProcess();

    if (isset($nid)) {
      $node = node_load($nid);
      if ($node) {
        $entityManager = \Drupal::service('entity_field.manager');
        $fields = $node->getFieldDefinitions();

        foreach ($fields as $name => $field) {
          $create_image_entity = $process_plugin[$name][0]['create_image_entity'];
          $type = $field->getType();
          if ($type == 'text_long' && $create_image_entity) {
            $this->rollbackSavedItems($name, $node, $event);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROW_DELETE][] = ['onRollback'];
    return $events;
  }

  /**
   * Main rollback logic.
   */
  private function rollbackSavedItems($field_name, $node, $event) {
    $values = $node->get($field_name)->getValue();
    foreach ($values as $key => $value) {

      $body = $value['value'];
      $process_plugin = $event->getMigration()->getProcess();
      $root_url_regex = $process_plugin[$field_name][0]['root_url_regex'];
      $subfolder_location = $process_plugin[$field_name][0]['subfolder_location'];

      // Get DOM Parser for easy html parsing.
      $dom = HtmlDomParser::str_get_html($body);

      // Get all links.
      foreach ($dom->find('a') as $element) {
        // This is dangerous though.  Someone else might be using that file.
        $this->deleteFile($element->href, $subfolder_location);
      }

      // Get all images.
      foreach ($dom->find('img') as $image) {
        // This is dangerous though.  Someone else might be using that file.
        $delete = $this->deleteFile($image->src, $subfolder_location);
        if ($delete) {
          $this->deleteFileManaged($image->getAttribute('data-entity-uuid'));
        }
      }
    }
  }

  /**
   * Deletes the associated file.
   */
  private function deleteFile($link, $subfolder_location) {
    $delete = FALSE;

    $public = file_create_url('public://');
    $public = parse_url($public);
    $public = $public['path'];
    $public = str_replace("/", "\/", $public);

    $pattern = '/' . $public . $subfolder_location . '\/(.*\.(' . NeiuBodyTextMigration::ALLOWED_EXTENSIONS . '))/i';

    if (preg_match($pattern, $link, $matches)) {
      $_link = urldecode($link);
      $real_path = \Drupal::service('file_system')->realpath(DRUPAL_ROOT . $_link);
      unlink($real_path);
      $delete = TRUE;
    }
    return $delete;
  }

  /**
   * Deletes related file_managed record.
   */
  private function deleteFileManaged($uuid) {
    $query = \Drupal::service('entity.query')->get('file');
    $query->condition('uuid', $uuid);
    $entity_ids = $query->execute();

    $storage_handler = \Drupal::entityTypeManager()->getStorage('file');
    $entities = $storage_handler->loadMultiple($entity_ids);
    $storage_handler->delete($entities);
  }

}
