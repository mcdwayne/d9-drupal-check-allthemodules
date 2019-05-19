<?php

/**
 * @file
 * Contains \Drupal\smartling\SubmissionListBuilder.
 */

namespace Drupal\smartling;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of submission entities.
 *
 * @see \Drupal\smartling\Entity\SmartlingSubmission
 */
class SubmissionListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'entity_id' => $this->t('Related entity'),
      'title' => $this->t('Title'),
      'entity_type' => [
        'data' => $this->t('Entity type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'entity_bundle' => [
        'data' => $this->t('Bundle'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'target_language' => $this->t('Language'),
      'submitter' => [
        'data' => $this->t('Author'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'progress' => $this->t('Status'),
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\smartling\SmartlingSubmissionInterface $entity */
    $related_entity = $entity->getRelatedEntity();
    $entity_type = $related_entity->getEntityType();
    $row = [
      'entity_id' => $entity->get('entity_id')->value,
      'title' => ['data' => [
        '#type' => 'link',
        '#title' => $entity->label(),
        '#url' => $entity->urlInfo(),
      ]],
      'entity_type' => $entity_type->getLabel(),
      'entity_bundle' => $entity_type->getBundleLabel(),
      'target_language' => $entity->get('target_language')->language->getName(),
      // @todo Convert submitter into entity reference to user entity.
      'submitter' => $entity->get('submitter')->value,
      // @todo Render progress properly.
      'progress' => $entity->get('progress')->value,
      'created' => $this->dateFormatter->format($entity->get('created')->value, 'short')
    ];
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
