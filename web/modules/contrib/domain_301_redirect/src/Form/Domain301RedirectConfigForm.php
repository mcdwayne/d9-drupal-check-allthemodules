<?php

namespace Drupal\domain_301_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a settings for domain_301_redirect module.
 */
class Domain301RedirectConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_301_redirect_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'domain_301_redirect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('domain_301_redirect.settings');

    $form['enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#description' => t('Enable this setting to start 301 redirects to the domain below for all other domains.'),
      '#options' => [
        1 => $this->t('Enabled'),
        0 => $this->t('Disabled'),
      ],
      '#default_value' => $config->get('enabled'),
    ];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#description' => $this->t('This is the domain that all other domains pointing to this site will be 301 redirected to. This value should also include the scheme such as http or https but will redirect to http if not specified. Example: http://www.testsite.com'),
      '#default_value' => $config->get('domain'),
    ];

    // Per path configuration settings to apply the redirect to specific paths.
    $form['applicability'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pages'),
      '#open' => TRUE,
    ];

    $form['applicability']['pages'] = [
      '#type' => 'textarea',
      '#default_value' => $config->get('pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
        '%blog' => '/blog',
        '%blog-wildcard' => '/blog/*',
        '%front' => '<front>',
      ]),
    ];

    $form['applicability']['applicability'] = [
      '#type' => 'radios',
      '#options' => [
        DOMAIN_301_REDIRECT_EXCLUDE_METHOD => $this->t('Do not redirect for the listed pages'),
        DOMAIN_301_REDIRECT_INCLUDE_METHOD => $this->t('Only redirect for the listed pages'),
      ],
      '#default_value' => $config->get('applicability'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('domain_301_redirect.settings');

    $values = $form_state->getValues();
    if (!empty($values['enabled'])) {
      $domain = trim($values['domain']);

      if (!preg_match('|^https?://|', $domain)) {
        $domain = 'http://' . $domain;
      }
      if (!UrlHelper::isValid($domain, TRUE)) {
        $form_state->setErrorByName('domain', $this->t('Domain 301 redirection can not be enabled if a valid domain is not set.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('domain_301_redirect.settings')
      ->set('enabled', $values['enabled'])
      ->set('domain', $values['domain'])
      ->set('applicability', $values['applicability'])
      ->set('pages', $values['pages'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
