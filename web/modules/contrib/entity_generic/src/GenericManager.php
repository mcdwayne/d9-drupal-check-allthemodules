<?php

namespace Drupal\entity_generic;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity manager service.
 */
abstract class GenericManager implements GenericManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_generic';

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $entity_type_manager->getStorage($this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAll(array $ids = NULL) {
    return $this->entityStorage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailable(AccountInterface $user) {
    $available = [];

    $entities = $this->entityStorage->loadMultiple();
    foreach ($entities as $entity) {
      $available[$entity->id()] = $entity->label();
    }

    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableOptions(AccountInterface $user) {
    $available = [];

    $entities = $this->entityStorage->loadMultiple();
    foreach ($entities as $entity) {
      $available[$entity->id()] = $entity->label();
    }

    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableOptionsUuid(AccountInterface $user) {
    $available = [];

    $entities = $this->entityStorage->loadMultiple();
    foreach ($entities as $entity) {
      $available[$entity->uuid()] = $entity->label();
    }

    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getByField($field_name, $field_value) {
     $ids = \Drupal::entityQuery($this->entityTypeId)
       ->condition($field_name, $field_value)
       ->sort('id', 'ASC')
       ->execute();

    if (!empty($ids)) {
      $entities = $this->entityStorage->loadMultiple($ids);

      if (count($entities) == 1) {
        $entity = reset($entities);
        return $entity;
      }
      else {
        return $entities;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddLinkModal(array $args = []) {
    $add_entity_url = NULL;
    if ($route = \Drupal::service('router')->getRouteCollection()->get('entity.' . $this->entityTypeId . '.add_modal_form')) {
      // Create URL to add form.
      $options = $route->getOptions();
      $parameters = isset($options['parameters']) ? $options['parameters'] : [];
      $url_options = [];
      foreach ($parameters as $parameter_name => $parameter) {
        $url_options[$parameter_name] = isset($args[$parameter_name]) ? $args[$parameter_name] : NULL;
        unset($args[$parameter_name]);
      }
      foreach ($args as $arg_name => $arg) {
        if ($arg instanceof EntityInterface) {
          $url_options[$arg_name] = $arg->id();
        }
      }
      $add_entity_url = Url::fromRoute('entity.' . $this->entityTypeId . '.add_modal_form', $url_options);
    }
    return $add_entity_url;
  }

  /**
   * {@inheritdoc}
   */
  public function generateAddLinkModal(array $args = []) {
    $add_entity_url = $this->getAddLinkModal($args);
    $link_array = Link::fromTextAndUrl($this->t('Add new entity'), $add_entity_url)->toRenderable();
    $link_array['#attributes'] = ['class' => ['button', 'button-action', 'button--primary', 'button--small']];
    // Add AJAX handling for the button.
    $link_array['#attributes']['class'][] = 'use-ajax';
    $link_array['#attributes']['data-dialog-type'] = 'modal';
    $width = isset($args['width']) ? $args['width'] : 800;
    $height = isset($args['height']) ? $args['height'] : 500;
    $link_array['#attributes']['data-dialog-options'] = "{'width':" . $width . ",'height':" . $height . "}";
    return $link_array;
  }

}
