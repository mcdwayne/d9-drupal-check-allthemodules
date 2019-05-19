<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * ModulesInstalled.
 */
class ModulesInstalled extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$modules) {
    if (in_array('colorbox', $modules)) {
      self::colorboxMobileDetect();
    }
    if (in_array('contact_mail', $modules)) {
      self::contactMailSettings();
    }
  }

  /**
   * Update settings colorbox.settings.yml.
   */
  public static function colorboxMobileDetect() {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('colorbox.settings');
    $config->set('advanced.mobile_detect', 0);
    $config->save(TRUE);
  }

  /**
   * Update settings contact_mail.settings.yml.
   */
  public static function contactMailSettings() {
    $ru_header = "
    <h2>Вам письмо от сайта</h2>
    <ul>
    <li>Заявка пришла с технического адреса, не стоит нажимать 'ответить' и отправлять ответ нам.
    E-mail клиента (если он его оставил) находится где-то в письме.</li>
    <li>До того как начать писать e-mail посмотри - может клиент оставил телефон,
    в таком случае лучше прямо сейчас ему позвонить, сообщить что заявка получена, и передана в работу.</li>
    </ul>
    <hr>
    <h2>Содержимое заявки</h2>";
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('contact_mail.settings');
    $config->set('header', $ru_header);
    $config->set('langcode', 'ru');
    $config->save(TRUE);
  }

}
