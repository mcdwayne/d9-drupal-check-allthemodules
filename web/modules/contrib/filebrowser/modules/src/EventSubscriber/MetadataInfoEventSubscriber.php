<?php

namespace Drupal\filebrowser_extra\EventSubscriber;


use Drupal\filebrowser\Events\MetadataInfo;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MetadataInfoEventSubscriber implements EventSubscriberInterface {
  public static function getSubscribedEvents() {
    $events['filebrowser.metadata_info'][] = ['setInfo', 0];
    return $events;
  }

  public function setInfo($event) {
    /** @var MetadataInfo $event */
    $data = $event->getMetaDataInfo();
    $data['modified'] = [
      'title' => t('Modified'),
      //'sortable' => TRUE,
      'type' => 'integer'
    ];
    $event->setMetaDataInfo($data);
  }

}