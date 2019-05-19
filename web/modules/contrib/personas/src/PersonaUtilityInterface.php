<?php

namespace Drupal\personas;

use Drupal\user\UserInterface;

interface PersonaUtilityInterface {

  /**
   * Extracts personas from a user entity.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user from which to extract personas.
   *
   * @return \Drupal\personas\PersonaInterface[]
   *   The extracted personas.
   */
  public static function fromUser(UserInterface $user);

  /**
   * Extracts roles from a user's personas.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user from which to extract roles based on its personas.
   *
   * @return array
   *   An associative array of user_roles keyed by their ids.
   */
  public static function rolesFromUserPersonas(UserInterface $user);

  /**
   * Determines whether a given user has a given persona.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user with which to compare.
   *
   * @param string $persona
   *   The id of the persona to check.
   *
   * @return boolean
   *   Whether the given user has the specified persona.
   */
  public static function hasPersona(UserInterface $user, $persona);

}
