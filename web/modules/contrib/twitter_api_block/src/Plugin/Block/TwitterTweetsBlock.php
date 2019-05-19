<?php

namespace Drupal\twitter_api_block\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TwitterTweetsBlock' block.
 *
 * @Block(
 *   id = "twitter_tweets_block",
 *   admin_label = @Translation("Twitter - Tweets block"),
 *   category = @Translation("Content")
 * )
 */
class TwitterTweetsBlock extends TwitterBlockBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form   = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['options']['username'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t("Username"),
      '#default_value' => isset($config['options']['username']) ? $config['options']['username'] : NULL,
      '#required'      => TRUE,
    ];
    $form['options']['replies'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t("Include replies"),
      '#default_value' => isset($config['options']['replies']) ? $config['options']['replies'] : 0,
    ];
    $form['options']['retweets'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t("Include retweets"),
      '#default_value' => isset($config['options']['retweets']) ? $config['options']['retweets'] : 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build  = parent::build();
    $config = $this->getConfiguration();

    if (!$this->hasCredentials()) {
      return [];
    }

    // Get latest tweets.
    $tweets = $this->getTweets($this->getUrl(), $this->getParameters());

    // Return empty if no tweets found.
    if (!count($tweets)) {
      return [];
    }

    // Build renderable array of oembed tweets.
    $embed           = $this->renderTweets($tweets);
    $build['tweets'] = $this->displayTweets($embed);

    // Pass account name to Twig.
    $build['username'] = [
      '#type'   => 'item',
      '#markup' => $config['options']['username'],
    ];

    return $build;
  }

  /**
   * {@inheritDoc}
   */
  private function getUrl() {
    return 'https://api.twitter.com/1.1/statuses/user_timeline.json';
  }

  /**
   * {@inheritDoc}
   */
  private function getParameters() {
    $config = $this->getConfiguration();
    return UrlHelper::buildQuery([
      'screen_name'     => $config['options']['username'],
      'count'           => $config['options']['count'],
      // Reverse exclude replies value on purpose.
      'exclude_replies' => ($config['options']['replies']) ? 'false' : 'true',
      'include_rts'     => $config['options']['retweets'] ? 'true' : 'false',
    ]);
  }
}
