<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 12/10/16
 * Time: 13:21
 */

namespace Drupal\elastic_search\Plugin\EntityTypeDefinitions;

use Drupal\elastic_search\Annotation\EntityTypeDefinitions;
use Drupal\elastic_search\Plugin\EntityTypeDefinitionsBase;

/**
 * Class User
 * Returns an array of fields for User entity types.
 * This removes most of the fields that you dont care about, leaving only the ID
 *
 * @EntityTypeDefinitions(
 *   id = "user",
 *   label = @Translation("user")
 * )
 */
class User extends EntityTypeDefinitionsBase {

  use FieldFilterTrait;

  /**
   * @inheritDoc
   */
  protected function allowedFields(): array {
    return ['uid'];
  }

}