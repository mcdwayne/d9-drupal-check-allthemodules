<?php

namespace Drupal\ad_entity_dfp\Plugin\ad_entity\AdView;

/**
 * View handler plugin for DFP ads for Facebook Instant Articles.
 *
 * @AdView(
 *   id = "dfp_fia",
 *   label = "DFP tag for Facebook Instant Articles",
 *   container = "fia",
 *   allowedTypes = {
 *     "dfp"
 *   }
 * )
 */
class DFPFia extends DFPIframe {}
