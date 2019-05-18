<?php

namespace Drupal\filebrowser\Events;

use Symfony\Component\EventDispatcher\Event;

class MetadataEvent extends Event {

  /**
   * @var array $metadata
   */
  protected $metadata;

  /**
   * @var integer
   * fid of the file owning this event
   */
  protected $fid;

  public $file;

  /**
   * @var integer
   */
  public $subdir_fid;

  /**
   * @var integer
   */
  public $nid;

  /**
   * @var array
   */
  public $columns;

  /**
   * MetadataInfo constructor.
   * @param integer $fid
   * @param integer $subdir_fid
   * @param integer $nid
   * @param array $columns Array containing the selected metadata to be displayed
   * @param \stdClass $file
   */

  public function __construct($nid, $fid, $file, $subdir_fid, $columns) {
    $this->fid = $fid;
    $this->file = $file;
    $this->subdir_fid = $subdir_fid;
    $this->nid = $nid;
    $this->columns = $columns;
    return $this;
  }

  public function getFid() {
    return $this->fid;
  }

  public function setFid($fid) {
    $this->fid = $fid;
  }

  /**
   * @return mixed
   */
  public function getMetadata() {
    return $this->metadata;
  }

  /**
   * @param mixed $metadata
   */
  public function setMetadata($metadata) {
    $this->metadata = $metadata;
  }

}