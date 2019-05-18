<?php
/**
 * Debug controller. Visible for site administrators only.
 * @author appels
 */

namespace Drupal\adcoin_payments\Controller;
use Drupal\Core\Controller\ControllerBase;

class DebugController extends ControllerBase {
  public function content() {
    return [
      '#markup' => ''
    ];
  }
}