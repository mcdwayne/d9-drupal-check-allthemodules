<?php

namespace Drupal\alexa_flash_briefing\Plugin\views\display;

use Drupal\views\Plugin\views\display\Feed;

/**
 * Plugin used to create an Alexa Flash Briefing feed.
 *
 * @ViewsDisplay(
 *   id = "alexa_flash_briefing",
 *   title = @Translation("Alexa Flash Briefing"),
 *   help = @Translation("Display the view as an Alexa Flash Briefing feed."),
 *   uses_route = TRUE,
 *   admin = @Translation("Alexa Flash Briefing"),
 *   returns_response = TRUE
 * )
 */
class FlashBriefing extends Feed {

  /**
   * {@inheritdoc}
   */
  protected $usesAJAX = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesPager = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesMore = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAttachments = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'alexa_flash_briefing';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['style']['contains']['type']['default'] = 'alexa_flash_briefing_text_json';
    $options['row']['contains']['type']['default'] = 'fields';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function newDisplay() {
    // Do not execute parent logic.
  }

}
