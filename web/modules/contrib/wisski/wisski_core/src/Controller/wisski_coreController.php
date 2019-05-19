<?php
/**
 * @file
 * Contains \Drupal\example\Controller\ExampleController.
 */
   
namespace Drupal\wisski_core\Controller;
   
use Drupal\Core\Controller\ControllerBase;

class wisski_coreController extends ControllerBase {

  /**
    * {@inheritdoc}
    */
  public function content() {
    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello World!!!'),
    );
    return $build;
  }
                                    
}
   