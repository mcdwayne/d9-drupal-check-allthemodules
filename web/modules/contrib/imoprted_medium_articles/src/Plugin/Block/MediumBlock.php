<?php

namespace Drupal\medium_blog_articles\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Medium Blog' Block.
 *
 * @Block(
 *   id = "medium_blog_block",
 *   admin_label = @Translation("Medium Blog"),
 *   category = @Translation("System"),
 * )
 */
class MediumBlock extends BlockBase implements ContainerFactoryPluginInterface {

  private $pubUrl;
  private $countArticle;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * Constructs a new ConfigFactoryInterface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, Connection $connection, $pubUrl = '', $countArticle = '') {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
    $config = $this->config->get('medium_blog_articles.settings');
    $this->connection = $connection;
    $this->pubUrl = $config->get('medium_blog_articles.publication_name');
    $this->countArticle = $config->get('medium_blog_articles.articles_count');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $connectionContainer = $container->get('database');
    $configContainer = $container->get('config.factory');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $configContainer,
      $connectionContainer
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = $this->connection->select('medium_blog_articles', 'm');
    $query->fields('m', [
      'title',
      'post_date',
      'image',
      'url',
      'claps',
      'author',
    ]);
    $result = $query->execute();
    $blog_articles = [];
    foreach ($result as $post) {
      $blog_articles[] = [
        'title' => $post->title,
        'post_date' => $post->post_date,
        'image' => $post->image,
        'url' => $post->url,
        'claps' => $post->claps,
        'author' => $post->author,
      ];
    }
    if (!empty($this->pubUrl) && !empty($this->countArticle)) {
      $config_fields_medium = FALSE;
    }
    else {
      $config_fields_medium = TRUE;
    };
    $build[] = [
      '#theme' => 'medium_template',
      '#medium_array' => $blog_articles,
      '#config_fields_medium_array' => $config_fields_medium,
    ];
    return $build;
  }

}
