<?php

namespace Drupal\dirtyform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DirtyFormSettingsForm.
 *
 * @package Drupal\dirtyform\Form
 */
class DirtyFormSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dirtyform_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['dirtyform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_value = implode("\n", dirtyform_form_id_whitelist());
    $form['form_id_whitelist'] = [
      '#type' => 'textarea',
      '#title' => t('Enabled Form IDs'),
      '#description' => t('One per line.'),
      '#default_value' => $default_value,
    ];

    $default_value = implode("\n", dirtyform_form_id_blacklist());
    $form['form_id_blacklist'] = [
      '#type' => 'textarea',
      '#title' => t('Disabled Form IDs'),
      '#description' => t('One per line.'),
      '#default_value' => $default_value,
    ];

    $default_value = implode("\n", dirtyform_form_id_regex());
    $form['form_id_regex'] = [
      '#type' => 'textarea',
      '#title' => t('Form ID Regular Expressions'),
      '#description' => t('One per line, including delimeters, e.g. <code>/.*_node_form/</code>.'),
      '#default_value' => $default_value,
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $lines = array_filter(
      preg_split('/[\n\r]+/', $form_state->getValue('form_id_regex'))
    );

    array_map(function ($line) use ($form_state) {
      if (preg_match($line, '') === FALSE) {
        $form_state->setErrorByName('form_id_regex',
          t('The line %line could not be parsed.', ['%line' => $line]));
      }
    }, $lines);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    dirtyform_form_id_whitelist(array_filter(
      preg_split('/[\n\r]+/', $form_state->getValue('form_id_whitelist'))
    ));
    dirtyform_form_id_blacklist(array_filter(
      preg_split('/[\n\r]+/', $form_state->getValue('form_id_blacklist'))
    ));
    dirtyform_form_id_regex(array_filter(
      preg_split('/[\n\r]+/', $form_state->getValue('form_id_regex'))
    ));

    parent::submitForm($form, $form_state);
  }

}
