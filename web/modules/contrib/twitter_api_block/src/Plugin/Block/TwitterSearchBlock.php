<?php

namespace Drupal\twitter_api_block\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TwitterSearchBlock' block.
 *
 * @Block(
 *   id = "twitter_search_block",
 *   admin_label = @Translation("Twitter - Search block"),
 *   category = @Translation("Content")
 * )
 */
class TwitterSearchBlock extends TwitterBlockBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form   = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Block options.
    $form['options']['search'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t("Search"),
      '#description'   => $this->t("Enter your query string (ex: #drupal or 'Drupal')."),
      '#default_value' => isset($config['options']['search']) ? $config['options']['search'] : NULL,
      '#required'      => TRUE,
    ];
    $form['options']['result_type'] = [
      '#type'          => 'select',
      '#options'       => [
        'mixed'   => $this->t('mixed'),
        'recent'  => $this->t('Recent'),
        'popular' => $this->t('Popular'),
      ],
      '#title'         => $this->t("Result type"),
      '#default_value' => isset($config['options']['result_type']) ? $config['options']['result_type'] : 'popular',
      '#required'      => TRUE,
    ];
    $form['options']['lang'] = [
      '#type'          => 'select',
      '#options'       => [
        'en' => $this->t('EN'),
        'fr' => $this->t('FR'),
        'es' => $this->t('ES'),
      ],
      '#title'         => $this->t("Language"),
      '#default_value' => isset($config['options']['lang']) ? $config['options']['lang'] : 3,
    ];
    $form['options']['geocode'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t("Geocode"),
      '#default_value' => isset($config['options']['geocode']) ? $config['options']['geocode'] : NULL,
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
    $results = $this->getTweets($this->getUrl(), $this->getParameters());
    $tweets  = isset($results['statuses']) ? $results['statuses'] : [];

    // Return empty if no tweets found.
    if (!count($tweets)) {
      return [];
    }

    // Build renderable array of oembed tweets.
    $embed           = $this->renderTweets($tweets);
    $build['tweets'] = $this->displayTweets($embed);

    // Pass search to Twig.
    $build['search'] = [
      '#type'   => 'item',
      '#markup' => $config['options']['search'],
    ];

    return $build;
  }

  /**
   * {@inheritDoc}
   */
  private function getUrl() {
    return 'https://api.twitter.com/1.1/search/tweets.json';
  }

  /**
   * {@inheritDoc}
   */
  private function getParameters() {
    $config = $this->getConfiguration();

    return UrlHelper::buildQuery([
      'q'           => $config['options']['search'],
      'count'       => $config['options']['count'],
      'result_type' => $config['options']['result_type'],
      'lang'        => $config['options']['lang'],
      'geocode'     => $config['options']['geocode'],
    ]);
  }
}
