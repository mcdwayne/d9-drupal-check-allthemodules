<?php

namespace Drupal\healthcheck;

/**
 * Interface CheckConfigServiceInterface.
 */
interface CheckConfigServiceInterface {

  /**
   * Deletes check config entities with no plugin, creates defaults for those missing.
   */
  public function sync();

  /**
   * Deletes any check config entity which no longer has a valid plugin.
   */
  public function clean();

  /**
   * Creates new check config entities for plugins with no existing entity.
   */
  public function createDefaults();

    /**
   * Get Check Config entities, filtering by tag and name.
   *
   * @param $tags
   *   Optional. An array of Healthcheck plugin tags.
   * @param array $omit
   *   Optional. An array of Healthcheck IDs to omit from results.
   *
   * @return array
   *   An array of Healthcheck Check COnfig entities, keyed by plugin ID.
   */
  public function getByTags($tags = [], $omit = []);
}
