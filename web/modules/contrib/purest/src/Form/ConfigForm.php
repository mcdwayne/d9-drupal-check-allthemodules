<?php

namespace Drupal\purest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DevelopmentSettingsForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'purest.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purest_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('purest.settings');

    $form['front_end'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Front End Settings'),
    ];

    $form['front_end']['front_end_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Front End URL'),
      '#description' => $this->t('The URL of the front end application'),
      '#default_value' => $config->get('front_end_url'),
    ];

    $form['api_url'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Rest Resources Path Prefix'),
    ];

    $form['api_url']['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path Prefix'),
      '#description' => $this->t('Change the default prefix for Purest resources from the default of "purest"'),
      '#default_value' => $config->get('prefix'),
      '#element_validate' => [
        [$this, 'validatePath'],
      ],
    ];

    $form['normalizers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Normalization'),
    ];

    $form['normalizers']['normalize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Purest Typed Data Normalizer'),
      '#description' => $this->t('Purest includes a typed data normalizer. It simplifies the structures returned for nodes and taxonomy terms in rest responses.'),
      '#default_value' => $config->get('normalize'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validates the path.
   */
  public function validatePath(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#value'])) {
      $parts = explode('/', trim($element['#value'], '/'));
      $value = [];

      foreach ($parts as $key => $part) {
        if (!$part) {
          unset($parts[$key]);
        }
        elseif (preg_match('/[^a-z_\-0-9]/i', $part)) {
          $form_state->setErrorByName('prefix', t('URL prefix can only contain letters, numbers, underscores and dashes.'));
          return;
        }
      }

      // Ensure trailing slash.
      $clean_path = '/' . implode('/', $parts);

      $route_provider = \Drupal::service('router.route_provider');
      $found_routes = $route_provider->getRoutesByPattern($clean_path . '/{menu}');
      $route_iterator = $found_routes->getIterator();

      if (count($route_iterator)) {
        $form_state->setErrorByName('prefix', t('The chosen URL prefix causes clashes with one or more existing routes.'));
      }
      else {
        $form_state->setValue('prefix', $clean_path);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('purest.settings');
    $config->set('front_end_url', $form_state->getValue('front_end_url'));
    $config->set('normalize', $form_state->getValue('normalize'));
    $config->set('prefix', $form_state->getValue('prefix'));
    $config->save();
    drupal_flush_all_caches();
  }

}
