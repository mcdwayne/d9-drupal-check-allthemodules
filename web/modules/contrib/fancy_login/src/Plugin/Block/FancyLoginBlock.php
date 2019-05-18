<?php

namespace Drupal\fancy_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Fancy Login' block.
 *
 * @Block(
 *   id = "fancy_login_login_block",
 *   admin_label = @Translation("Fancy Login"),
 *   category = @Translation("Fancy Login Link")
 * )
 */
class FancyLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Creates a FancyLoginBlock object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->isAnonymous() || !empty($GLOBALS['menu_admin'])) {
      $url = Url::fromRoute('user.login');
      return [
        'link' => [
          '#markup' => Link::fromTextAndUrl($this->t('Login'), $url)->toString(),
          '#prefix' => '<div id="fancy_login_login_link_wrapper">',
          '#suffix' => '</div>',
        ],
      ];
    }
  }

}
