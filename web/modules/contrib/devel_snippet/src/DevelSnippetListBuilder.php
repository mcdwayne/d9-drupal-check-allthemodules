<?php

namespace Drupal\devel_snippet;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Devel Snippet entities.
 *
 * @ingroup devel_snippet
 */
class DevelSnippetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Snippet ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\devel_snippet\Entity\DevelSnippet */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.devel_snippet.canonical', ['devel_snippet' => $entity->id()]);
    $row += parent::buildRow($entity);

    $row['operations']['data']['#links'] = [
      'execute' => [
        'title' => $this->t('Execute / Edit'),
        'weight' => 0,
        'url' => Url::fromRoute('entity.devel_snippet.canonical', ['devel_snippet' => $entity->id()]),
      ],
    ] + $row['operations']['data']['#links'];

    return $row;
  }

}
