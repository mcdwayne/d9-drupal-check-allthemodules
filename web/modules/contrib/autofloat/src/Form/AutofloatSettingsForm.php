<?php

namespace Drupal\autofloat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AutofloatSettingsForm.
 */
class AutofloatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autofloat_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['autofloat.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('autofloat.settings');

    $form['autofloat_start'] = [
      '#type' => 'radios',
      '#title' => $this->t('Start with the first image on the..'),
      '#options' => [
        0 => $this->t('right'),
        1 => $this->t('left (swaps "odd" and "even" classes)'),
      ],
      '#default_value' => $config->get('start'),
      '#description' => $this->t('Clear the site cache to apply changes.'),
    ];
    $form['autofloat_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use autofloat.css'),
      '#default_value' => $config->get('css'),
      '#description' => $this->t('Uncheck to take care of the floating and margins yourself in custom css.'),
    ];
    $form['target_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Selector/rejector settings'),
      '#description' => $this->t('Images will float unless they have the class "nofloat". Clear the site cache to apply changes. Avoid adding classes manually by defining classes here added by other modules/filters. Use your browser inspector to find them.'),
    ];
    $form['target_settings']['autofloat_target'] = [
      '#type' => 'radios',
      '#title' => $this->t('Elements to target'),
      '#options' => [
        'div' => $this->t('div'),
        'span' => $this->t('span'),
      ],
      '#default_value' => $config->get('target'),
      '#description' => $this->t('Clear the site cache to apply changes.'),
    ];
    $form['target_settings']['autofloat_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional selector classes to float'),
      '#default_value' => $config->get('selector'),
      '#description' => $this->t('A "selector" with the class "float" will float all containing content, e.g. the image with a caption under it. Optionally define others. Maximum two, divided by a comma. Example: "caption".'),
    ];
    $form['target_settings']['autofloat_rejector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional div classes to ignore'),
      '#default_value' => $config->get('rejector'),
      '#description' => $this->t('Images nested within any element with the class "nofloat" will NOT float, e.g. a set of thumbnails. Optionally define others. Maximum two, divided by a comma. Example: "flickr-photoset".'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Accept maximum two class value for the selector field.
    $limit = $form_state->getValue('autofloat_selector');
    if ((substr_count($limit, ',') > 1)) {
      $form_state->setErrorByName('autofloat_selector', $this->t('Not more than two values.'));
    }
    // Accept maximum two class value for the rejector field.
    $limit = $form_state->getValue('autofloat_rejector');
    if ((substr_count($limit, ',') > 1)) {
      $form_state->setErrorByName('autofloat_rejector', $this->t('Not more than two values.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('autofloat.settings');

    $config
      ->set('start', $form_state->getValue('autofloat_start'))
      ->set('css', $form_state->getValue('autofloat_css'))
      ->set('target', $form_state->getValue('autofloat_target'))
      ->set('selector', $form_state->getValue('autofloat_selector'))
      ->set('rejector', $form_state->getValue('autofloat_rejector'));

    $config->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
