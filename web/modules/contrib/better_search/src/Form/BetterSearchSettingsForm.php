<?php

namespace Drupal\better_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the better_search settings form.
 */
class BetterSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['better_search.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'better_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Better Search Text Options'),
    ];

    $form['text']['placeholder_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder Text'),
      '#description' => $this->t('Enter the text to be displayed in the search field (placeholder text)'),
      '#default_value' => $this->config('better_search.settings')->get('placeholder_text'),
      '#size' => 30,
      '#maxlength' => 60,
      '#required' => TRUE,
    ];

    $form['theme'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Better Search Theme Options'),
    ];

    $options = [
      0 => $this->t('Background Fade'),
      1 => $this->t('Expand on Hover'),
      2 => $this->t('Expand Icon on Hover'),
      3 => $this->t('Slide Icon on Hover'),
    ];

    $form['theme']['theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Theme'),
      '#default_value' => $this->config('better_search.settings')->get('theme'),
      '#options' => $options,
      '#description' => $this->t('Select the theme to use for the search block.'),
    ];

    $options = [
      10 => '10',
      12 => '12',
      14 => '14',
      16 => '16',
      18 => '18',
      20 => '20',
      22 => '22',
      24 => '24',
      26 => '26',
      28 => '28',
      30 => '30',
    ];

    $form['theme']['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Box Size'),
      '#default_value' => $this->config('better_search.settings')->get('size'),
      '#options' => $options,
    ];
    $form['searchpage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Better Search options for search pages'),
    ];

    $form['searchpage']['searchpage_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Better Search on search pages'),
      '#default_value' => $this->config('better_search.settings')->get('searchpage_enable'),
      '#description' => $this->t('If true, the Drupal search pages will be altered by this module.'),
    ];

    $form['searchpage']['searchpage_submit_not_visible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the submit button on search pages'),
      '#description' => $this->t('A form_alter adds a visually-hidden class to the Submit button.'),
      '#default_value' => $this->config('better_search.settings')->get('searchpage_submit_not_visible'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('better_search.settings')->set('placeholder_text', $form_state->getValue('placeholder_text'));
    $config->set('theme', $form_state->getValue('theme'));
    $config->set('size', $form_state->getValue('size'));
    $config->set('searchpage_enable', $form_state->getValue('searchpage_enable'));
    $config->set('searchpage_submit_not_visible', $form_state->getValue('searchpage_submit_not_visible'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
