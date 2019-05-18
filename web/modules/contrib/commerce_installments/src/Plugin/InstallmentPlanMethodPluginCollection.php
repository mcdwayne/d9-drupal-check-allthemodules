<?php

namespace Drupal\commerce_installments\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * A collection of installment plan method plugins.
 */
class InstallmentPlanMethodPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The installment plan method entity ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $entityId;

  /**
   * Constructs a new InstallmentPlanMethodPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $entity_id
   *   The installment plan method entity ID this plugin collection belongs to.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $entity_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->entityId = $entity_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\commerce_installments\Plugin\Commerce\InstallmentPlanMethod\InstallmentPlanMethodInterface
   *   The installment plan method plugin.
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The payment plan method '{$this->entityId}' did not specify a plugin.");
    }

    $plugin = $this->manager->createInstance($instance_id, $this->configuration);
    $this->set($instance_id, $plugin);
  }

}
