<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Form\Adapter\AddForm.
 */

namespace Drupal\wisski_salz\Form\Adapter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;

/**
 * Controller for profile addition forms.
 *
 * @see \Drupal\wisski_salz\Adapter\FormBase
 */
class AddForm extends FormBase {
  
   
  public function buildForm(array $form, FormStateInterface $form_state, $engine_id = NULL) {
    
    // we need to override this to catch the extra engine_id argument
    // form() is explicitly called without any supllementary args
    if ($engine_id !== NULL) {
      $this->entity->setEngineId($engine_id);
    } elseif ($this->entity->isNew()) {
      // TODO: Bad case!!! Redirect?
      throw new \LogicException("bad route?");
    }
    return parent::buildForm($form, $form_state);

  }

}
