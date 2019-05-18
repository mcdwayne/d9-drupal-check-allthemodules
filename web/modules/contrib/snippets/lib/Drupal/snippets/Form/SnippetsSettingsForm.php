<?php

/**
 * @file
 * Contains \Drupal\snippets\Form\SnippetsSettingsForm.
 */

namespace Drupal\snippets\Form;

use Drupal\Core\Form\ConfigFormBase;

class SnippetsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'snippets_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('snippets.settings');

    $elements = drupal_map_assoc(array('pre', 'code'));

    $form['snippets_wrapping_element'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select wrapping element'),
      '#default_value' => $config->get('element'),
      '#options' => $elements,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('snippets.settings')
        ->set('element', $form_state['values']['snippets_wrapping_element'])
        ->save();

    parent::submitForm($form, $form_state);
  }
}
