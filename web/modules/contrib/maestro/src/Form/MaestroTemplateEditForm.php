<?php

/**
 * @file
 * Contains Drupal\maestro\Form\TemplateEditForm.
 */

namespace Drupal\maestro\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class MaestroTemplateEditForm
 *
 * Provides the edit form for our Template entity.
 *
 * @package Drupal\maestro\Form
 *
 * @ingroup maestro
 */
class MaestroTemplateEditForm extends MaestroTemplateFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For the edit form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $isModal = $this->getRequest()->get('is_modal');
    
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Update Template');
    if($isModal == 'modal') {
      $actions['submit']['#ajax'] =  [
        'callback' => [$this, 'save'], 
        'wrapper' => '',
      ];
    }
    
    return $actions;
  }


  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need form the base class.
    $form['description'] = array(
      '#markup' => $this->t("Edit a Maestro Template Definition" ),
    );
    $form = parent::buildForm($form, $form_state);


    return $form;
  }
}
