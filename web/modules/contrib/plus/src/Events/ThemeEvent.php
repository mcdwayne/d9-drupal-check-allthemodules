<?php

namespace Drupal\plus\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ThemeEvent.
 */
class ThemeEvent extends Event {

  /**
   * A list of theme machine names relevant to the event.
   *
   * @var string[]
   */
  protected $themes;

  /**
   * ThemeEvent constructor.
   *
   * @param string[] $themes
   *   A list of theme machine names relevant to the event.
   */
  public function __construct(array $themes) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($themes)', 'Themes must be machine name strings.');
    $this->themes = $themes;
  }

  /**
   * Retrieves the themes for the event.
   *
   * @return string[]
   *   A list of theme machine names relevant to the event.
   */
  public function getThemes() {
    return $this->themes;
  }

  /**
   * Retrieves the first theme present in the theme list.
   *
   * @return string
   *   A theme machine name.
   */
  public function getTheme() {
    return reset($this->themes);
  }

}
