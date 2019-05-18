<?php

/**
 * @file
 * Contains \Drupal\draggable_blocks\Form\AdminForm.
 */

namespace Drupal\draggable_blocks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminForm extends FormBase {
  
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'draggable_blocks_admin_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'draggable_blocks/draggable-blocks';
    $form['#attributes']['class'][] = 'clearfix';
    $form['blocks'] = array(
      '#type' => 'hidden',
      '#attributes' => array('id' => array('draggable-blocks-input')),
      // Use #default_value instead of #value
      // https://www.drupal.org/node/333647
      '#default_value' => t('Blocks')
    );
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    $form['cancel'] = array(
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#submit' => array('_draggable_blocks_cancel_submit'),
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $regions = json_decode($form_state->getValue('blocks'));
    _draggable_blocks_save_blocks($regions);
  }

  private function _draggable_blocks_cancel_submit() {
    location.reload();
  }

}