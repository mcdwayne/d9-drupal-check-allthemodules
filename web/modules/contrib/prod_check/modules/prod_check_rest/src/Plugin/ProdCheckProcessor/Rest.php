<?php

namespace Drupal\prod_check_rest\Plugin\ProdCheckProcessor;

use Drupal\prod_check\Plugin\ProdCheckInterface;
use Drupal\prod_check\Plugin\ProdCheckProcessor\Internal;

/**
 * Release notes check
 *
 * @ProdCheckProcessor(
 *   id = "rest",
 *   title = @Translation("Rest prod check processor"),
 * )
 */
class Rest extends Internal {

  /**
   * Processes a single prod check plugin
   */
  public function process(ProdCheckInterface $check) {
    $check->setProcessor($this);

    $status = $check->state();
    $requirement = array(
      'status' => $status,
      'severity' => $status ? $this->ok() : $check->severity(),
      'title' => (string) $check->title(),
      'category' => (string) $check->category(),
    );

    if ($status) {
      $requirement += $check->successMessages();
    }
    else {
      $requirement += $check->failMessages();
    }

    return $requirement;
  }

}
