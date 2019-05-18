<?php

namespace Drupal\parrafos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\parrafos\Helper\ParrafosHelper;

/**
 * Class AddTitleForm.
 */
class AddTitleForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_title_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $bundle_list =  \Drupal::service('parrafos.listbundles')->getBundlesWithoutField();
    
    $list = [];
    
    foreach ($bundle_list as $key => $bundle) {
      $list[$key] = $this->t($bundle['label']);
    }
  
  
    $form['paragraphs_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('paragraphs bundles'),
      '#description' => $this->t('Choose what paragraphs bundles you want to extend with parrafos'),
      '#options' => $list,
      '#default_value' => [],
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $values = array_filter($form_state->getValue('paragraphs_bundles'));
    $wadus = '';

  }

}
