<?php
/**
 * @file
 * Contains \Drupal\nodeletter\Controller\NodeletterSendingListBuilder.
 */

namespace Drupal\nodeletter\Controller;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeletterSendingListBuilder extends EntityListBuilder  {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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


  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = array(
      'node' => $this->t('Node'),
      'date' => array(
        'data' => $this->t('Created'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'status' => array(
        'data' => $this->t('Sending Status'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'owner' => array(
        'data' => $this->t('Author'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = array(
        'data' => $this->t('Language'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      );
    }
    return $header + parent::buildHeader();
  }



  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\nodeletter\Entity\NodeletterSending */
    $node = $entity->getNode();

//    $mark = array(
//      '#theme' => 'mark',
//      '#mark_type' => node_mark($entity->id(), $entity->getChangedTime()),
//    );

    $row['node'] = $node->link();
    $row['date'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');
    $row['status'] = $entity->getSendingStatus();
    $row['owner']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];

    return $row + parent::buildRow($entity);
  }
}
