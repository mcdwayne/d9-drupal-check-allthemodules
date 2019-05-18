<?php

namespace Drupal\eid_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ID-Card/Mobile-ID login block.
 *
 * @Block(
 *   id = "eid_auth_login_block",
 *   admin_label = @Translation("ID-Card/Mobile-ID login")
 * )
 */
class EidAuthLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * EidAuthLoginBlock constructor.
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
    $config = $this->configFactory->get('eid_auth.settings');
    $enabled_methods = $config->get('enabled_auth_methods');
    $eid_form = NULL;
    $eid_smart_id_form = NULL;

    if ($enabled_methods['mobile_id'] === 'mobile_id') {
      $eid_form = $this->formBuilder->getForm('\Drupal\eid_auth\Form\EidLoginForm');
    }

    if ($enabled_methods['smart_id'] === 'smart_id') {
      $eid_smart_id_form = $this->formBuilder->getForm('\Drupal\eid_auth\Form\EidSmartIdLoginForm');
    }

    if (empty($enabled_methods) ||
      (empty($enabled_methods['id_card']) &&
        empty($enabled_methods['mobile_id']) &&
        empty($enabled_methods['smart_id']))) {
      return [];
    }

    return [
      '#theme' => 'eid_auth_login_content',
      '#forms' => [
        'eid_auth_form' => $eid_form,
        'eid_auth_smart_id_form' => $eid_smart_id_form,
      ],
      '#enabled_methods' => $enabled_methods,
      '#cache' => [
        'tags' => ['config:eid_auth.settings'],
      ],
    ];
  }

}
