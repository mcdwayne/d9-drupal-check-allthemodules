<?php

// @todo: The list is overridden by visualn_iframes views
//   thus the the given list builder can be deleted with corresponding
//   link and route (and action link).

namespace Drupal\visualn_iframe;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of VisualN IFrame entities.
 *
 * @ingroup iframes_toolkit
 */
class VisualNIFrameListBuilder extends EntityListBuilder {

  // @todo: add by hash filter
  //   use views instead of list builder (see /admin/structure/views/view/content view)

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['hash'] = $this->t('Hash');
    $header['handler_key'] = $this->t('Handler key');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\visualn_iframe\Entity\VisualNIFrame */
    $row['id'] = $entity->id();
    $row['hash'] = $entity->getHash();
    // @todo: use getter for handler_key
    $row['handler_key'] = $entity->get('handler_key')->value;
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.visualn_iframe.edit_form',
      ['visualn_iframe' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
