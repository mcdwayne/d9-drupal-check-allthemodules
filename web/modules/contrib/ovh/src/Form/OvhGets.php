<?php

namespace Drupal\ovh\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ovh\OvhHelper;
use Drupal\ovh\Entity\OvhKey;

/**
 * Test or Read from OVH API using GET method.
 */
class OvhGets extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ovh_gets';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $entities = OvhKey::loadMultiple();
    $ovhkeys = [];
    foreach ($entities as $key => $entity) {
      $ovhkeys[$entity->id()] = $entity->label();
    }

    // Global.
    $form['ovhkey'] = [
      '#type' => 'select',
      '#title' => 'Ovh Key',
      '#options' => $ovhkeys,
      '#default_value' => $form_state->getValue('ovhkey'),
    ];
    $form['input'] = [
      '#type' => 'textfield',
      '#title' => 'Input',
      '#default_value' => $form_state->getValue('input', "/"),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = trim($form_state->getValue('input'));
    $ovhkey = $form_state->getValue('ovhkey');
    drupal_set_message("Input : " . $input);
    drupal_set_message("Key ID : " . $ovhkey);

    try {
      $result = OvhHelper::ovhGet($input, $ovhkey);
      if (function_exists('kint')) {
        kint($result);
      }
      elseif (function_exists('dpm')) {
        dpm($result);
      }
      else {
        drupal_set_message(json_encode($result));
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    $form_state->setRebuild(TRUE);
  }

}
