<?php

/**
 * @file
 * Contains \Drupal\devel_a11y\Form\Settings.
 */

namespace Drupal\devel_a11y\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure devel accessibility settings for this site.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'devel_a11y_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'devel_a11y.settings',
    ];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('devel_a11y.settings');
    $form['aural'] = array(
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => t('Aural accessibility (screen readers)'),
      '#open' => TRUE,
    );
    $form['aural']['announce']['log'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log announcements (ARIA live regions)'),
      '#description' => t('Overrides <code>Drupal.announce()</code> to not only announce UI changes via ARIA live regions, but to also log these aural announcements to the browser console.'),
      '#default_value' => (int) $config->get('aural.announce.log'),
    );

    $form['keyboard'] = array(
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => t('Keyboard accessibility'),
      '#open' => TRUE,
    );
    $form['keyboard']['tabbingmanager']['log'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log tabbing manager'),
      '#description' => t('Logs when tabbing is constrained.'),
      '#default_value' => (int) $config->get('keyboard.tabbingmanager.visualize'),
    );
    $form['keyboard']['tabbingmanager']['visualize'] = array(
      '#type' => 'checkbox',
      '#title' => t('Visualize tabbing manager'),
      '#description' => t('Visually indicates which elements are reachable when the tabbing manager is active.'),
      '#default_value' => (int) $config->get('keyboard.tabbingmanager.visualize'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('devel_a11y.settings')
      ->set('aural.announce.log', (boolean) $form_state->getValue(['aural', 'announce', 'log']))
      ->set('keyboard.tabbingmanager.log', (boolean) $form_state->getValue(['keyboard', 'tabbingmanager', 'log']))
      ->set('keyboard.tabbingmanager.visualize', (boolean) $form_state->getValue(['keyboard', 'tabbingmanager', 'visualize']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
