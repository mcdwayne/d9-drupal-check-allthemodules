<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Case tag.
 *
 * @MetatagTag(
 *   id = "agls_case",
 *   label = @Translation("Case"),
 *   description = @Translation("A specific piece of case law which requires or drives the creation or provision of the resource."),
 *   name = "AGLSTERMS.case",
 *   group = "agls",
 *   weight = 3,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CaseTag extends MetaNameBase {
  // Inherits everything from Base.
}
