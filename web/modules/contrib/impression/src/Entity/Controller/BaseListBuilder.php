<?php

/**
 * @file
 * Contains \Drupal\impression\Entity\Controller\ContentEntityExampleController.
 */

namespace Drupal\impression\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for impression entity.
 *
 * @ingroup impression
 */
class BaseListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('Content Entity Example implements a Bases model. These bases are fieldable entities. You can manage the fields on the <a href="@adminlink">Bases admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()->generateFromRoute('impression.base_settings'),
      )),
    );
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the base list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('BaseID');
    $header['domain'] = $this->t('Domain');
    $header['uri'] = $this->t('URL');
    $header['ip'] = $this->t('IP');
    $header['ref'] = $this->t('Referral URL');
    $header['action'] = $this->t('Action');
    $header['user_id'] = $this->t('User');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\impression\Entity\Base */
    $row['id'] = $entity->link();
    $row['domain'] = $entity->domain->value;
    $row['uri'] = $entity->uri->value;
    $row['ip'] = $entity->ip->value;
    $row['ref'] = $entity->ref->value;
    $row['action'] = $entity->action->value;
    $row['user_id'] = $entity->getOwnerId();
    $row['created'] = $entity->created->value;
    return $row + parent::buildRow($entity);
  }

}
