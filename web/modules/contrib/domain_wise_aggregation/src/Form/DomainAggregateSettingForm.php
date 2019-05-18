<?php

namespace Drupal\domain_wise_aggregation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;

/**
 * DomainAggregateSettingForm method.
 *
 * @package Drupal\domain_wise_aggregation\Form
 */
class DomainAggregateSettingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_aggregate_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $domain = NULL) {
    $result = [];
    $css_optimization_status = "";
    $js_optimization_status = "";
    $domain_id_value = $domain;
    $db = Database::getConnection('default');
    $query = $db->select('domain_aggregate_compress', 'aadv');
    $query->fields('aadv', ['css_optimization_status', 'js_optimization_status']);
    $query->condition('aadv.domain_id', $domain_id_value);
    $resultset = $query->execute();
    $result = $resultset->fetchAll();

    if (count($result) > 0 && !empty($result)) {
      foreach ($result as $value) {
        $css_optimization_status = $value->css_optimization_status;
        $js_optimization_status = $value->js_optimization_status;
      }
    }
    $form['contents'] = [
      'css_optimization_status' => [
        '#type' => 'checkbox',
        '#default_value' => !empty($form_state->getValue('css_optimization_status')) ? $form_state->getValue('css_optimization_status') : $css_optimization_status,
        '#title' => $this->t('Aggregate and Compress css files'),
      ],
      'js_optimization_status' => [
        '#type' => 'checkbox',
        '#default_value' => !empty($form_state->getValue('js_optimization_status')) ? $form_state->getValue('js_optimization_status') : $js_optimization_status,
        '#title' => $this->t('Aggregate and Compress js files'),
      ],
      'domain_id' => [
        '#type' => 'hidden',
        '#value' => $domain_id_value,
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'save',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db = Database::getConnection('default');
    $query = $db->select('domain_aggregate_compress', 'aadv');
    $query->fields('aadv', ['css_optimization_status', 'js_optimization_status']);
    $query->condition('aadv.domain_id', $form_state->getValue('domain_id'));
    $resultset = $query->execute();
    $result = $resultset->fetchAll();
    if (empty($result)) {
      $db = Database::getConnection('default');
      $result = $db->insert('domain_aggregate_compress')
        ->fields([
          'domain_id' => $form_state->getValue('domain_id'),
          'css_optimization_status' => $form_state->getValue('css_optimization_status'),
          'js_optimization_status' => $form_state->getValue('js_optimization_status'),
        ])
        ->execute();
    }
    else {
      $db = Database::getConnection('default');
      $result = $db->update('domain_aggregate_compress')
        ->fields([
          'domain_id' => $form_state->getValue('domain_id'),
          'css_optimization_status' => $form_state->getValue('css_optimization_status'),
          'js_optimization_status' => $form_state->getValue('js_optimization_status'),
        ])->condition('domain_id', $form_state->getValue('domain_id'))
        ->execute();
    }
  }

}
