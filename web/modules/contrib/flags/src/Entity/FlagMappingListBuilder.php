<?php


namespace Drupal\flags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a listing of flag mapping entities.
 */
class FlagMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * Array of all flags with their names.
   *
   * @var string[]
   */
  protected $flags;

  /**
   * @var string[]
   */
  protected $countries;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('country_manager')->getList(),
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
   * @param string[] $countries
   * @param string[] $flags
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, $countries, $flags) {
    parent::__construct($entity_type, $storage);
    $this->flags = $flags;
    $this->countries = $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['country'] = $this->t('Country');
    $header['flag'] = $this->t('Flag');
    $header['info'] = $this->t('Info');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Unfortunately countries are indexed with uppercase letters
    // se we make sure our ids are correct.
    /** @var FlagMapping $entity */
    $id = strtoupper($entity->getSource());

    $row['country'] = isset($this->countries[$id]) ? $this->countries[$id] : $id;
    $row['flag']['data'] = [
      '#theme' => 'flags',
      '#code' => strtolower($entity->getFlag()),
      '#source' => 'country',
    ];
    $row['info'] = $this->flags[$entity->getFlag()];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>Country to flag mapping allows you to display"
        . " flags from Flags module next to your country fields or"
        . " country select forms.</p><p>Default mappings can be changed"
        . " by adding configurations. You can also use the"
        . " 'Operations' column to edit and delete mappings.</p>"),
    );
    $build[] = parent::render();
    return $build;
  }

}
