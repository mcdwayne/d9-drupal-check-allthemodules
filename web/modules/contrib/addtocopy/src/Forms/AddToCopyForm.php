<?php

namespace Drupal\addtocopy\Forms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a configuration form for addtocopy settings.
 */
class AddToCopyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addtocopy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['addtocopy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $addtocopy_config = $this->config('addtocopy.settings');

    $form['addtocopy_selector'] = array(
      '#type' => 'textfield',
      '#title' => t('jQuery selector'),
      '#default_value' => $addtocopy_config->get('addtocopy.selector') ? $addtocopy_config->get('addtocopy.selector') : '#content',
      '#description' => t('jQuery selector expression to apply Add to Copy.'),
      '#required' => TRUE,
    );
    $form['addtocopy_minlen'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum text length'),
      '#default_value' => ($addtocopy_config->get('addtocopy.minlen')) ? $addtocopy_config->get('addtocopy.minlen') : '25',
      '#description' => t('Minimum selected text length to activate Add to Copy.'),
      '#required' => TRUE,
    );
    $form['addtocopy_htmlcopytxt'] = array(
      '#type' => 'textfield',
      '#title' => t('HTML to add to selected text'),
      '#default_value' => ($addtocopy_config->get('addtocopy.htmlcopytxt')) ? $addtocopy_config->get('addtocopy.htmlcopytxt') : '<br>More: <a href="[link]">[link]</a><br>',
      '#description' => t('[link] will be replaced with the current page link.'),
      '#required' => TRUE,
    );
    $form['addtocopy_addcopyfirst'] = array(
      '#type' => 'radios',
      '#title' => t('Add before or after selected text'),
      '#options' => array(0 => t('After'), 1 => t('Before')),
      '#default_value' => ($addtocopy_config->get('addtocopy.addcopyfirst')) ? $addtocopy_config->get('addtocopy.addcopyfirst') : '0',
      '#description' => t('jQuery selector expression to apply Add to Copy.'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for numeric min length.
    $min_length = $form_state->getValue('addtocopy_minlen');
    if (!is_numeric($min_length)) {
      // Show error for numeric value.
      $form_state->setErrorByName('addtocopy_minlen', $this->t("Please enter numeric value."));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('addtocopy.settings')
      ->set('addtocopy.selector', $form_state->getValue('addtocopy_selector'))
      ->set('addtocopy.minlen', $form_state->getValue('addtocopy_minlen'))
      ->set('addtocopy.htmlcopytxt', $form_state->getValue('addtocopy_htmlcopytxt'))
      ->set('addtocopy.addcopyfirst', $form_state->getValue('addtocopy_addcopyfirst'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
