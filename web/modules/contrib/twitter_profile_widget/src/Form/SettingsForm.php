<?php

namespace Drupal\twitter_profile_widget\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\twitter_profile_widget\TwitterProfile;
use Drupal\Core\Cache\Cache;

/**
 * Class SettingsForm.
 *
 * @package Drupal\twitter_profile_widget\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_widget_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'twitter_profile_widget.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twitter_profile_widget.settings');

    $form['twitter_widget_description'] = [
      '#markup' => $this->t('Assign the Twitter App for this site. To register a new App, go to the <a href=":url">Twitter App page</a>.', [':url' => 'https://apps.twitter.com/']),
    ];
    $form['expire_internal_cache'] = [
      '#type' => 'checkbox',
      '#title' => 'Correctly expire page cache',
      '#description' => $this->t('Use this if you have the Internal Page Cache enabled and are not using a memory-based cache such as Varnish. By default, the internal (anonymous) page cache will never expire, regardless of what you have set on the <a href=":url">Performance configuration page</a>. Checking this box will set the internal page cache to expire based on the "Page cache maximum age" setting. This change <em>only</em> applies to pages that include Twitter widgets.', [':url' => '/admin/config/development/performance']),
      '#default_value' => $config->get('expire_internal_cache'),
    ];
    $form['twitter_widget_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter App Key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('twitter_widget_key'),
      '#required' => TRUE,
    ];
    $form['twitter_widget_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter App Secret'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('twitter_widget_secret'),
      '#required' => TRUE,
    ];
    $form['twitter_widget_cache_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Refresh Interval (seconds)'),
      '#default_value' => $config->get('twitter_widget_cache_time'),
      '#description' => $this->t('The Twitter <a href=":url">rate limiting policy</a> requires you limit how frequently you pull new tweets. The general rule: do not pull more frequently (in minutes) than the number of widgets should exceed the number of individual widgets on the site (e.g., if there are 5 widgets, the cache lifetime should be at least 300 seconds).', [':url' => 'https://dev.twitter.com/rest/public/rate-limits']),
    ];
    $form['twitter_widget_token'] = [
      '#type'   => 'markup',
      '#markup' => t('<strong>API Token: </strong> :status', [':status' => $config->get('twitter_widget_token')]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $twitter = new TwitterProfile();
    $connection = $twitter->checkConnection($values['twitter_widget_key'], $values['twitter_widget_secret']);
    if ($connection === FALSE) {
      $form_state->setErrorByName('twitter_widget_secret', $this->t('Cannot connect to Twitter. Please check the Twitter account and credentials.'));
    }
    if ($connection !== TRUE) {
      $form_state->setErrorByName('twitter_widget_secret', $connection . $this->t('The form has not been updated; any previously valid data you entered will remain active.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Invalidate cached Twitter data for widget views.
    Cache::invalidateTags(['twitter_widget_view']);

    $values = $form_state->getValues();
    $this->config('twitter_profile_widget.settings')
      ->set('expire_internal_cache', $values['expire_internal_cache'])
      ->set('twitter_widget_key', $values['twitter_widget_key'])
      ->set('twitter_widget_secret', $values['twitter_widget_secret'])
      ->set('twitter_widget_cache_time', $values['twitter_widget_cache_time'])
      ->set('twitter_widget_token', 'Credentials Valid')
      ->save();

    parent::submitForm($form, $form_state);
  }

}
