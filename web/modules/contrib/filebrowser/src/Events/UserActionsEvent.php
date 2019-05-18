<?php

namespace Drupal\filebrowser\Events;

use Symfony\Component\EventDispatcher\Event;

class UserActionsEvent extends Event{

  protected $actions;
  protected $fileData;

  /**
   * ActionsInfoEvent constructor.
   * @param array $actions
   * @param array $fileData Array containing a list of files to be displayed
   * on a filebrowser node.
   */
  public function __construct($actions, $fileData) {
    $this->actions = $actions;
    $this->fileData = $fileData;
  }

  /**
   * @return mixed
   */
  public function getActions() {
    return $this->actions;
  }

  /**
   * @param mixed $actions
   */
  public function setActions($actions) {
    $this->actions = $actions;
  }

  public function getFileData() {
    return $this->fileData;
  }

}