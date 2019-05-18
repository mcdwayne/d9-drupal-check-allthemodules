<?php

namespace Drupal\erf;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Stores source entity to registration mappings in anonymous user sessions.
 *
 * Allows registration forms attached to entities to properly associate
 * registrations with different anonymous users. We can only do this with
 * sessions, because all anonymous users share the same user id (0).
 *
 * @see EntityRegistrationForm::buildForm()
 * @see Registration::postSave()
 */
class EntityRegistrationSession {

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a new EntityRegistrationSession object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * Add a mapping for a source entity to a registration.
   */
  public function addEntityRegistration($source_entity, $registration_id) {
    $key = 'erf:' . $source_entity->getEntityTypeId() . ':' . $source_entity->id();
    $this->session->set($key, $registration_id);
  }

  /**
   * Get a mapping for a source entity to a registration.
   */
  public function getEntityRegistration($source_entity) {
    $key = 'erf:' . $source_entity->getEntityTypeId() . ':' . $source_entity->id();
    return $this->session->get($key);
  }

  /**
   * Get all registration ids for a given session
   */
  public function getRegistrationIds() {
    $registration_ids = [];
    foreach ($this->session->all() as $key => $value) {
      if (strpos($key, 'erf:') === 0) {
        $registration_ids[] = $value;
      }
    }
    return $registration_ids;
  }
}
