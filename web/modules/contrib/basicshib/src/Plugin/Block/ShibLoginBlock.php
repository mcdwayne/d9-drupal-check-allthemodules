<?php

namespace Drupal\basicshib\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\basicshib\AuthenticationHandler;

/**
 * Provides a 'ShibLoginBlock' block.
 *
 * @Block(
 *  id = "basicshib_login",
 *  admin_label = @Translation("Shibboleth login"),
 * )
 *
 * @todo Write tests for this block
 */
class ShibLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\basicshib\AuthenticationHandler definition.
   *
   * @var \Drupal\basicshib\AuthenticationHandler
   */
  protected $authentication_handler;

  /**
   * @var string
   */
  protected $login_link_label;

  /**
   * @var AccountProxyInterface
   */
  protected $current_user;

  /**
   * Constructs a new ShibLoginBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        AuthenticationHandler $basicshib_authentication_handler,
        ConfigFactoryInterface $config_factory,
        AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->authentication_handler = $basicshib_authentication_handler;
    $this->login_link_label = $config_factory
      ->get('basicshib.settings')
      ->get('login_link_label');

    $this->current_user = $current_user;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('basicshib.authentication_handler'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->current_user->isAuthenticated()) {
      return null;
    }

    $build = [
      '#theme' => 'basicshib_login_link',
      '#login_url' => $this->authentication_handler->getLoginUrl(),
      '#login_link_label' => $this->login_link_label,
      '#cache' => [
        'contexts' => ['url.path', 'user'],
      ],
    ];
    return $build;
  }

}
