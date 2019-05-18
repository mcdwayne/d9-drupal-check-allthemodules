<?php

namespace Drupal\micro_node;

final class MicroNodeFields {

  /**
   * Name of the field for additional sites for node.
   */
  const NODE_SITES = 'field_sites';

  /**
   * Name of the field to publish node on all sites.
   */
  const NODE_SITES_ALL = 'field_sites_all';

  /**
   * Name of the field to disable main site canonical url.
   */
  const NODE_SITES_DISABLE_CANONICAL_URL = 'field_disable_canonical_url';
}
