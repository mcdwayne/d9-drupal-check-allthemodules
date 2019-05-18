<?php

namespace Drupal\markjs_search;

interface MarkjsProfileManagerInterface {

  /**
   * Get MarkJS profile options.
   *
   * @return array
   *   An array of profile form options.
   */
  public function getProfileOptions();

  /**
   * Load MarkJS profile.
   *
   * @param $identifier
   *   The MarkJS profile identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The MarkJS profile; otherwise NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadProfile($identifier);
}
