<?php

namespace Drupal\path_file;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Path file entity entities.
 *
 * @ingroup path_file
 */
class PathFileEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
    $entity_type,
    $container->get('entity.manager')->getStorage($entity_type->id()),
    $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header = array(
      'name' => $this->t('Name'),
      'url' => array(
        'data' => $this->t('File Url'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'status' => $this->t('Status'),
      'changed' => array(
        'data' => $this->t('Updated'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\path_file\Entity\PathFileEntity */
    // display name as a link to the edit form
    $row['name'] = $this->l(
        $entity->label(),
        new Url(
            'entity.path_file_entity.edit_form', array(
              'path_file_entity' => $entity->id(),
            )
        )
    );

    // Display path, links to the file itself.
    $url = $entity->url();
    $row['url'] = $this->l(
        $url, new Url(
            'entity.path_file_entity.canonical', array(
              'path_file_entity' => $entity->id(),
            )
        )
    );

    // Show published status.
    $row['status'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');

    // Last changed date.
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');

    return $row + parent::buildRow($entity);
  }

}
