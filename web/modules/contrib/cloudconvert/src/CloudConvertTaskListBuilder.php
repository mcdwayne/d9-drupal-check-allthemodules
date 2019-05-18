<?php

namespace Drupal\cloudconvert;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of CloudConvert Task entities.
 *
 * @ingroup cloudconvert
 */
class CloudConvertTaskListBuilder extends EntityListBuilder {

  /**
   * DateFormatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $dateFormatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Task ID');
    $header['original_file_id'] = $this->t('File ID');
    $header['original_file'] = $this->t('File');
    $header['process_id'] = $this->t('Process ID');
    $header['step'] = $this->t('Step');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Changed');
    $header['user'] = $this->t('User');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cloudconvert\Entity\CloudConvertTask */
    $row['id'] = $entity->id();
    $row['original_file_id'] = $entity->getOriginalFile()->id();
    $row['original_file'] = $entity->getOriginalFile()->label();
    $row['process_id'] = Link::createFromRoute(
      $entity->label(),
      'entity.cloudconvert_task.canonical',
      ['cloudconvert_task' => $entity->id()]
    );
    $row['step'] = $entity->getStep();
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime());
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime());
    $row['user'] = $entity->getOwner()->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('changed', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
