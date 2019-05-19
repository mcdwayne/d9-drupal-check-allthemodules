<?php

/**
 * @file
 * Contains \Drupal\tweet_poster\Form\TweetPosterAdmin.
 */

namespace Drupal\tweet_poster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class TweetPosterAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tweet_poster_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tweet_poster.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tweet_poster.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface &$form_state) {
   /* global $base_url;
    $form = [];
    $form['tweet_poster_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => t('Twitter Consumer Key'),
      '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_consumer_key'),
    ];
    $form['tweet_poster_consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Twitter Consumer Secret'),
      '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_consumer_secret'),
    ];
    $form['tweet_poster_callback_url'] = [
      '#type' => 'textfield',
      '#title' => t('Callback URL ') . $base_url . '/twittercallback)',
      '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_callback_url'),
    ];
    $form['tweet_poster_tweetpic_key'] = [
      '#type' => 'textfield',
      '#title' => t('Tweetpic Key'),
      '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_tweetpic_key'),
    ];
    return parent::buildForm($form, $form_state); */
  } 

}
