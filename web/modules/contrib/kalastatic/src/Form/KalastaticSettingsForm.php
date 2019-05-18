<?php

namespace Drupal\kalastatic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure example settings for this site.
 */
class KalastaticSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kalastic_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'kalastatic.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = kalastatic_get_settings();
    $config = $this->config('kalastatic.settings');
    $github_url = 'https://github.com/kalamuna/kalastatic';
    $github_link = Link::fromTextAndUrl($github_url, Url::fromUri($github_url))->toString();

    $form = [
      'description' => [
        '#markup' => '<p>' . t('Static site framework for building out prototypes and styleguides. See @link for more details.', ['@link' => $github_link]) . '</p>',
      ],
      'kalastatic_src_path_wrap' => [
        '#type' => 'fieldset',
        '#title' => t('Kalastatic'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'kalastatic_src_path' => [
          '#prefix' => '<h3>' . $this->t('Source Path') . ':</h3>',
          '#markup' => '<pre>' . $settings['source'] . '</pre>',
        ],
        'kalastatic_build_path' => [
          '#prefix' => '<h3>' . $this->t('Build Path') . ':</h3>',
          '#markup' => '<pre>' . $settings['destination'] . '</pre>',
        ],
      ],
      'kalastatic_brand_color' => [
        '#type' => 'textfield',
        '#title' => $this->t('Brand color'),
        '#default_value' => $config->get('kalastatic_brand_color'),
        '#size' => 60,
        '#description' => t("Provide the hex brand color for the site. Useful for favicons and mobile icon homescreens."),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('kalastatic.settings');
    $config->set('kalastatic_brand_color', $form_state->getValue('kalastatic_brand_color'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
