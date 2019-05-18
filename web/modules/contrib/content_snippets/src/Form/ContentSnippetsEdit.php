<?php

namespace Drupal\content_snippets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Custom Texts.
 */
class ContentSnippetsEdit extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'content_snippets_edit';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['content_snippets.content_scraps.items'];
  }

  /**
   * Settings for "Custom Text" form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('content_snippets.items')->get();
    $snippets = $this->config('content_snippets.content')->get();

    // Add form header describing purpose and use of form.
    $form['header'] = [
      '#type' => 'markup',
      '#markup' => '<h3>Set custom text used on the site.</h3><p>Configure text that appears on the site outside of specific pieces of content or blocks. Use with care: the effect is immediate.</p>',
    ];
    foreach ($config as $snip_name => $snip_type) {
      $form[$snip_name] = [
        '#title' => $snip_name,
        '#default_value' => isset($snippets[$snip_name]) ? $snippets[$snip_name] : '',
        '#type' => $snip_type,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $snippets = $this->configFactory()->getEditable('content_snippets.content');
    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $field_key => $field_value) {
      $snippets->set($field_key, $field_value);
    }
    $snippets->save();
    parent::submitForm($form, $form_state);
  }

}
