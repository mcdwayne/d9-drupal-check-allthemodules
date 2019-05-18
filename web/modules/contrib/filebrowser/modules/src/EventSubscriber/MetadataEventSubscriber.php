<?php

namespace Drupal\filebrowser_extra\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filebrowser\Entity\FilebrowserMetadataEntity;
use Drupal\filebrowser\Events\MetadataEvent;
use Drupal\filebrowser\File\DisplayFile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MetadataEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * @var integer
   */
  protected $nid;

  public static function getSubscribedEvents() {
    $events['filebrowser.metadata_event'][] = ['createModified', 0];
    return $events;
  }

  // fixme: "go up" folder table cell for modified not shown
  public function createModified(MetadataEvent $event) {
    /** @var FilebrowserMetadataEntity $metadata */
    /** @var DisplayFile $file */
    $this->nid = $event->nid;
    $fid = $event->getFid();
    $file = $event->file;
    $columns = $event->columns;

    // Only calculate modified time if this column is selected
    if (!empty($columns['modified'])) {
      if (isset($file->fileData->uri)) {
        $file_real_path = \Drupal::service('file_system')->realpath($file->fileData->uri);
        $m_time = filemtime($file_real_path);
        $m_time = empty($m_time) ? 0 : $m_time;
        $content = serialize(\Drupal::service('date.formatter')->format($m_time, 'short'));
        $theme = "";
        $storage = \Drupal::entityTypeManager()->getStorage('filebrowser_metadata_entity');

        $query = \Drupal::entityQuery('filebrowser_metadata_entity')
          ->condition('fid', $fid)
          ->condition('module', 'filebrowser_extra')
          ->condition('name', 'modified');
        $entity_id = $query->execute();

        if ($entity_id) {
          // entity exists, so we just update the contents
          $metadata = $storage->load(reset($entity_id));
          $metadata->setContent($content);
          $metadata->save();
        }
        else {
          $value = [
            'fid' => $fid,
            'nid' => $this->nid,
            'name' => 'modified',
            'title' => t('Modified'),
            'module' => 'filebrowser_extra',
            'theme' => $theme,
            'content' => $content,
          ];
          $entity = FilebrowserMetadataEntity::create($value);
          $entity->save();
        }
      }
    }
  }

}