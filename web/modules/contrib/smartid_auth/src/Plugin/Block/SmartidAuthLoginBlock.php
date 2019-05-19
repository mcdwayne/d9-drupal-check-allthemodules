<?php

namespace Drupal\smartid_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ID-Card/Mobile-ID login block.
 *
 * @Block(
 *   id = "smartid_auth_login_block",
 *   admin_label = @Translation("ID-Card/Mobile-ID login")
 * )
 */
class SmartidAuthLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SmartidAuthLoginBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              FormBuilderInterface $form_builder,
                              ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $form_builder;
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
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('smartid_auth.settings');
    $login_redirect = $config->get('login_redirect');
    $client_id = $config->get('client_id');

    $smartid_form = NULL;
    $smartid_smart_id_form = NULL;

    // https://smartid.ee/how/
    return [
      '#theme' => 'smartid_auth_login_content',
      '#forms' => [
        'smartid_auth_form' => $smartid_form,
        'smartid_auth_smart_id_form' => $smartid_smart_id_form,
      ],
      '#client_id' => $client_id,
      '#login_redirect' => $login_redirect,
      '#cache' => [
        'tags' => ['config:smartid_auth.settings'],
      ],
    ];
  }

}
