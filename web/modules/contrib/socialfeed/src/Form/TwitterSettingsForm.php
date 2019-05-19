<?php

namespace Drupal\socialfeed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TwitterSettingsForm.
 *
 * @package Drupal\socialfeed\Form
 */
class TwitterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'socialfeed.twittersettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.twittersettings');
    $form['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Consumer Key'),
      '#default_value' => $config->get('consumer_key'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Consumer Secret'),
      '#default_value' => $config->get('consumer_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Access Token'),
      '#default_value' => $config->get('access_token'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Access Token Secret'),
      '#default_value' => $config->get('access_token_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['tweets_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Tweet Count'),
      '#default_value' => $config->get('tweets_count'),
      '#size' => 60,
      '#maxlength' => 100,
      '#min' => 1,
    ];
    // @todo: Move these to the block form; Update theme implementation.
    $form['hashtag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Hashtag'),
      '#default_value' => $config->get('hashtag'),
    ];
    $form['time_stamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Date/Time'),
      '#default_value' => $config->get('time_stamp'),
    ];
    $form['time_ago'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Twitter Style Date'),
      '#default_value' => $config->get('time_ago'),
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/Time Format'),
      '#default_value' => $config->get('time_format'),
      '#description' => $this->t('You can check for PHP Date Formats <a href="@datetime" target="@blank">here</a>', [
        '@datetime' => 'http://php.net/manual/en/function.date.php',
        '@blank' => '_blank',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
          ':input[name="time_ago"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim Length'),
      '#default_value' => $config->get('trim_length'),
      '#size' => 60,
      '#maxlength' => 280,
    ];
    $form['teaser_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Teaser Text'),
      '#default_value' => $config->get('teaser_text'),
      '#size' => 60,
      '#maxlength' => 60,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.twittersettings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
