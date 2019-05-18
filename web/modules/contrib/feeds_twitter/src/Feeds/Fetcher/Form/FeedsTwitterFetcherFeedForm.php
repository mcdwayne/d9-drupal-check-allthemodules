<?php

namespace Drupal\feeds_twitter\Feeds\Fetcher\Form;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Utility\Feed;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Plugin\PluginAwareInterface;
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Provides a form on the feed edit page for the HttpFetcher.
 */
class FeedsTwitterFetcherFeedForm implements PluginFormInterface, PluginAwareInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The Feeds plugin.
   *
   * @var \Drupal\feeds\Plugin\Type\FeedsPluginInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setPlugin(FeedsPluginInterface $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $form['source'] = [
      '#title' => $this->t('Twitter username'),
      '#type' => 'textfield',
      '#default_value' => $feed->getSource(),
      '#maxlength' => 15,
      '#required' => TRUE,
    ];

    // @TODO: make count, replies, retweets, etc configurable

    return $form;
  }

  /**
   * {@inheritdoc}
   * Try to fetch the user's timeline and throw error if it doesn't exist
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $source = $form_state->getValue('source');
    $config = $this->plugin->getConfiguration();
    $twitter = new TwitterOAuth($config['api_key'], $config['api_secret_key'], $config['access_token'], $config['access_token_secret']);
    $test = $twitter->get('statuses/user_timeline', ['screen_name' => $source, 'count' => 1]);

    if (is_object($test) && property_exists($test, 'errors') && !empty($test->errors)) {
      $form_state->setError($form['source'], $this->t('%source is an invalid username. (%error)', ['%source' => $source, '%error' => $test->errors[0]->message]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->setSource($form_state->getValue('source'));
  }

}
