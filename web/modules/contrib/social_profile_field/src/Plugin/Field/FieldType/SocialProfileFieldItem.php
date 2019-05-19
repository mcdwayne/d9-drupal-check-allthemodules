<?php

/**
 * @file
 * Contains Drupal\social_profile_field\Plugin\Field\FieldType\SocialProfileFieldItem.
 */

namespace Drupal\social_profile_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;

/**
 * Plugin implementation of the 'social_profile_url' field type.
 *
 * @FieldType(
 *   id = "social_profile_url",
 *   label = @Translation("Social Profile Field"),
 *   module = "SocialProfileField",
 *   description = @Translation("Handle social profiles links."),
 *   default_widget = "social_profile_field_default",
 *   default_formatter = "social_profile_field_default"
 * )
 */
class SocialProfileFieldItem extends UriItem {}
