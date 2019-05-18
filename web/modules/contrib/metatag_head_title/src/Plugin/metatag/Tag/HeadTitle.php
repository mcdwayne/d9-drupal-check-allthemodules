<?php

namespace Drupal\metatag_head_title\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The HEAD page title.
 *
 * @MetatagTag(
 *   id = "head_title",
 *   label = @Translation("HEAD title"),
 *   description = @Translation("The text to display in the title bar of a visitor's web browser when they view this page. This tag is never rendered in meta, instead, it replaces page head title."),
 *   name = "head_title",
 *   group = "basic",
 *   weight = -1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class HeadTitle extends MetaNameBase {}
