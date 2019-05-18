<?php

namespace Drupal\contact_scheduler;

use Drupal\contact\ContactFormInterface;

trait ContactSchedulerSettingsTrait {

  /**
   * Wrapper for getThirdPartySetting;
   *
   * @param \Drupal\contact\ContactFormInterface $contactForm
   *  The contact form entity.
   * @param string $name
   *  The name of the setting.
   * @param null $default_value
   *  The default value.
   * @return mixed
   */
  public static function getThirdPartySetting(ContactFormInterface $contactForm, $name, $default_value = NULL) {
    return $contactForm->getThirdPartySetting('contact_scheduler', $name, $default_value);
  }

  /**
   * Wrapper for setThirdPartySetting.
   *
   * @param \Drupal\contact\ContactFormInterface $contactForm
   *  The contact form entity.
   * @param string $name
   *  The name of the setting.
   * @param $value
   *  The value of the setting.
   */
  public static function setThirdPartySetting(ContactFormInterface $contactForm, $name, $value) {
    $contactForm->setThirdPartySetting('contact_scheduler', $name, $value);
  }
}
