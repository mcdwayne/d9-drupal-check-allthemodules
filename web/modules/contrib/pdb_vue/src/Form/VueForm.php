<?php

namespace Drupal\pdb_vue\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VueForm.
 *
 * @package Drupal\pdb_vue\Form
 */
class VueForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pdb_vue.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pdb_vue_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pdb_vue.settings');
    $form['development_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Development Mode'),
      '#description' => $this->t('Checking the box enables development mode'),
      '#default_value' => $config->get('development_mode'),
    ];

    $form['use_spa'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Vue components in a Single Page App format.'),
      '#description' => $this->t('Checking the box will initialize a Vue instance on a root element to overtake the entire page allowing each block to be a <a href="https://vuejs.org/v2/guide/components.html">Vue Components</a>.'),
      '#default_value' => $config->get('use_spa'),
    ];

    $form['spa_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Element Selector'),
      '#description' => $this->t('Set the element that the Vue instance will be attached to. By default it will use the Classy theme\'s wrapping element "#page-wrapper".'),
      '#default_value' => ($config->get('spa_element')) ? $config->get('spa_element') : '#page-wrapper',
      '#states' => [
        // Only show this field when the use_spa checkbox is checked.
        'visible' => [
          ':input[name="use_spa"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Get the config object.
    $config = $this->configFactory()->getEditable('pdb_vue.settings');
    // Set the values the user submitted in the form.
    $config->set('development_mode', $form_state->getValue('development_mode'));
    $config->set('use_spa', $form_state->getValue('use_spa'));
    $config->set('spa_element', $form_state->getValue('spa_element'));
    $config->save();

    // Clear caches so that it will pick up the changes to vue library.
    $this->flushCaches();
  }

  /**
   * Clear all caches.
   */
  public function flushCaches() {
    drupal_flush_all_caches();
  }

}
