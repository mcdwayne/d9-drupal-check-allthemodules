<?php

namespace Drupal\applenews;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Applenews Channel.
 */
class ChannelListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Channel ID');
    $header['sections'] = $this->t('Sections');
    $header['section_ids'] = $this->t('Section ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    $operations['refresh'] = [
      'title' => $this->t('Refresh'),
      'weight' => $operations['edit']['weight'] + 1,
      'url' => $entity->toUrl('refresh-form'),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $channel_id = $entity->getChannelId();
    $sections = $entity->getSections();
    /** @var \Drupal\applenews\Entity\ApplenewsChannel $entity */
    $row['name'] = $entity->getName();
    $row['id'] = $channel_id;
    $row['sections']['data'] = [
      '#type' => 'markup',
      '#markup' => implode('<br />', $sections),
    ];
    $row['section_ids']['data'] = [
      '#type' => 'markup',
      '#markup' => implode('<br />', array_keys($sections)),
    ];
    return $row + parent::buildRow($entity);
  }

}
