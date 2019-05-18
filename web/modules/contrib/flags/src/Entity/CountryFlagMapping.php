<?php

namespace Drupal\flags\Entity;


/**
 * Defines the flag mapping entity.
 *
 *
 * @ConfigEntityType(
 *   id = "country_flag_mapping",
 *   label = @Translation("Flag mapping for country"),
 *   config_prefix = "country_flag_mapping",
 *   admin_permission = "administer flag mapping",
 *   handlers = {
 *     "access" = "Drupal\flags\Security\FlagMappingAccessController",
 *     "list_builder" = "Drupal\flags\Entity\FlagMappingListBuilder",
 *     "form" = {}
 *   },
 *   entity_keys = {
 *     "id" = "source",
 *     "label" = "source"
 *   },
 *   links = {}
 * )
 */
class CountryFlagMapping extends FlagMapping {

}
