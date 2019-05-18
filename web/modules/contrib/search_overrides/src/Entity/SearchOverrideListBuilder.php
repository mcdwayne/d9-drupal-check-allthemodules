<?php

namespace Drupal\search_overrides\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Search overrides.
 *
 * @ingroup search_overrides
 */
class SearchOverrideListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['query'] = $this->t('Query');
    $header['elnid'] = $this->t('Promoted');
    $header['exnid'] = $this->t('Excluded');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\search_overrides\Entity\SearchOverride */
    $row['query'] = Link::createFromRoute(
      $entity->label(),
      'entity.search_override.edit_form',
      ['search_override' => $entity->id()]
    );
    // List the nodes that are elevated and excluded.
    $row['elnid']['data'] = $this->makeLinksFromRef($entity->get('elnid'));
    $row['exnid']['data'] = $this->makeLinksFromRef($entity->get('exnid'));
    return $row + parent::buildRow($entity);
  }

  /**
   * Turn a EntityReferenceFieldItemList into a render array of links.
   */
  protected function makeLinksFromRef($ref) {
    // No value means nothing to do.
    if (!$ref) {
      return NULL;
    }
    $entities = $ref->referencedEntities();
    $content = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => ['class' => 'container'],
    ];
    $links = [];
    foreach ($entities as $ref_entity) {
      $links[] = Link::fromTextAndUrl(
        $ref_entity->getTitle(),
        $ref_entity->toUrl()
      );
    }
    $content['#items'] = $links;
    return $content;
  }

}
