<?php

namespace Drupal\whitelabel\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\whitelabel\WhiteLabelProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clear a white label.
 *
 * @Action(
 *   id = "clear_white_label",
 *   label = @Translation("Clear a white label"),
 *   type = "whitelabel"
 * )
 */
class ClearWhiteLabel extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The current white label.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * Constructs the set white label action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WhiteLabelProviderInterface $white_label_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->whiteLabelProvider = $white_label_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('whitelabel.whitelabel_provider'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->whiteLabelProvider->setWhiteLabel(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
