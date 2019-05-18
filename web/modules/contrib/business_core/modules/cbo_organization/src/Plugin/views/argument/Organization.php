<?php

namespace Drupal\cbo_organization\Plugin\views\argument;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\cbo_organization\OrganizationStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for organization oid.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("organization")
 */
class Organization extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * Organization storage handler.
   *
   * @var \Drupal\cbo_organization\OrganizationStorageInterface
   */
  protected $organizationStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OrganizationStorageInterface $organization_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->organizationStorage = $organization_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('organization')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $organization = $this->organizationStorage->load($this->argument);
      if (!empty($organization)) {
        return $organization->label();
      }
    }
    return $this->t('No name');
  }

}
