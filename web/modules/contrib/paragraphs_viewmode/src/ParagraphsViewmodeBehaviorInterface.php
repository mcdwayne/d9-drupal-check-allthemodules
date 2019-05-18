<?php

namespace Drupal\paragraphs_viewmode;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorInterface;

/**
 * Interface ParagraphsViewmodeBehaviorInterface.
 */
interface ParagraphsViewmodeBehaviorInterface extends ParagraphsBehaviorInterface {

  /**
   * Allow plugin to alter the paragraph view mode.
   *
   * @param string $view_mode
   *   The current view mode.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param array $context
   *   Additional context information.
   *
   * @return mixed
   *   The new view mode.
   */
  public function entityViewModeAlter(&$view_mode, ParagraphInterface $paragraph, array $context);

}
