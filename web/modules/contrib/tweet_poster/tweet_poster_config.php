<?php

/**
 * @file
 * A single location to store configuration.
 */

define('TWEET_POSTER_CONSUMER_KEY', \Drupal::config('tweet_poster.settings')->get('tweet_poster_consumer_key'));
define('TWEET_POSTER_CONSUMER_SECRET', \Drupal::config('tweet_poster.settings')->get('tweet_poster_consumer_secret'));
define('TWEET_POSTER_OAUTH_CALLBACK', \Drupal::config('tweet_poster.settings')->get('tweet_poster_callback_url'));
define('TWEET_POSTER_TWEETPIC_KEY', \Drupal::config('tweet_poster.settings')->get('tweet_poster_tweetpic_key'));
