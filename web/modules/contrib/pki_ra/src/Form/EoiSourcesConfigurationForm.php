<?php

namespace Drupal\pki_ra\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure EOI Sources.
 */
class EoiSourcesConfigurationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pki_ra_eoi_sources_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pki_ra.settings');
    $form['table'] = array(
      '#type' => 'table',
      '#header' => [
        $this->t('EOI Source'),
        $this->t('Status'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-order-weight',
        ],
      ],
    );

    foreach (\Drupal::service('pki_ra.eoi_progress_manager')->availableEoiSources() as $key => $method) {
      if (!empty($method['label'])) {
        // TableDrag: Mark the table row as draggable.
        $form['table'][$key]['#attributes']['class'][] = 'draggable';
        $form['table'][$key]['#weight'] = $method['weight'];
        // Some table columns containing raw markup.
        $form['table'][$key]['label'] = array(
          '#plain_text' => $method['label'],
        );
        $form['table'][$key]['status'] = array(
          '#type' => 'radios',
          '#options' => $method['options'],
          '#default_value' => $config->get('eoi_sources.' . $key . '.status') ?: 'required',
        );
        $form['table'][$key]['weight'] = array(
          '#type' => 'weight',
          '#title' => t('Weight for @title', array('@title' => $method['label'])),
          '#title_display' => 'invisible',
          '#default_value' => $method['weight'],
          '#attributes' => array('class' => array('table-order-weight')),
        );
      }
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    );

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
    $config = \Drupal::service('config.factory')->getEditable('pki_ra.settings');
    $values = $form_state->getValue('table');
    $config->set('eoi_sources.order', array_keys($values));
    foreach ($values as $key => $item) {
      $config->set('eoi_sources.' . $key . '.status', $item['status']);
    }
    $config->save();
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
