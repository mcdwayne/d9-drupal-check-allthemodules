<?php

namespace Drupal\applenews;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Applenews article.
 */
class ArticleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Article ID');
    $header['revision'] = $this->t('Revision');
    $header['created'] = $this->t('Created');
    $header['modified'] = $this->t('Modified');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $channel_id = $entity->getChannelId();
    $sections = $entity->getSections();
    /** @var \Drupal\applenews\Entity\ApplenewsArticle $entity */
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
