<?php

namespace Drupal\sign_for_acknowledgement\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Acknowledgements controller.
 */
class AcknowledgementsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function content($node) {

    $myform = \Drupal::formBuilder()->getForm('Drupal\sign_for_acknowledgement\Form\FilterForm', $node);
	
    $build['form'] = array(
        '#theme' => 'sign_for_acknowledgement_filters',
        '#form' => $myform,
    );
	
    $tableform = \Drupal::formBuilder()->getForm('Drupal\sign_for_acknowledgement\Form\TableForm', $node);
	
    $build['table'] = array(
        '#theme' => 'sign_for_acknowledgement_filters',
        '#form' => $tableform,
    );

    return $build;
  }

}
