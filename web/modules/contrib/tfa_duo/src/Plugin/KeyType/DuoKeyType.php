<?php

namespace Drupal\tfa_duo\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyType\AuthenticationMultivalueKeyType;

/**
 * Defines a key that combines the crendentials to authenticate to Duo.com.
 *
 * @KeyType(
 *   id = "duo",
 *   label = @Translation("Duo"),
 *   description = @Translation("A key type to store credentials to authenticate to Duo.com."),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "textarea_field"
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *       "duo_application" = @Translation("Application key"),
 *       "duo_secret" = @Translation("Secret key"),
 *       "duo_integration" = @Translation("Integration key"),
 *       "duo_apihostname" = @Translation("API hostname")
 *     }
 *   }
 * )
 */
class DuoKeyType extends AuthenticationMultivalueKeyType {
}
