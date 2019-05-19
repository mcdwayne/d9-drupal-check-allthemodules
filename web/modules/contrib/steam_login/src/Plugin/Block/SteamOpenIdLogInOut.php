<?php

namespace Drupal\steam_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Steam OpenId' Block.
 *
 * @Block(
 *   id = "steam_openid",
 *   admin_label = @Translation("Steam OpenId"),
 * )
 */
class SteamOpenIdLogInOut extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Steam OpenId block constructor.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ModuleHandler $module_handler, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->modulePath = $module_handler->getModule('steam_login')->getPath();
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
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['steam_login_button'] = [
      '#type' => 'radios',
      '#options' => [
        0 => "<img src=\"/$this->modulePath/images/openid_01.png\" />",
        1 => "<img src=\"/$this->modulePath/images/openid_02.png\" />",
      ],
      '#default_value' => $this->configFactory->getEditable('steam_login.config')
        ->get('login_button'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('steam_login.config')
      ->set('login_button', $form_state->getValue('steam_login_button'))
      ->save();

    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if (!$this->account->isAuthenticated()) {
      switch ($this->configFactory->getEditable('steam_login.config')->get('login_button')) {
        case 0:
          $img = "<img src=\"/$this->modulePath/images/openid_01.png\" />";
          break;

        case 1:
          $img = "<img src=\"/$this->modulePath/images/openid_02.png\" />";
          break;

        default:
          return $build;
      }

      $build['steam_openid'] = [
        '#type' => 'link',
        '#title' => [
          '#markup' => $img,
        ],
        '#url' => Url::fromRoute('steam_login.openid'),
      ];

      return $build;
    }

    $build['account'] = [
      '#type' => 'link',
      '#title' => $this->account->getDisplayName(),
      '#url' => Url::fromRoute('entity.user.canonical', ['user' => $this->account->id()]),
    ];
    $build['separator'] = [
      '#markup' => ' | ',
    ];
    $build['logout'] = [
      '#type' => 'link',
      '#title' => 'Logout',
      '#url' => Url::fromRoute('user.logout'),
    ];

    return $build;

  }

}
