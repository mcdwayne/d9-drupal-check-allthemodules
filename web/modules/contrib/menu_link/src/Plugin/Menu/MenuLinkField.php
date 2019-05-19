<?php

namespace Drupal\menu_link\Plugin\Menu;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a menu link plugin for field-based menu links.
 */
class MenuLinkField extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * The entity connected to this plugin instance.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repsoitory.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new MenuLinkField object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityRepository $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_manager;
    $this->entityRepository = $entity_repository;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->getProperty('title');
  }

  public function getUrlObject($title_attribute = TRUE) {
    $url = $this->getEntity()->toUrl();
    $options = $this->getOptions();
    if ($title_attribute && $description = $this->getDescription()) {
      $options['attributes']['title'] = $description;
    }
    $url->setOption('attributes', $options['attributes']);
    return $url;
  }


  /**
   * Gets a specific property.
   *
   * In case the underlying entity is translatable, we watch the translated
   * value.
   *
   * @param string $property
   *   Gets a specific property from the field, like title or weight.
   *
   * @return mixed
   *   The Property.
   */
  protected function getProperty($property) {
    // We only need to get the property from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if (!empty($this->pluginDefinition['metadata']['translatable']) && $this->languageManager->isMultilingual()) {
      /** @var \Drupal\Core\TypedData\TranslatableInterface|\Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->getEntity();
      $field_name = $this->getFieldName();
      if ($property_value = $entity->get($field_name)->$property) {
        return $property_value;
      }
      return $entity->label();
    }
    return $this->pluginDefinition[$property];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getProperty('description');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->getProperty('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    $field_name = $this->getFieldName();

    $this->pluginDefinition = $new_definition_values + $this->getPluginDefinition();
    if ($persist) {
      $updated = [];
      foreach ($new_definition_values as $key => $value) {
        $field = $this->getEntity()->{$field_name};
        if (isset($field->{$key})) {
          $field->{$key} = $value;
          $updated[] = $key;
        }
      }
      if ($updated) {
        $this->getEntity()->save();
      }
    }

    return $this->pluginDefinition;
  }

  /**
   * Loads the entity the field was attached to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns the entity, if exists.
   */
  public function getEntity() {
    if (empty($this->entity)) {
      $entity_type_id = $this->pluginDefinition['metadata']['entity_type_id'];
      $entity_id = $this->pluginDefinition['metadata']['entity_id'];
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);

      if ($entity instanceof TranslatableInterface && $this->pluginDefinition['metadata']['langcode'] !== LanguageInterface::LANGCODE_NOT_SPECIFIED && $entity->hasTranslation($this->pluginDefinition['metadata']['langcode'])) {
        $this->entity = $entity->getTranslation($this->pluginDefinition['metadata']['langcode']);
      }
      else {
        $this->entity = $this->entityRepository->getTranslationFromContext($entity);
      }
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    $entity = $this->getEntity();
    $field_name = $this->getFieldName();

    $field_item_list = $entity->get($field_name);
    $field_item_list->title = '';
    $field_item_list->description = '';
    $field_item_list->menu_name = '';
    $field_item_list->parent = '';
    $this->entity->save();
  }

  /**
   * Returns the field name.
   *
   * @return string
   *   The Field name.
   */
  protected function getFieldName() {
    return $this->getPluginDefinition()['metadata']['field_name'];
  }

}
