<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Service Type tag.
 *
 * @MetatagTag(
 *   id = "agls_servicetype",
 *   label = @Translation("Service Type"),
 *   description = @Translation("The form of the described resource where the value of category is 'service'."),
 *   name = "AGLSTERMS.serviceType",
 *   group = "agls",
 *   weight = 14,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ServiceType extends MetaNameBase {
  // Inherits everything from Base.
}
