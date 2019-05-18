<?php

/**
 * @file
 * Contains \Drupal\openlayers\Entity\Controller\MapListBuilder.
 */

namespace Drupal\openlayers\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for dictionary_term entity.
 *
 * @ingroup openlayers
 */
class MapListBuilder extends EntityListBuilder {

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
   * Constructs a new MapTermListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type term.
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
/*  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('Content Entity Example implements a DictionaryTerms model. These are fieldable entities. You can manage the fields on the <a href="@adminlink">Term admin page</a>.', array(
        '@adminlink' => $this->urlGenerator->generateFromRoute('entity.openlayers.map.settings'),
      )),
    );
    $build['table'] = parent::render();
    return $build;
  } */

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the openlayers_map list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['map_name'] = $this->t('Map name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openlayers\Entity\Map */
    $row['id'] = $entity->id();
    $row['map_name'] = $entity->map_name->value;
    return $row + parent::buildRow($entity);
  }

}
