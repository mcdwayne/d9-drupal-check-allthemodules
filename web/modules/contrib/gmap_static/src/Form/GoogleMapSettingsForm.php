<?php

namespace Drupal\gmap_static\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class GoogleMapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'gmap_static_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gmap_static.settings');
    $replace_iframe_to = [
      'computer' => $this->t('Desktop preferences'),
      'mobile' => $this->t('Mobile preferences'),
    ];
    $replace_iframe_options = [
      'none' => $this->t('Do nothing.'),
      'popup_map' => $this->t('Change iframe to static map. Switchable to fullscreen popup.'),
      'change_condition' => $this->t('Change iframe to static map. Switchable back to iframe.'),
      'in_new_window' => $this->t('Change iframe to static map. Display Iframe in a new window.'),
    ];
    $form['apiKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps JavaScript API Key'),
      '#default_value' => $config->get('apiKey'),
      '#description' => $this->t('You need to get api key for work this module.'),
    ];
    $table_headers = ['View Mode'] + $replace_iframe_to;
    $form['table'] = [
      '#theme' => 'table',
      '#header' => $table_headers,
    ];
    foreach ($replace_iframe_options as $option => $option_description) {
      $cell = [
        'data' => [
          '#type' => 'item',
          '#suffix' => $option_description,
          '#title' => ucfirst(str_replace('_', ' ', $option)),
        ],
      ];
      $form['table']['#rows'][$option][] = $cell;
      foreach ($replace_iframe_to as $device => $device_description) {
        $cell = [
          'data' => [
            '#type' => 'radio',
            '#name' => $device,
            '#attributes' => ['value' => $option],
          ],
        ];
        $defice_config = 'device_' . $device;
        $default_option = ($config->get($defice_config)) ? $config->get($defice_config) : 'none';
        if ($default_option == $option) {
          $cell['data']['#attributes']['checked'] = 'checked';
        }
        $form['table']['#rows'][$option][] = $cell;
      }
    }
    $form['device_list'] = [
      '#type' => 'value',
      '#value' => array_keys($replace_iframe_to),
    ];
    $form['visibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Visibility settings'),
    ];
    $options = [$this->t('All pages except those listed'), $this->t('Only the listed pages')];
    $description = 'Specify pages by using their paths. Enter one path per line. ';
    $description .= 'The "*" character is a wildcard. Example paths are %blog for ';
    $description .= 'the blog page and %blog-wildcard for every personal blog. ';
    $description .= '%front is the front page.';
    $substitute_array = [
      '%blog' => '/blog',
      '%blog-wildcard' => '/blog/*',
      '%front' => '<front>',
    ];
    // @codingStandardsIgnoreLine
    $description = t($description, $substitute_array);
    $form['visibility']['pages'] = [
      '#type' => 'textarea',
      '#default_value' => $config->get('pages'),
      '#description' => $description,
    ];
    $visibility = ($config->get('visibility')) ? $config->get('visibility') : 0;
    $form['visibility']['visibility'] = [
      '#title' => $this->t('Negate the condition'),
      '#type' => 'radios',
      '#title_display' => 'invisible',
      '#options' => [
        $this->t('Hide for the listed pages'),
        $this->t('Show for the listed pages'),
      ],
      '#default_value' => $visibility,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal ::configFactory()->getEditable('gmap_static.settings');
    $inputs = $form_state->getUserInput();
    foreach ($form_state->getValue('device_list') as $device) {
      $config->set('device_' . $device, $inputs[$device]);
    }
    $config->set('visibility', (int) $form_state->getValue('visibility'));
    $config->set('pages', trim($form_state->getValue('pages')));
    $config->set('apiKey', $form_state->getValue('apiKey'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gmap_static.settings'];
  }

}
