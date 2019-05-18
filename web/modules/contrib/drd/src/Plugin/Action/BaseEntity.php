<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for DRD Action plugins.
 */
abstract class BaseEntity extends Base implements BaseEntityInterface {

  /**
   * DRD entity.
   *
   * @var \Drupal\drd\Entity\BaseInterface
   */
  protected $drdEntity;

  /**
   * {@inheritdoc}
   */
  public function access($domain, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!isset($domain)) {
      // This is a general check if we have access to the action itself.
      return parent::access($domain, $account, $return_as_object);
    }

    $this->drdEntity = $domain;
    if (parent::access($domain, $account)) {
      return $this->drdEntity->access('edit', $account, $return_as_object);
    }
    return $return_as_object ? AccessResult::forbidden() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function log($severity, $message, array $args = []) {
    if (empty($this->drdEntity) || $this->drdEntity->isNew()) {
      return;
    }
    $args['@entity_id'] = $this->drdEntity->id();
    $args['@entity_name'] = $this->drdEntity->label();
    $args['@entity_type'] = $this->drdEntity->getEntityTypeId();
    $args['link'] = $this->drdEntity->toUrl()->toString(TRUE)->getGeneratedUrl();
    parent::log($severity, $message, $args);
  }

}
