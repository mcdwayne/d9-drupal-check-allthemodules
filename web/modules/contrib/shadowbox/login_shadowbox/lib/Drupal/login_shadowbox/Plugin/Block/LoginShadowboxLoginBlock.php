<?php

/**
 * @file
 * Contains \Drupal\login_shadowbox\Plugin\Block\LoginShadowboxLoginBlock.
 */

namespace Drupal\login_shadowbox\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Shadowbox Login' block.
 *
 * @Block(
 *   id = "login_shadowbox_login_block",
 *   admin_label = @Translation("Shadowbox Login"),
 *   category = @Translation("Forms")
 * )
 */
class LoginShadowboxLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a \Drupal\views\Plugin\Block\ViewsBlockBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return !(arg(0) == 'user' && !is_numeric(arg(1)));
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
      $container->get('current_user')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'login_shadowbox_login_block_visibility' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    $form['login_shadowbox_login_block_visibility'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show when logged'),
      '#default_value' => $this->configuration['login_shadowbox_login_block_visibility'],
      '#description' => t('Check this box if you want to show shadowbox login block with a logout link when user is logged.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['login_shadowbox_login_block_visibility'] = $form_state['values']['login_shadowbox_login_block_visibility'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = array();

    $user_config = $this->configFactory->get('user.settings');

    if (!$this->currentUser->id()) {
      $items[] = l(t('Login'), 'user/login', array('attributes' => array('title' => t('Login'))));
      if ($user_config->get('register') <> USER_REGISTER_ADMINISTRATORS_ONLY) {
        $items[] = l(t('Create new account'), 'user/register', array('attributes' => array('title' => t('Create a new user account.'))));
      }
      $items[] = l(t('Request new password'), 'user/password', array('attributes' => array('title' => t('Request new password via e-mail.'))));

      $block['login_shadowbox'] = array(
        '#theme' => 'item_list',
        '#items' => $items,
      );
    }
    elseif ($this->configuration['login_shadowbox_login_block_visibility']) {
      $items[] = l(t('My Account'), 'user', array('attributes' => array('title' => t('My Account'))));
      $items[] = l(t('Log out'), 'user/logout', array('attributes' => array('title' => t('Log out'))));

      $block['login_shadowbox'] = array(
        '#theme' => 'item_list',
        '#items' => $items,
      );
    }

    return $block;
  }
}
