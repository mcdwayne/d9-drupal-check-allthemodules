<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Availability tag.
 *
 * @MetatagTag(
 *   id = "agls_availability",
 *   label = @Translation("Availability"),
 *   description = @Translation("How the resource can be obtained or accessed, or contact information. The availability property is primarily used for offline resources to provide information on how to obtain physical access to the resource. <em>Mandatory for offline resources</em>."),
 *   name = "AGLSTERMS.availability",
 *   group = "agls",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Availability extends MetaNameBase {
  // Inherits everything from Base.

  // @TODO, scheme from D7?
  // '#description' => t('Possible values are AglsAgent, GOLD, or URI.'),
}
