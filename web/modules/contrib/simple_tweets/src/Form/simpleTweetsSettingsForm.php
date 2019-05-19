<?php

/**
 * @file
 * Contains \Drupal\simple_tweets\Form\simpleTweetsSettingsForm.
 */

namespace Drupal\simple_tweets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Class simpleTweetsSettingsForm
 * @package Drupal\simple_tweets\Form
 */
class simpleTweetsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_tweets_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_tweets.settings'];
  }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * @return mixed
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

    $moduleSettings = \Drupal::config('simple_tweets.settings');

    $form['simple_tweets_id'] = array(
      '#title'         => t('ID'),
      '#description'   => t("twitter.com/settings/widgets/YUOR WIDGET ID/edit"),
      '#type'          => 'textfield',
      '#default_value' => $moduleSettingsk
      ->get('simple_tweets_id'),
      '#required'      => TRUE,
      '#size' => 20,
      '#maxlength' => 20,
    );

    $form['simple_tweets_max'] = array(
      '#title'         => t('Max tweets'),
      '#description'   => t('Number between 1 and 20'),
      '#type'          => 'textfield',
      '#default_value' => $moduleSettings
      ->get('simple_tweets_max', '1'),
      '#required'      => TRUE,
      '#size' => 10,
      '#maxlength' => 2,
    );

    $form['simple_tweets_hyperlink'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable hyperlinks'),
      '#description' => t('Urls and hashtags to be hyperlinked'),
      '#default_value' => $moduleSettings
      ->get('simple_tweets_hyperlink', '0'),
    );

    $form['simple_tweets_user'] = array(
      '#type' => 'checkbox',
      '#title' => t('User'),
      '#description' => t('Show user photo / name for tweet'),
      '#default_value' => $moduleSettings
      ->get('simple_tweets_user', '0'),
    );

    $form['simple_tweets_time'] = array(
      '#type' => 'checkbox',
      '#title' => t('Time'),
      '#description' => t('Show time of tweet'),
      '#default_value' => $moduleSettings
      ->get('simple_tweets_time', '0'),
    );

    $form['simple_tweets_retweet'] = array(
      '#type' => 'checkbox',
      '#title' => t('Retweets'),
      '#description' => t('Show retweets'),
      '#default_value' => $moduleSettings
      ->get('simple_tweets_retweet', '0'),
    );

    $form['simple_tweets_interact'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable interaction'),
      '#description' => t('Show links for reply, retweet and favourite'),
      '#default_value' => $moduleSettings
      ->get('simple_tweets_interact', '0'),
    );

    $form['simple_tweets_img'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable images'),
      '#description' => t('Show images from tweet'),
      '#default_value' => $moduleSettings
      ->get('simple_tweets_img', '0'),
    );

    $form['simple_tweets_wind'] = array(
      '#type' => 'checkbox',
      '#title' => t('Links in new window'),
      '#description' => t('Open links in new widows'),
      '#default_value' => $moduleSettings
      ->get('simple_tweets_wind', '0'),
    );

    // The list of languages from twitter api v 1.1.0.
    $form['simple_tweets_lang'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#options' => array(

        'Fr' => ('French'),

        'en' => ('English'),

        'ar' => ('Arabic'),

        'ja' => ('Japanese'),

        'es' => ('Spanish'),

        'de' => ('German'),

        'it' => ('Italian'),

        'id' => ('Indonesian'),

        'pt' => ('Portuguese'),

        'ko' => ('Korean'),

        'tr' => ('Turkish'),

        'ru' => ('Russian'),

        'nl' => ('Dutch'),

        'fil' => ('Filipino'),

        'msa' => ('Malay'),

        'zh-tw' => ('Traditional Chinese'),

        'zh-cn' => ('Simplified Chinese'),

        'hi' => ('Hindi'),

        'no' => ('Norwegian'),

        'sv' => ('Swedish'),

        'fi' => ('Finnish'),

        'da' => ('Danish'),

        'pl' => ('Polish'),

        'hu' => ('Hungarian'),

        'fa' => ('Persian'),

        'he' => ('Hebrew'),

        'th' => ('Thai'),

        'uk' => ('Ukrainian'),

        'cs' => ('Czech'),

        'ro' => ('Romanian'),

        'en-gb' => ('British English'),

        'vi' => ('Vietnamese'),

        'bn' => ('Bengali'),

      ),
      '#default_value' => $moduleSettings->get('simple_tweets_lang', 'en'),
    );
    asort($form['simple_tweets_lang']['#options']);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue(['simple_tweets_id']);
    $id_pattern = '/[0-9]+$/';

    $url = 'https://cdn.syndication.twimg.com/widgets/timelines/' . $id
        . '?&lang=en&callback=twitterFetcher.callback&+suppress_response_codes=true&rnd=123';
    $client = \Drupal::httpClient();
    $res = $client->get($url, ['http_errors' => FALSE]);

    if (!@preg_match($id_pattern, $id)) {
      $form_state->setErrorByName('simple_tweets_id', t('Widget id only figures'));
    }
    elseif ($res->getStatusCode() != 200) {
      $form_state->setErrorByName('simple_tweets_id', t('Widget id is invalid'));
    }

    $maxpost_key = $form_state->getValue(['simple_tweets_max']);

    if (!is_numeric($maxpost_key) || $maxpost_key > 20 || $maxpost_key < 1) {
      $form_state->setErrorByName('simple_tweets_max', t('Only integers from 1 to 20'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('simple_tweets.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();
    entity_render_cache_clear();

    parent::submitForm($form, $form_state);
  }

}
