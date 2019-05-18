<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS act tag.
 *
 * @MetatagTag(
 *   id = "agls_act",
 *   label = @Translation("ACT"),
 *   description = @Translation("A specific piece of legislation which requires or drives the creation or provision of the resource."),
 *   name = "AGLSTERMS.act",
 *   group = "agls",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Act extends MetaNameBase {
  // Inherits everything from Base.
}
