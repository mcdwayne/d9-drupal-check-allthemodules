<?php

namespace Drupal\filebrowser\Events;

use Symfony\Component\EventDispatcher\Event;

class MetadataInfo extends Event {

  /**
   *  @var array
   *  $metaDataInfo is an associative array with following fields
   *  'key' = [
   *    'title' => t('Description'), // required
   *    'writable' => TRUE,          // optional, default false
   *    'sortable' => TRUE,          // optional, default false
   *    'type' => 'string            // required
   */
  protected $metaDataInfo;

  /**
   * MetadataInfo constructor.
   * @param $metaDataInfo
   */
  public function __construct($metaDataInfo) {
    $this->metaDataInfo = $metaDataInfo;
  }

  /**
   * @return mixed
   */
  public function getMetaDataInfo() {
    return $this->metaDataInfo;
  }

  /**
   * @param mixed $metaDataInfo
   */
  public function setMetaDataInfo($metaDataInfo) {
    $this->metaDataInfo = $metaDataInfo;
  }

}