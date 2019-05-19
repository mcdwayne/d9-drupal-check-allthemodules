<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;
use Moment\Moment;

/**
 * Base class for moment plugins.
 */
class BaseMoment extends TwigPluginBase {

  /**
   * Get current language.
   *
   * @return string
   *   Return language id if exists. Default ist 'en_GB'.
   */
  protected function getLocale() {
    $language = \Drupal::service('language_manager')->getCurrentLanguage();
    $default = 'en_GB';
    $reflector = new \ReflectionClass('\Moment\Moment');

    $lang = implode('_', [
      $language->getId(),
      strtoupper($language->getId()),
    ]);

    if (!file_exists(dirname($reflector->getFileName()) . '/Locales/' . $lang . '.php')) {
      return $default;
    }
    return $lang;

  }

  /**
   * Get default timezone.
   *
   * @see https://api.drupal.org/api/drupal/core%21includes%21bootstrap.inc/function/drupal_get_user_timezone/8.4.x
   *
   * @return string
   *   Return timezone.
   */
  protected function getDefaultTimezone() {
    return drupal_get_user_timezone();
  }

  /**
   * Get moment library.
   *
   * @param mixed $date
   *   String or DateTime Object.
   * @param string $timezone
   *   Override timezone.
   */
  protected function getMoment($date, $timezone = NULL) {
    if ($timezone === NULL) {
      $timezone = $this->getDefaultTimezone();
    }

    Moment::setLocale($this->getLocale());

    if ($date === NULL) {
      return (new Moment())->setTimezone($timezone);
    }

    if (is_numeric($date)) {
      return (new Moment())->setTimestamp($date)
        ->setTimezone($timezone);
    }

    if (is_a($date, '\Moment\Moment')) {
      return $date;
    }

    return new Moment($date, $timezone);
  }

}
