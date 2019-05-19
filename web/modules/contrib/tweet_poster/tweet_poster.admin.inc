<?php

/**
 * @file
 * Tweet Poster admin settings page configuration.
 */

/**
 * Settings page.
 */
function tweet_poster_admin($form, &$form_state) {
  global $base_url;
  $form = array();
  $form['tweet_poster_consumer_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Twitter Consumer Key'),
    '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_consumer_key'),
  );
  $form['tweet_poster_consumer_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Twitter Consumer Secret'),
    '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_consumer_secret'),
  );
  $form['tweet_poster_callback_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Callback URL ') . $base_url . '/twittercallback)',
    '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_callback_url'),
  );
  $form['tweet_poster_tweetpic_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Tweetpic Key'),
    '#default_value' => \Drupal::config('tweet_poster.settings')->get('tweet_poster_tweetpic_key'),
  );
  return system_settings_form($form);
}
