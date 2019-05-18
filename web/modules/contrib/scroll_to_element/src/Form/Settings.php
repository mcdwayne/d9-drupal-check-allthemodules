<?php

namespace Drupal\scroll_to_element\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Scroll to elements settings form
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scroll_to_element_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'scroll_to_element.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scroll_to_element.settings');

    $form['selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Identifiers'),
      '#default_value' => $config->get('selectors'),
      '#description' => $this->t(
        'The anchor names of the elements that should be scrolled to. One per line. Each line should have the format: "selector|offset|duration". Offset and duration are optional.'
      ),
    ];

    $form['default_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Default scroll offset'),
      '#default_value' => $config->get('default_offset'),
    ];

    $form['default_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Default animation duration'),
      '#default_value' => $config->get('default_duration'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('scroll_to_element.settings')
      ->set('selectors', trim($form_state->getValue('selectors')))
      ->set('default_offset', trim($form_state->getValue('default_offset')))
      ->set('default_duration', trim($form_state->getValue('default_duration')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
