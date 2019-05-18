<?php

namespace Drupal\licensing\Plugin\Condition;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ctools\ConstraintConditionInterface;
use Drupal\ctools\Plugin\Condition\EntityBundle;
use Drupal\licensing\LicensingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Licensing' condition.
 *
 * @Condition(
 *   id = "license",
 *   label = "Has active license",
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node")),
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 *
 * @todo Dynamically allow this condition to inherit a context for any entity
 * type, not just entity:node.
 *
 */
class Licensing extends EntityBundle implements ConstraintConditionInterface, ContainerFactoryPluginInterface {

  protected $licensingService;

  /**
   * Creates a new EntityBundle instance.
   *
   * @param \Drupal\licensing\LicensingService $licensing_service
   *   The licensing service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(LicensingService $licensing_service, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, array $configuration, $plugin_id, $plugin_definition) {

    $this->licensingService = $licensing_service;
    parent::__construct($entity_type_manager, $entity_type_bundle_info, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('licensing.licensing'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    /** @var \Drupal\Core\Session\AccountInterface $active_user */
    $active_user = $this->getContextValue('user');

    if ($active_user->isAnonymous()) {
      return FALSE;
    }

    if ($active_user->hasPermission('administer license entities')) {
      return TRUE;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $viewed_node = $this->getContextValue('node');
    $license = licensing_load_active_user_license_for_entity($active_user, $viewed_node);

    return (bool) $license;
  }

  /**
   * Get the plugin ID.
   *
   * We are extending EntityBundle, which uses a deriver to provide an
   * EntityBundle plugin for each entity type. However, we are only interested
   * in providing a Licensing plugin for the "licensing" entity type, so we
   * override parent::getDerivativeId() and simply return the (base) plugin ID.
   *
   * @return string
   */
  public function getDerivativeId() {
    return $this->getPluginId();
  }

  /**
   * Provides the bundle label with a fallback when not defined.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type we are looking the bundle label for.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The entity bundle label or a fallback label.
   */
  protected function getEntityBundleLabel($entity_type) {

    if ($label = $entity_type->getBundleLabel()) {
      return $this->t('@label', ['@label' => $label]);
    }

    $fallback = 'Licensing';

    return $this->t('@label bundle', ['@label' => $fallback]);
  }

}

