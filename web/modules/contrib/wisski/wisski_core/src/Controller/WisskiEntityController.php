<?php

namespace Drupal\wisski_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wisski_core\WisskiBundleInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\LocalRedirectResponse;

class WIsskiEntityController extends ControllerBase {

  /**
  * {@inheritdoc}
  */
  public function content() {
    $form = array();
    $form[] = array(
      '#type' => 'markup',
      '#markup' => t('Hello World!'),
    );
    $form[] = array(
      '#type' => 'textfield',
      '#default_value' => 'murks',
    );
    return $form;
  }
  
  public function add(WisskiBundleInterface $wisski_bundle) {
    #dpm(microtime(), "before");
    $entity = $this->entityManager()->getStorage('wisski_individual')->create(array(
      'bundle' => $wisski_bundle->id(),
    ));
    #dpm(microtime(), "in");
    
    $form = $this->entityFormBuilder()->getForm($entity);
    
    #dpm(microtime(), "after");
    
    return $form;
  }
}