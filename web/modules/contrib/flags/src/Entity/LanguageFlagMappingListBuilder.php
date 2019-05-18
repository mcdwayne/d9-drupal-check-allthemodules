<?php


namespace Drupal\flags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\flags\Entity\FlagMapping;
use Drupal\flags\FullLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a listing of flag mapping entities.
 */
class LanguageFlagMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * Array of all flags with their names.
   *
   * @var string[]
   */
  protected $flags;

  /**
   * The configurable language manager.
   *
   * @var FullLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('flags.language_helper'),
      $container->get('flags.manager')->getList()
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\flags\FullLanguageManagerInterface $fullLanguageManager
   * @param string[] $flags
   *   Array of all available flags with their names.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FullLanguageManagerInterface $fullLanguageManager, $flags) {
    parent::__construct($entity_type, $storage);
    $this->languageManager = $fullLanguageManager;
    $this->flags = $flags;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['language'] = $this->t('Language');
    $header['flag'] = $this->t('Flag');
    $header['info'] = $this->t('Info');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var FlagMapping $entity */
    $allLanguages = $this->languageManager->getAllDefinedLanguages();
    $id = $entity->getSource();

    $row['language'] = isset($allLanguages[$id]) ? $allLanguages[$id] : $id;
    $row['flag']['data'] = [
      '#theme' => 'flags',
      '#code' => strtolower($entity->getFlag()),
      '#source' => 'language',
    ];
    $row['info'] = $this->flags[$entity->getFlag()];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>Language to flag mapping allows you to display"
        . " flags from Flags module next to your language fields, language"
        . " select form or language switcher links.</p><p>Default mappings"
        . " can be changed by adding configurations. You can also use the"
        . " 'Operations' column to edit and delete mappings.</p>"),
    );
    $build[] = parent::render();
    return $build;
  }

}
