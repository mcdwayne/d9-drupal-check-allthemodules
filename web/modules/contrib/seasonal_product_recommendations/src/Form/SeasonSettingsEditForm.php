<?php

namespace Drupal\seasonal_product_recommendations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Configure example settings for this site.
 */
class SeasonSettingsEditForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'seasonal_product_recommendations_admin_settings_edit';
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

    $route_match = \Drupal::service('current_route_match');
    $hid = $route_match->getParameter('hid');

    if (isset($hid)) {
      $pager_data = \Drupal::database()->select('hemisphere_seasons', 'hs')
        ->fields('hs')
        ->condition('hid', $hid, '=');
      $pager_final_data = $pager_data->execute()->fetchAll();
      $hemisphere = $pager_final_data[0]->hemisphere;
      $season = $pager_final_data[0]->season;
      $start_date = $pager_final_data[0]->start_date;
      $end_date = $pager_final_data[0]->end_date;
    }

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('season');
    foreach ($terms as $key => $term_name) {
      $names[$term_name->tid] = $term_name->name;
    }
    $form['hemisphere'] = [
      '#type' => 'select',
      '#title' => $this->t('Hemisphere name'),
      '#options' => [
        'Northern' => 'Northern',
        'Southern' => 'Southern',
        'Equator-Cancer' => 'Equator-Cancer',
        'Equater-Capricon' => 'Equater-Capricon',
      ],
      '#default_value' => isset($hemisphere) ? $hemisphere : '',
    ];
    $form['season'] = [
      '#type' => 'select',
      '#title' => $this->t('Season'),
      '#options' => $names,
      '#default_value' => isset($season) ? $season : '',
    ];
    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date of the season'),
      '#default_value' => isset($start_date) ? $start_date : '',
    ];
    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date of the season'),
      '#default_value' => isset($end_date) ? $end_date : '',
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
    $route_match = \Drupal::service('current_route_match');
    $hid = $route_match->getParameter('hid');
    \Drupal::database()->update('hemisphere_seasons')
      ->fields(
    [
      'hemisphere' => $form_state->getValue('hemisphere'),
      'season' => $form_state->getValue('season'),
      'start_date' => $form_state->getValue('start_date'),
      'end_date' => $form_state->getValue('end_date'),
    ]
    )
      ->condition('hid', $hid, '=')
      ->execute();
    drupal_set_message(t('Season Configurations updated.'));
  }

}
