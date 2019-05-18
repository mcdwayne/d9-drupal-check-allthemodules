<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Function tag.
 *
 * @MetatagTag(
 *   id = "agls_function",
 *   label = @Translation("Function"),
 *   description = @Translation("The business function to which the resource relates (Recommended if dcterms:subject is not used). AGIFT is the recommended thesaurus for Australian government agencies."),
 *   name = "AGLSTERMS.function",
 *   group = "agls",
 *   weight = 7,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class FunctionTag extends MetaNameBase {
  // Inherits everything from Base.
}
