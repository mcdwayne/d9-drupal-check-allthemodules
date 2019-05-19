<?php

namespace Drupal\task\Plugin\task\Action;

use Drupal\Core\Plugin\PluginBase;
use Drupal\task\TaskActionInterface;
use Drupal\task\Entity\TaskInterface;

/**
 * @TaskAction(
 *   id = "send_mail",
 *   label = @Translation("Send Mail"),
 *   system_task = TRUE,
 * )
 */
class SendMail extends PluginBase implements TaskActionInterface {

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('This is a description of the default plugin.');
  }

  /**
   * Since this is a default, just return what we have.
   */
  public static function doAction(TaskInterface $task, $data = []) {
    $module = isset($data['module']) ? $data['module'] : 'task';
    $key = isset($data['key']) ? $data['key'] : 'task_mail';
    $to = isset($data['to']) ? $data['to'] : '';
    $langcode = isset($data['langcode']) ? $data['langcode'] : '';
    $params = isset($data['params']) ? $data['params'] : [];
    $reply = isset($data['reply']) ? $data['reply'] : NULL;
    $send = isset($data['send']) ? $data['send'] : TRUE;
    $mailManager = \Drupal::service('plugin.manager.mail');
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
  }
}