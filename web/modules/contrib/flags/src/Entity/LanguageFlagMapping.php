<?php


namespace Drupal\flags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the flag mapping entity.
 *
 *
 * @ConfigEntityType(
 *   id = "language_flag_mapping",
 *   label = @Translation("Language Flag mapping"),
 *   config_prefix = "language_flag_mapping",
 *   admin_permission = "administer flag mapping",
 *   handlers = {
 *     "access" = "Drupal\flags\Security\FlagMappingAccessController",
 *     "list_builder" = "Drupal\flags\Entity\LanguageFlagMappingListBuilder",
 *     "form" = {}
 *   },
 *   entity_keys = {
 *     "id" = "source",
 *     "label" = "info"
 *   },
 *   links = {}
 * )
 */
class LanguageFlagMapping extends FlagMapping {}
