<?php

namespace Drupal\panels_breadcrumbs\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PageVariantBreadcrumbsForm.
 */
class PageVariantBreadcrumbsForm extends FormBase {

  use PageBreadcrumbsFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_breadcrumbs_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $page_variant = $cached_values['page_variant'];
    $variant_settings = $page_variant->get('variant_settings');
    $breadcrumbs_settings = isset($variant_settings['panels_breadcrumbs']) ? $variant_settings['panels_breadcrumbs'] : [];

    $form['state'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom breadcrumb configuration.'),
      '#default_value' => isset($breadcrumbs_settings['state']) ? $breadcrumbs_settings['state'] : FALSE,
    ];
    $form['titles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Breadcrumb titles'),
      '#description' => $this->t('Enter one title per line.'),
      '#default_value' => isset($breadcrumbs_settings['titles']) ? $breadcrumbs_settings['titles'] : '',
    ];
    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Breadcrumb paths'),
      '#description' => $this->t('Enter one path per line. You can use @front to link
      to the front page, or @nolink for no link.', ['@front' => '<front>', '@nolink' => '<nolink>']),
      '#default_value' => isset($breadcrumbs_settings['paths']) ? $breadcrumbs_settings['paths'] : '',
    ];
    $form['home'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prepend Home Link to the Breadcrumb'),
      '#default_value' => isset($breadcrumbs_settings['home']) ? $breadcrumbs_settings['home'] : FALSE,
    ];
    $form['home_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Home link title'),
      '#description' => $this->t('Text will be displayed as Home link title in the breadcrumb'),
      '#default_value' => isset($breadcrumbs_settings['home_text']) ? $breadcrumbs_settings['home_text'] : $this->t('Home'),
      '#states' => [
        'visible' => [
          ':input[name="home"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if ($token_types = $this->getTypesOfTokens($page_variant)) {
      $form['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
        '#global_types' => FALSE,
        '#dialog' => TRUE,
        '#click_insert' => FALSE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $page_variant = $cached_values['page_variant'];
    $variant_settings = $page_variant->get('variant_settings');
    $submitted_values = $form_state->getValues();

    foreach ($this->getSettingsKeys() as $name) {
      $variant_settings['panels_breadcrumbs'][$name] = $submitted_values[$name];
    }
    $page_variant->set('variant_settings', $variant_settings);

    // Invalidate breadcrumbs block cache.
    $theme_name = \Drupal::config('system.theme')->get('default');
    Cache::invalidateTags(["config:block.block.{$theme_name}_breadcrumbs"]);
  }

}
