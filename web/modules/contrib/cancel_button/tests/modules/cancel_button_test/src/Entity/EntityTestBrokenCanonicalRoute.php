<?php

namespace Drupal\cancel_button_test\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines a test entity type with administrative routes.
 *
 * @ContentEntityType(
 *   id = "entity_test_broken_canonical",
 *   label = @Translation("Test entity - missing canonical route"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\cancel_button_test\Entity\Routing\BrokenHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_missing_routes",
 *   data_table = "entity_test_missing_routes_property_data",
 *   admin_permission = "administer entity_test content",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_broken_canonical/{entity_test_broken_canonical}",
 *     "add-form" = "/entity_test_broken_canonical/add",
 *     "edit-form" = "/entity_test_broken_canonical/manage/{entity_test_broken_canonical}/edit",
 *     "delete-form" = "/entity_test_broken_canonical/delete/entity_test/{entity_test_broken_canonical}",
 *   },
 * )
 */
class EntityTestBrokenCanonicalRoute extends EntityTest {

}
