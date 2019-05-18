<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Document Type tag.
 *
 * @MetatagTag(
 *   id = "agls_documenttype",
 *   label = @Translation("Document Type"),
 *   description = @Translation("The form of the described resource where the value of category is 'document'. Document is used in its widest sense and includes resources such as text, images, sound files and software."),
 *   name = "AGLSTERMS.documentType",
 *   group = "agls",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class DocumentType extends MetaNameBase {
  // Inherits everything from Base.
}
