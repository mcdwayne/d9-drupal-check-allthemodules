<?php

/**
 * @file
 * Contains \Drupal\entity_expiration\Entity\Controller\EntityExpirationPolicyListBuilder.
 */

namespace Drupal\entity_expiration\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for entity_expiration_policy entity.
 *
 * @ingroup entity_expiration
 */
class EntityExpirationPolicyListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;


  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('url_generator')
    );
  }

  /**
   * Constructs a new EntityStatementListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type entity_expiration_policy.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
  }


  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => '',
    );
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the entity_expiration_policy list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Policy ID');
    $header['active'] = $this->t('Active?');
    $header['select_method'] = $this->t('Selection Method');
    $header['entity_type'] = $this->t('Statement/Data/Entity Type');
    $header['expire_method'] = $this->t('Expiration Method');
    $header['expire_age'] = $this->t('Expiration Age (seconds)');
    $header['expire_max'] = $this->t('Expiration Max Entities');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\entity_expiration\Entity\EntityExpirationPolicy */
    $row['id'] = $entity->id();
    $form_keys = array('active', 'select_method', 'entity_type', 'expire_method', 'expire_age', 'expire_max');
    $select_plugin_manager = \Drupal::service('plugin.manager.entity_expiration_method');
    $select_plugin_definitions = $select_plugin_manager->getDefinitions();
    foreach ($form_keys as $key) {
      switch ($key) {
        case 'active':
          if (isset($entity->get($key)->getValue()[0]['value'])) {
            $row[$key] = $entity->get($key)->getValue()[0]['value'] === '1' ? 'Active' : 'Inactive';
          }

          break;
        case 'select_method':
        case 'expire_method':
          $type = str_replace('_method', '', $key);
          if (isset($entity->get($key)->getValue()[0]['value'])) {
            $val = $entity->get($key)->getValue()[0]['value'];
            $row[$key] = $val;
            // If we can find the proper method name, use that instead.
            foreach ($select_plugin_definitions as $plugin => $definition) {
              foreach ($definition[$type . '_options'] as $option_key => $method) {
                if ($val === $option_key) {
                  $row[$key] = $method;
                }
              }
            }
          }
          break;
        default:
          $row[$key] = isset($entity->get($key)->getValue()[0]['value']) ? $entity->get($key)->getValue()[0]['value'] : '';
      }
    }
    return $row + parent::buildRow($entity);
  }

}
