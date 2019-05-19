<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * FormContactMailSettingsAlter.
 */
class FormContactMailSettingsAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $header = '';
    if ($lang == 'ru' && isset($form['contact']['header']['#default_value'])) {
      $header = $form['contact']['header']['#default_value'];
      if (substr($header, 0, 26) == '<h2>Mail from website</h2>') {
        $form['contact']['header']['#default_value'] = self::getRuHeader();
      }
    }
  }

  /**
   * Ru Header.
   */
  public static function getRuHeader() {
    $ru_header = "
<h2>Вам письмо от сайта</h2>
<ul>
  <li>Заявка пришла с технического адреса, не стоит нажимать 'ответить' и отправлять ответ нам.
E-mail клиента (если он его оставил) находится где-то в письме.</li>
  <li>До того как начать писать e-mail посмотри - может клиент оставил телефон,
в таком случае лучше прямо сейчас ему позвонить, сообщить что заявка получена, и передана в работу.</li>
</ul>
<hr>
<h2>Содержимое заявки</h2>
";
    return $ru_header;
  }

}
