<?php

namespace Drupal\onehub;

/**
 * Class OneHubService.
 */
class OneHubFormIdService {

  /**
   * The OneHub row id for the form id.
   *
   * @var string.
   */
  protected $id;

  /**
   * Sets the form_id for the OneHub Views Form.
   *
   * @param $id
   *   The row id used to set the form id.
   */
  public function setFormId($id) {
    $this->id = 'onehub_views_download_form_' . $id;
  }

  /**
   * Gets the form_id for the OneHub Views Form.
   */
  public function getFormId() {
    return $this->id;
  }

}
