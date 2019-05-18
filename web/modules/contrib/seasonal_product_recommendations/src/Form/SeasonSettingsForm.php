<?php

namespace Drupal\seasonal_product_recommendations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Configure example settings for this site.
 */
class SeasonSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'seasonal_product_recommendations_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'seasonal_product_recommendations.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('seasonal_product_recommendations.settings');
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('season');
    foreach ($terms as $key => $term_name) {
      $names[$term_name->tid] = $term_name->name;
    }
    $form['hemisphere'] = [
      '#type' => 'select',
      '#title' => $this->t('Hemisphere name'),
      '#options' => [
        'Nothern' => 'Nothern',
        'Southern' => 'Southern',
        'Equator-Cancer' => 'Equator-Cancer',
        'Equater-Capricon' => 'Equater-Capricon',
      ],
    ];
    $form['season'] = [
      '#type' => 'select',
      '#title' => $this->t('Season'),
      '#options' => $names,
    ];
    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date of the season'),
    ];
    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date of the season'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $end_date = $form_state->getValue('end_date');
    $start_date = $form_state->getValue('start_date');
    if ($start_date >= $end_date) {
      $form_state->setErrorByName('end_date', $this->t('End date should be greater than the start date'));
    }
  }

  /**
   * Submits the form to the table 'hemisphere_seasons'.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->insert('hemisphere_seasons')->fields(
      [
        'hemisphere' => $form_state->getValue('hemisphere'),
        'season' => $form_state->getValue('season'),
        'start_date' => $form_state->getValue('start_date'),
        'end_date' => $form_state->getValue('end_date'),
      ]
        )->execute();
    drupal_set_message(t('Season Configurations saved.'));
  }

}
