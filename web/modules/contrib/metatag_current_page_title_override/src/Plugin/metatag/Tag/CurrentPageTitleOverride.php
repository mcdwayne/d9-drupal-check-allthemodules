<?php

namespace Drupal\metatag_current_page_title_override\Plugin\metatag\Tag;


/**
 * The standard page title.
 *
 * @MetatagTag(
 *   id = "current-page-title-override",
 *   label = @Translation("Current Page Title override"),
 *   description = @Translation("This value will replace the [current-page:title] token value, allowing you to keep the sitewide page title pattern."),
 *   name = "current_page_title_override",
 *   group = "basic",
 *   weight = -1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CurrentPageTitleOverride extends \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase {

  /**
   * We should not render any tag for this.
   */
  public function output() {
    return '';
  }
}
