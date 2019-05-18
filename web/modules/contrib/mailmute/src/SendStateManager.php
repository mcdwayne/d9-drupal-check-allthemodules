<?php
/**
 * @file
 * Contains \Drupal\mailmute\SendStateManager.
 */

namespace Drupal\mailmute;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Service for checking whether to suppress sending mail to some address.
 *
 * @ingroup plugin
 */
class SendStateManager extends DefaultPluginManager implements SendStateManagerInterface, FallbackPluginManagerInterface {

  /**
   * The entity manager, used for finding send state fields.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Lazy-loaded send states, keyed by address.
   *
   * @todo Either actually be lazy in getState() or change this doc https://www.drupal.org/node/2379939
   *
   * @var \Drupal\mailmute\Plugin\mailmute\SendState\SendStateInterface[]
   */
  protected $states;

  /**
   * Constructs a SendStateManager object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager) {
    parent::__construct('Plugin/mailmute/SendState', $namespaces, $module_handler, '\Drupal\mailmute\Plugin\mailmute\SendState\SendStateInterface', '\Drupal\mailmute\Annotation\SendState');
    $this->setCacheBackend($cache_backend, 'mailmute_sendstate');
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($address) {
    $field = $this->getField($address);
    if (isset($field->plugin_id)) {
      $this->states[$address] = $this->createInstance($field->plugin_id, (array) $field->configuration);
      return $this->states[$address];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save($address) {
    $state = $this->states[$address];
    $this->transition($address, $state->getPluginId(), $state->getConfiguration());
  }

  /**
   * {@inheritdoc}
   */
  public function transition($address, $plugin_id, array $configuration = array()) {
    if ($field = $this->getField($address)) {
      if ($this->hasDefinition($plugin_id)) {
        $field->plugin_id = $plugin_id;
        $field->configuration = $configuration;
        $field->getEntity()->save();
      }
      else {
        throw new \InvalidArgumentException(SafeMarkup::format('Unknown state "@state"', ['@state' => $plugin_id]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isManaged($address) {
    return (bool) $this->getField($address);
  }

  /**
   * Find and return the send state field for the given email address.
   *
   * @param string $email
   *   An email address.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The send state field, or NULL if not found.
   */
  protected function getField($email) {
    foreach ($this->entityManager->getFieldMap() as $entity_type => $fields) {

      // Both users and Simplenews subscribers use 'mail' for email field name.
      if (isset($fields['sendstate']) && isset($fields['mail'])) {

        // Get the entity for the given email.
        $entities = $this->entityManager->getStorage($entity_type)->loadByProperties(array(
          'mail' => $email,
        ));
        $entity = reset($entities);

        // Return the send state field.
        if ($entity) {
          return $entity->sendstate;
        }
      }
    }

    // There may be multiple entities with the given email. Return NULL only if
    // none of them has the send state field.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'send';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginHierarchy() {
    $definitions = $this->getDefinitions();
    $hierarchy = array();

    // Move each ID from $definitions to its parent in the hierarchy. If the
    // parent is not yet added, leave it in $definitions until next iteration.
    while (!empty($definitions)) {
      $hierarchy_changed = FALSE;
      foreach ($definitions as $key => $definition) {
        if ($this->addToHierarchy($hierarchy, $definition)) {
          // Added to hierarchy, so remove from list.
          unset ($definitions[$key]);
          $hierarchy_changed = TRUE;
        }
      }
      if (!$hierarchy_changed) {
        // If there are definitions that cannot be moved because of invalid
        // parent_id, break to avoid infinite loop.
        break;
      }
    }

    return $hierarchy;
  }

  /**
   * Recursive helper method for getPluginIdHierarchy().
   */
  protected function addToHierarchy(&$array, $definition) {
    if (empty($definition['parent_id'])) {
      // Add orphans to top level.
      $array[$definition['id']] = array();
      return TRUE;
    }
    elseif (array_key_exists($definition['parent_id'], $array)) {
      // Add child to found parent.
      $array[$definition['parent_id']][$definition['id']] = array();
      return TRUE;
    }
    else {
      // Try adding to children.
      foreach ($array as &$child_array) {
        if ($this->addToHierarchy($child_array, $definition)) {
          return TRUE;
        }
      }
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginHierarchyLevels() {
    return $this->getPluginHierarchyLevelsRec($this->getPluginHierarchy(), 0);
  }

  /**
   * Recursive helper for getPluginIdsWithHierarchyLevels().
   */
  protected function getPluginHierarchyLevelsRec($hierarchy, $level) {
    $levels = array();
    foreach ($hierarchy as $id => $children) {
      $levels[$id] = $level;
      $levels = array_merge($levels, $this->getPluginHierarchyLevelsRec($children, $level + 1));
    }
    return $levels;
  }

}
