<?php

namespace Drupal\config_entity_revisions;

/**
 * Defines a trait for modules to use in creating a revisioned config entity.
 */
trait ConfigEntityRevisionsStorageTrait {

  /**
   * @return string
   *   The name of the module implementing the API.
   */
  public function module_name() {
    return $this->constants['module_name'];
  }

  /**
   * @return string
   *   The name of the entity being revisioned.
   */
  public function config_entity_name() {
    return $this->constants['config_entity_name'];
  }

  /**
   * @return string
   *   The name of the content entity in which revisions are being stored.
   */
  public function revisions_entity_name() {
    return $this->constants['revisions_entity_name'];
  }

  /**
   * @return string
   *   The name of the setting on the config entity in which content entity
   *   ids are stored.
   */
  public function setting_name() {
    return $this->constants['setting_name'];
  }

  /**
   * @return string
   *   The proper name (displayed to the user) of the module implementing the
   *   API.
   */
  public function title() {
    return $this->constants['title'];
  }

  /**
   * @return boolean
   *   Does the config entity have its own content entities?
   */
  public function has_own_content() {
    return $this->constants['has_own_content'];
  }

  /**
   * @return string
   *   The name of the content entities that the config entity has.
   */
  public function content_entity_type() {
    return $this->constants['content_entity_type'];
  }

  /**
   * @return string
   *   @TODO.
   */
  public function content_parameter_name() {
    return $this->constants['content_parameter_name'];
  }

  /**
   * @return string
   *   @TODO.
   */
  public function content_parent_reference_field() {
    return $this->constants['content_parent_reference_field'];
  }

  /**
   * @return string
   *   The name of the module implementing the API.
   */
  public function admin_permission() {
    return $this->constants['admin_permission'];
  }

  /**
   * @return boolean
   *   Whether the entity has a canonical URL.
   */
  public function has_canonical_url() {
    return $this->constants['has_canonical_url'] ?: FALSE;
  }

}