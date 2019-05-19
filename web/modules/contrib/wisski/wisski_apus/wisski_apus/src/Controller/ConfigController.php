<?php
/**
 * @file
 * Contains \Drupal\wisski_apus\Controller\ConfigController.
 */
 
namespace Drupal\wisski_apus\Controller;
 
use Drupal\Core\Controller\ControllerBase;
 
class ConfigController extends ControllerBase {
  public function overview() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->t("Configure WissKI's Content and Annotation Processing"),
    );
  }

  public function dummy() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->t("bla blubb"),
    );
  }

}
