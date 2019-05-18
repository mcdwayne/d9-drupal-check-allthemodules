<?php

namespace Drupal\github_connect\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'github_connect' block.
 *
 * @Block(
 *   id = "github_connect_block",
 *   admin_label = @Translation("Github Connect"),
 * )
 */
class GithubConnectBlock extends BlockBase implements ContainerFactoryPluginInterface {
  use LinkGeneratorTrait;
  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GithubConnectBlock constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    global $base_url;

    $config = $this->configFactory->get('github_connect.settings');
    $client_id = $config->get('client_id');

    $option = [
      'query' => [
        'client_id' => $client_id,
        'scope' => 'user,public',
        'uri' => urlencode($base_url . '/github/register/create'),
      ],
    ];
    $link = Url::fromUri('https://github.com/login/oauth/authorize', $option);
    $output = $this->l($this->t('Login with GitHub'), $link);
    return array(
      '#type' => 'markup',
      '#markup' => $output,
      '#attached' => [
        'library' => ['github_connect/github_connect_icon']
      ],
      '#attributes' => array('class' => array('github-links')),
    );

  }

}
