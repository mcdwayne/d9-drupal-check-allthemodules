<?php

namespace Drupal\require_login_by_site\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Require Login settings for this site.
 */
class RequireLoginBySiteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'require_login_by_site_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['require_login_by_site.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('require_login_by_site.config');

    $description = $this->t('The domains to use with this filter. Enter one domain per line,');
    // Enable Sites Filter.
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Site Filter'),
      '#default_value' => $config->get('enabled'),
      '#description' => $this->t('Uncheck to disable site filter.'),
    ];
    $form['filter_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter type'),
      '#options' => [
        '1' => $this->t('Allow Anonymous Access for Domains below'),
        '0' => $this->t('Only Require Login for Domains below'),
      ],
      '#default_value' => $config->get('filter_type'),
    ];
    $form['domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Domains'),
      '#description' => $description,
      '#default_value' => $config->get('domains'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $absolutes = [];

    // Handle anonymous domains.
    $problem_domains = [];
    $domains = explode(PHP_EOL, $form_state->getValue('domains'));
    foreach ($domains as $key => $domain) {
      $domain = trim($domain);
      if (!$domain) {
        continue;
      }
      if (!preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $domain)) {
        $problem_domains[] = Html::escape($domain);
      }
      $domains[$key] = strtolower($domain);
    }
    $form_state->setValue('domains', implode(PHP_EOL, $domains));

    // Throw error if invalid domains were detected.
    if ($problem_domains) {
      $form_state->setErrorByName('domains', $this->t("Invalid Anonymous domains names found. Invalid domains:<br />:domains", [
        ':domains' => implode('<br />', $problem_domains),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('require_login_by_site.config')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('filter_type', $form_state->getValue('filter_type'))
      ->set('domains', $form_state->getValue('domains'))
      ->save();

    drupal_flush_all_caches();
    parent::submitForm($form, $form_state);
  }

}
