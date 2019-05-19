<?php

namespace Drupal\social_stats\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Url;
use Drupal\social_stats\SocialStatsFbManager;
use Drupal\social_stats\SocialStatsGplusManager;
use Drupal\social_stats\SocialStatsLinkedinManager;

/**
 * Update Social stats for nodes.
 *
 * @QueueWorker(
 *   id = "social_stats_updater",
 *   title = @Translation("Social stats update"),
 *   cron = {"time" = 300}
 * )
 */
class SocialStatsUpdater extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection from which to read route information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new SocialStatsUpdater object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($result) {
    $facebook_total = 0;
    $linkedin_total = 0;
    $google_plusone = 0;
    global $base_url;

    $variable = ($this->configFactory->get('social_stats.settings')
      ->get('social_stats.content.types.' . $result->type));

    // Create absolute node path using the node path.
    $config = $this->configFactory->get('social_stats.cron_settings');
    if ($config->get('social_stats_url_root')) {
      $url_root = $config->get('social_stats_url_root');
    }
    else {
      $url_root = $base_url;
    }

    $node_path = $url_root . Url::fromRoute('entity.node.canonical', array('node' => $result->nid))->toString();

    // Getting data from Facebook for nodes of the selected node type.
    if ($variable['fb']) {
      $fb_baseurl = 'https://graph.facebook.com/fql?';
      $FbStatsManager = new SocialStatsFbManager($fb_baseurl, $node_path, $result->nid, 'get');
      $facebook_total = $FbStatsManager->execute();
    }

    // Getting data from LinkedIn for nodes of selected node type.
    if ($variable['linkedin']) {
      $linkedin_baseurl = 'http://www.linkedin.com/countserv/count/share?format=json&';
      $LinkedinStatsManager = new SocialStatsLinkedinManager($linkedin_baseurl, $node_path, $result->nid, 'get');
      $linkedin_total = $LinkedinStatsManager->execute();
    }

    // Getting data from Google Plus for nodes of selected node type.
    if ($variable['gplus']) {
      $google_plusone = new SocialStatsGplusManager($node_path, $result->nid);
    }

    $count_total = $linkedin_total + $google_plusone + $facebook_total;

    // Adding the total data from all above to get total virality.
    // Update table only when counter > 0
    if ($count_total) {
      $this->connection->merge('social_stats_total')
        ->key(array('nid' => $result->nid))
        ->fields(array('total' => $count_total))
        ->execute();
    }
  }
}
