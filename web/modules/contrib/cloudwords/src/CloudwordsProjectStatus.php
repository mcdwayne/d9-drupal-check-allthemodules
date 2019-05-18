<?php
namespace Drupal\cloudwords;

/**
 * Represents the value for a project status field in Cloudwords.
 *
 * @author dougkim
 * @since 1.0
 */
class CloudwordsProjectStatus {

  protected $display;
  protected $code;

  /**
   * Constructor used to create a Cloudwords project status
   *
   * - display: int The project status display name
   * - code: string The project status internal code
   *
   * @param array $params The parameters used to initialize a project status instance
   */
  public function __construct($params) {
    if (isset($params['display'])) {
      $this->display = $params['display'];
    }
    if (isset($params['code'])) {
      $this->code = $params['code'];
    }
  }

  public function getDisplay() {
    return $this->display;
  }

  public function setDisplay($display) {
    $this->display = $display;
  }

  public function getCode() {
    return $this->code;
  }

  public function setCode($code) {
    $this->code = $code;
  }

}
