<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Date Licensed tag.
 *
 * @MetatagTag(
 *   id = "agls_datelicensed",
 *   label = @Translation("Date Licensed"),
 *   description = @Translation("Date a license was applied or became effective."),
 *   name = "AGLSTERMS.dateLicensed",
 *   group = "agls",
 *   weight = 5,
 *   type = "date",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class DateLicensed extends MetaNameBase {
  // Inherits everything from Base.
}
