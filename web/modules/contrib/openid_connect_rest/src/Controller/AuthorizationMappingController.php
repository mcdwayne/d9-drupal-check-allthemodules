<?php

namespace Drupal\openid_connect_rest\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * Provides a listing of authorization mapping entities.
 *
 * List Controllers provide a list of entities in a tabular form. The base
 * class provides most of the rendering logic for us. The key functions
 * we need to override are buildHeader() and buildRow(). These control what
 * columns are displayed in the table, and how each row is displayed
 * respectively.
 *
 * Drupal locates the list controller by looking for the "list" entry under
 * "controllers" in our entity type's annotation. We define the path on which
 * the list may be accessed in our module's *.routing.yml file. The key entry
 * to look for is "_entity_list". In *.routing.yml, "_entity_list" specifies
 * an entity type ID. When a user navigates to the URL for that router item,
 * Drupal loads the annotation for that entity type. It looks for the "list"
 * entry under "controllers" for the class to load.
 *
 * @package Drupal\openid_connect_rest\Controller
 *
 * @ingroup openid_connect_rest
 */
class AuthorizationMappingController extends ConfigEntityListBuilder {

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine Name');
    $header['authorization_code'] = $this->t('Authorization Code');
    $header['state_token'] = $this->t('State Token');
    $header['user_sub'] = $this->t('User Sub');
    $header['expires'] = [
      'field' => 'expires',
      'specifier' => 'expires',
      'data' => $this->t('Expires'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['authorization_code'] = $entity->authorization_code;
    $row['state_token'] = $entity->state_token;
    $row['user_sub'] = $entity->user_sub;
    $row['expires'] = $entity->expires;

    return $row + parent::buildRow($entity);
  }

  /**
   * Adds some descriptive text to our entity list.
   *
   * Typically, there's no need to override render(). You may wish to do so,
   * however, if you want to add markup before or after the table.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('<p>You can view the list of authorization mappings here. You can also use the "Operations" column to edit and delete authorization mappings.</p>'),
    ];
    $build[] = parent::render();
    return $build;
  }

}
