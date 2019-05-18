<?php

namespace Drupal\astrology\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'AstrologyBlock' block.
 *
 * @Block(
 *  id = "astrology",
 *  admin_label = @Translation("Astrology"),
 * )
 */
class AstrologyBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Session\AccountInterface.
   *
   * @var account\Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor for this class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, ConfigFactoryInterface $config_factory, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->config = $config_factory;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $is_admin = $this->account->hasPermission('Administrator');
    $astrology_config = $this->config->get('astrology.settings');
    $astrology_id = $astrology_config->get('astrology');
    $formatter = $astrology_config->get('format_character');
    $query = $this->connection->select('astrology_signs', 's')
      ->fields('s')
      ->condition('s.astrology_id', $astrology_id, '=')
      ->execute();
    $query->allowRowCount = TRUE;
    $signs = NULL;
    $blank_msg = NULL;

    if ($query->rowCount()) {
      $signs = $query;
    }
    if ($is_admin) {
      $blank_msg = 'The default selected astrology does not contains any sign, please add one or more sign to display here.';
    }
    $build = [];
    $build[] = [
      '#theme' => 'astrology',
      '#formatter' => $formatter,
      '#signs' => $signs,
      '#blank_msg' => $blank_msg,
      '#attached' => [
        'library' => [
          'astrology/astrology.module',
        ],
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Set cache tag for astrology block.
    return Cache::mergeTags(parent::getCacheTags(), ['astrology_block']);
  }

}
