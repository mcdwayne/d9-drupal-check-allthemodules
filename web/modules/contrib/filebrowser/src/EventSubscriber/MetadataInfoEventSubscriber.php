<?php

namespace Drupal\filebrowser\EventSubscriber;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\filebrowser\Events\MetadataInfo;
use Drupal\filebrowser\Services\Common;

class MetadataInfoEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['filebrowser.metadata_info'][] =  ['setInfo', 0];
    return $events;
  }

  public function setInfo($event) {
    /** @var MetadataInfo $event */
    $info = [
      Common::ICON => [
        'title' => $this->t('Icon'),
      ],
      Common::NAME => [
        'title' => $this->t('Name'),
        'sortable' => TRUE,
        'type' => 'string'
      ],
      Common::CREATED => [
        'title' => $this->t('Created'),
        //'sortable' => TRUE,
        'type' => 'integer'
      ],
      Common::SIZE => [
        'title' => $this->t('Size'),
        // fixme: formatted size not sortable
        'sortable' => false,
        'type' => 'integer'
      ],
      Common::MIME_TYPE => [
        'title' => $this->t('Mime type'),
        'sortable' => TRUE,
        'type' => 'string'
      ],
      Common::DESCRIPTION => [
        'title' => $this->t('Description'),
        'writable' => TRUE,
        //'sortable' => TRUE,
        'type' => 'string',
      ],
    ];
    $event->setMetaDataInfo($info);
  }

}