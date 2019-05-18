<?php

namespace Drupal\flexible_layout\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Flexible Layout settings for this site.
 */
class FlexibleLayoutSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexible_layout_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['flexible_layout.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('flexible_layout.settings');

    $form['css_grid'] = [
      '#title' => $this->t('CSS Grid Support'),
      '#type' => 'details',
      '#tree' => TRUE,
    ];

    $form['css_grid']['enabled'] = [
      '#title' => $this->t('Enable CSS Grid Support'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('css_grid.enabled'),
      '#description' => $this->t("Allows pre-defined grid layouts."),
    ];

    $form['bootstrap'] = [
      '#title' => $this->t('Bootstrap Support'),
      '#type' => 'details',
      '#tree' => TRUE,
    ];

    $form['bootstrap']['enabled'] = [
      '#title' => $this->t('Enable Bootstrap Support'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('bootstrap.enabled'),
    ];

    $form['bootstrap']['source'] = [
      '#title' => $this->t('Bootstrap Grid Source'),
      '#type' => 'textfield',
      '#description' => $this->t('Specify the absolute path to your Bootstrap Source, the default is a grid-only source.'),
      '#default_value' => $config->get('bootstrap.source'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $bootstrap = $form_state->getValue('bootstrap');
    // Validates the source URL if we've enabled Bootstrap.
    if ($bootstrap['enabled']) {
      if (!UrlHelper::isValid($bootstrap['source'], TRUE)) {
        $form_state->setErrorByName('source', $this->t("Please enter a valid URL."));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('flexible_layout.settings');
    $config
      ->set('bootstrap', $form_state->getValue('bootstrap'))
      ->set('css_grid', $form_state->getValue('css_grid'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
