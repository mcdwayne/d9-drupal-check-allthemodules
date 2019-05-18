<?php

namespace Drupal\pixelpin_openid_connect\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager;

/**
 * Provides a 'OpenID Connect login' block.
 *
 * @Block(
 *  id = "pixelpin_openid_connect_login",
 *  admin_label = @Translation("Log In Using PixelPin"),
 * )
 */
class LoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager definition.
   *
   * @var \Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The OpenID Connect client manager.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      OpenIDConnectClientManager $plugin_manager,
      FormBuilder $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->pluginManager = $plugin_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.pixelpin_openid_connect_client.processor'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm('Drupal\pixelpin_openid_connect\Form\LoginForm');
  }

}
