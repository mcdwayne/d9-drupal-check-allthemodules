<?php

namespace Drupal\amp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines the configuration export form.
 */
class AmpAnalyticsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amp_analytics_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amp.analytics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('amp.analytics.settings');
    $ampService = \Drupal::service('amp.utilities');

    $form['google_analytics'] = array(
      '#type' => 'details',
      '#title' => t('Google Analytics'),
      '#open' => !empty($config->get('google_analytics_id')),
    );
    $form['google_analytics']['google_analytics_id'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('google_analytics_id'),
      '#title' => $this->t('Google Analytics ID'),
      '#description' => '<p>' . $this->t('Enter a value to add the Google Analytics '.
        'code to your AMP pages. This ID is unique to each site you want to '.
        'track separately, and is in the form of UA-xxxxxxx-yy. To get a Web ' .
        'Property ID, <a href=":analytics">register your site with Google ' .
        'Analytics</a>, or if you already have registered your site, go to ' .
        'your Google Analytics Settings page to see the ID next to every site ' .
        'profile. <a href=":webpropertyid">Find more information in the ' .
        'documentation</a>.', [
          ':analytics' => 'http://www.google.com/analytics/',
          ':webpropertyid' => Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', ['fragment' => 'webProperty'])->toString()
        ]
      ) . '</p><p>' . $ampService->libraryDescription(['amp/amp.analytics']) . '</p>',
      '#maxlength' => 20,
      '#size' => 15,
      '#placeholder' => 'UA-',
    ];

    $form['gtm'] = array(
      '#type' => 'details',
      '#title' => t('Google Tag Manager'),
      '#open' => !empty($config->get('amp_gtm_id')),
    );
    $form['gtm']['amp_gtm_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The Google Tag Manager ID'),
      '#default_value' => $config->get('amp_gtm_id'),
      '#description' => '<p>' . $this->t('Enter a value to add the Google Tag Manager '.
        'code to your AMP pages. This is the Google Tag Manager ID for the ' .
        'site owner. Get this in your <a href=":url">Google Tag Manager</a> ' .
        'account. GTM has built-in AMP functionality. Go to the GTM ' .
        'administration page, enter an account (your business) and container ' .
        '(the site url), select AMP, and click Create. Pull out the '.
        'id from that code (it looks like GTM-xxxxxxx) and paste it here.', [
          ':url' => Url::fromUri('https://tagmanager.google.com')->toString()
        ]
      ) . '</p><p>' . $ampService->libraryDescription(['amp/amp.analytics']) . '</p>',
      '#maxlength' => 20,
      '#size' => 15,
      '#placeholder' => 'GTM-',
    );

    $form['pixel'] = array(
      '#type' => 'details',
      '#title' => t('amp-pixel'),
      '#open' => !empty($config->get('amp_pixel')),
    );
    $form['pixel']['amp_pixel'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable amp-pixel'),
      '#default_value' => $config->get('amp_pixel'),
      '#description' => $ampService->libraryDescription(['amp/amp.pixel']),
    );
    $form['pixel']['amp_pixel_domain_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('amp-pixel domain name'),
      '#default_value' => $config->get('amp_pixel_domain_name'),
      '#description' => $this->t('The domain name where the tracking pixel will be loaded: do not include http or https.'),
      '#states' => array('visible' => array(
        ':input[name="amp_pixel"]' => array('checked' => TRUE))
      ),
    );
    $form['pixel']['amp_pixel_query_string'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('amp-pixel query path'),
      '#default_value' => $config->get('amp_pixel_query_string'),
      '#description' => $this->t('The path at the domain where the GET request will be received, e.g. "pixel" in example.com/pixel?RANDOM.'),
      '#states' => array('visible' => array(
        ':input[name="amp_pixel"]' => array('checked' => TRUE))
      ),
    );
    $form['pixel']['amp_pixel_random_number'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Random number'),
      '#default_value' => $config->get('amp_pixel_random_number'),
      '#description' => $this->t('Use the special string RANDOM to add a random number to the URL if required. Find more information in the <a href="https://github.com/ampproject/amphtml/blob/master/spec/amp-var-substitutions.md#random">amp-pixel documentation</a>.'),
      '#states' => array('visible' => array(
        ':input[name="amp_pixel"]' => array('checked' => TRUE))
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
    // Validate the Google Analytics ID.
    if (!empty($form_state->getValue('google_analytics_id'))) {
      $form_state->setValue('google_analytics_id', trim($form_state->getValue('google_analytics_id')));
      // Replace all type of dashes (n-dash, m-dash, minus) with normal dashes.
      $form_state->setValue('google_analytics_id', str_replace(['–', '—', '−'], '-', $form_state->getValue('google_analytics_id')));
      if (!preg_match('/^UA-\d+-\d+$/', $form_state->getValue('google_analytics_id'))) {
        $form_state->setErrorByName('google_analytics_id', t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'));
      }
    }

    // Validate the Google Tag Manager ID.
    if (!empty($form_state->getValue('gtm_id'))) {
      $form_state->setValue('gtm_id', trim($form_state->getValue('gtm_id')));
      // Replace all type of dashes (n-dash, m-dash, minus) with normal dashes.
      $form_state->setValue('gtm_id', str_replace(['â€“', 'â€”', 'âˆ’'], '-', $form_state->getValue('gtm_id')));
      if (!preg_match('/^GTM-[\d\w]+$/', $form_state->getValue('gtm_id'))) {
        $form_state->setErrorByName('gtm_id', t('A valid Google Tag Manager ID is case sensitive and formatted like GTM-xxxxxxx.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('amp.analytics.settings');
    $config->set('google_analytics_id', $form_state->getValue('google_analytics_id'))->save();
    $config->set('amp_gtm_id', $form_state->getValue('amp_gtm_id'))->save();
    $config->set('amp_pixel', $form_state->getValue('amp_pixel'))->save();
    $config->set('amp_pixel_domain_name', $form_state->getValue('amp_pixel_domain_name'))->save();
    $config->set('amp_pixel_query_string', $form_state->getValue('amp_pixel_query_string'))->save();
    $config->set('amp_pixel_random_number', $form_state->getValue('amp_pixel_random_number'))->save();

    parent::submitForm($form, $form_state);
  }
}
