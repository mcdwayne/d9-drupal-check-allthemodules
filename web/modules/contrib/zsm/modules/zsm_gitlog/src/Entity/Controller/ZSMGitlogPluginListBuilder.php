<?php

/**
 * @file
 * Contains \Drupal\zsm_gitlog\Entity\Controller\ZSMGitlogPluginListBuilder.
 */

namespace Drupal\zsm_gitlog\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for zsm_gitlog_plugin entity.
 *
 * @ingroup zsm_gitlog
 */
class ZSMGitlogPluginListBuilder extends EntityListBuilder {

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
     * Constructs a new ZSMGitlogPluginListBuilder object.
     *
     * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
     *   The entity type zsm_gitlog_plugin.
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
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  public function buildHeader() {
    $row['label'] = $this->t('Title');
    $row['uuid'] = $this->t('UUID');
    $row['operations'] = $this->t('Operations');
    return $row;
  }
  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'] = $entity->label();
    $row['uuid']['data'] = $entity->get('uuid')->getValue()[0]['value'];
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
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
      '#markup' => $this->t('Content Entity Example implements a ZSMCore model. These are fieldable entities. You can manage the fields on the <a href="@adminlink">ZSM Core Settings admin page</a>.', array(
        '@adminlink' => $this->urlGenerator->generateFromRoute('zsm_gitlog.zsm_gitlog_plugin_settings'),
      )),
    );
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There is no @label yet.', ['@label' => $this->entityType->getLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }

}
