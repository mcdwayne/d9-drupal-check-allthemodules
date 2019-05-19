<?php

namespace Drupal\contact_mail\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;

/**
 * Contact_mail hook_mail_alter().
 */
class MailAlter extends ControllerBase {

  /**
   * Hook init.
   */
  public static function hook(&$message) {
    if ($message['id'] == 'contact_page_mail') {
      $config = \Drupal::config('contact_mail.settings');
      // Rewrite submission template.
      if ($config->get('tpl') && isset($message['params']['contact_message'])) {
        // Mail extra information.
        $message['body'][0] .= "\n" . Markup::create(self::getWarning($config));
        // Rewrite submission template.
        $message['body'][1] = Markup::create(self::getMessage($message));
      }
      \Drupal::moduleHandler()->alter('contact_mail_alter_message', $message, $config);
      // Add e-mail Recipients.
      self::addEmails($config, $message);
      \Drupal::moduleHandler()->alter('contact_mail_alter_emails', $message, $config);
      // Send html (instead txt).
      if ($config->get('html')) {
        $message['headers']['Content-Type'] = 'text/html';
      }
    }
  }

  /**
   * Add e-mail Recipients.
   */
  public static function addEmails($config, &$message) {
    if ($config->get('emails')) {
      $to = [];
      $to[] = $message['to'];
      $emails = $config->get('emails');
      $emails = explode("\n", $emails);
      foreach ($emails as $email) {
        if (strpos($email, "@") && strpos($email, ".")) {
          $to[] = trim($email);
        }
      }
      $message['to'] = implode(', ', $to);
    }
  }

  /**
   * Submission template.
   */
  public static function getMessage($message) {
    $form_id = $message['params']['contact_form']->id();
    $config = \Drupal::config("core.entity_view_display.contact_message.{$form_id}.default");
    $content = $config->get('content');
    $hidden = $config->get('hidden');
    $submission = $message['params']['contact_message']->toArray();
    $form = $message['params']['contact_form']->toArray();
    $msg = [];
    foreach ($submission as $key => $value) {
      if (strpos($key, 'field_') !== FALSE) {
        if (isset($content[$key])) {
          $fieldDefinition = $message['params']['contact_message']->$key->getFieldDefinition();
          $label = $fieldDefinition->label();
          $keyval = $fieldDefinition->getFieldStorageDefinition()->getSetting('allowed_values');
          $val = '—';
          if (isset($value[0]['target_id'])) {
            $set = $fieldDefinition->getFieldStorageDefinition()->getSettings();
            $entity_type = $set['target_type'];
            $storage = \Drupal::entityManager()->getStorage($entity_type);
            $val = "";
            foreach ($value as $k => $v) {
              $id = $v['target_id'];
              $entity = $storage->load($id);
              $val = "<br> — " . $entity->label();
            }
          }
          if (isset($value[1]['value'])) {
            $vals = [];
            foreach ($value as $k => $v) {
              $current = $v['value'];
              if (isset($keyval[$current])) {
                $current = $keyval[$current];
              }
              $vals[] = $current;
            }
            $val = "<br> — " . implode("<br> — ", $vals);
          }
          elseif (isset($value[0]['value'])) {
            $val = $value[0]['value'];
            if (isset($keyval[$val])) {
              $val = $keyval[$val];
            }
          }
          elseif ($fieldDefinition->getType() == 'file') {
            $target = isset($value[0]['target_id']) ? $value[0]['target_id'] : FALSE;
            $file = File::load($target);
            if ($file) {
              $fileUrl = file_create_url($file->getFileUri());
              $val = "<a href='{$fileUrl}'>" . $file->getFilename() . "</a>";
            }
          }
          $msg["field-$key"] = [
            '#weight' => $content[$key]['weight'],
            '#prefix' => "<div>",
            'title' => ['#markup' => "<b>$label:</b> "],
            'data' => ['#markup' => $val],
            '#suffix' => "</div>\n",
          ];
        }
      }
    }
    $renderable = [
      '#theme' => 'contact_mail',
      '#type' => $form_id,
      '#submission' => $msg,
    ];
    $html = \Drupal::service('renderer')->render($renderable);
    $html = trim($html) . "\n";
    return $html;
  }

  /**
   * Mail extra information.
   */
  public static function getWarning($config) {
    $warning['#markup'] = $config->get('header');
    $html = \Drupal::service('renderer')->render($warning);
    return $html;
  }

}
