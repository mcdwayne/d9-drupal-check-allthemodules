<?php

namespace Drupal\personas;

use Drupal\user\UserInterface;
use Drupal\personas\PersonaUtilityInterface;

class PersonaUtility implements PersonaUtilityInterface {

  /**
   * {@inheritdoc}
   */
  public static function fromUser(UserInterface $user) {
    return $user->get('personas')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function rolesFromUserPersonas(UserInterface $user) {
    $personas = PersonaUtility::fromUser($user);
    /* @var \Drupal\personas\PersonaInterface[] $personas */
    return array_values(array_reduce($personas, function ($roles, $persona) {
      $roles = array_merge($roles, $persona->getRoles());
      return $roles;
    }, []));
  }

  /**
   * {@inheritdoc}
   */
  public static function hasPersona(UserInterface $user, $persona) {
    $personas = static::fromUser($user);
    return in_array($persona, static::personaNames($personas));
  }

  /**
   * Returns a list of persona ids from a list of persona entities.
   *
   * @param \Drupal\personas\PersonaInterface[] $personas
   *   The list of personas from which to get IDs.
   *
   * @return string[]
   *   The list of persona IDs.
   */
  public static function personaNames($personas) {
    return array_map(function ($persona) {
      return $persona->id();
    }, $personas);
  }

}
