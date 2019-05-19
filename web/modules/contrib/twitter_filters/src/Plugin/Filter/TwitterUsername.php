<?php

namespace Drupal\twitter_filters\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\twitter_filters\TwitterTextFilters;

/**
 * Provides a filter to convert twitter-style usernames to account links.
 *
 * @Filter(
 *   id = "twitter_username",
 *   title = @Translation("Twitter @username converter"),
 *   description = @Translation("Converts Twitter-style @usernames into links to Twitter account pages."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class TwitterUsername extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * TwitterTextFilters Service.
   *
   * @var \Drupal\twitter_filters\TwitterTextFilters
   */
  private $twitterTextFilters;

  /**
   * TwitterUsername constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\twitter_filters\TwitterTextFilters $twitterTextFilters
   *   TwitterTextFilters Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TwitterTextFilters $twitterTextFilters) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->twitterTextFilters = $twitterTextFilters;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_filters.twitter_text_filters')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $prefix = '@';
    $destination = 'https://twitter.com/';
    $filtered_text = $this->twitterTextFilters->twitterFilterText($text, $prefix, $destination, 'twitter-atreply');
    return new FilterProcessResult(_filter_url($filtered_text, $this));
  }

}
