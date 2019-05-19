<?php

/**
 * @file
 * Contains \Drupal\sms_ui\GroupListUploadController
 */

namespace Drupal\sms_ui;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GroupListUploadController extends ControllerBase {

  /**
   * Returns the group list as a key-value array.
   *
   * This value is json-encoded if $ajax is true.
   */
  public function getGroupList($js) {

  }
}