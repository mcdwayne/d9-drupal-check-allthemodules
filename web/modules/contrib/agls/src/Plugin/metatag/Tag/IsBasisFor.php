<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS IsBasisFor tag.
 *
 * @MetatagTag(
 *   id = "agls_isbasisfor",
 *   label = @Translation("Is Basis For"),
 *   description = @Translation("A related resource that is a performance, production, derivation, translation or interpretation of the described resource."),
 *   name = "AGLSTERMS.isBasisFor",
 *   group = "agls",
 *   weight = 8,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsBasisFor extends MetaNameBase {
  // Inherits everything from Base.
}
