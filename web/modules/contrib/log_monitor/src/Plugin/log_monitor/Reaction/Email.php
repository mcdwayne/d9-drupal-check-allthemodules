<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/12/17
 * Time: 2:10 PM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Reaction;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\log_monitor\Logger\LogMonitorLog;

/**
 * @LogMonitorReaction(
 *   id = "email",
 *   title = @Translation("Email"),
 *   description = @Translation("Email log message digests."),
 * )
 */
class Email extends ReactionPluginBase {


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type'     => 'textfield',
      '#title'    => t('Email address'),
      '#required' => TRUE,
    ];
    if (isset($this->getConfiguration()['settings']['email'])) {
      $form['email']['#default_value'] = $this->getConfiguration()['settings']['email'];
    }

    // Get email formats from formatter plugins
    $options = [];
    $descriptions = [];
    $formats = \Drupal::service('plugin.manager.log_monitor.formatter')->getDefinitions();
    foreach ($formats as $format) {
      $options[$format['id']] = (string) $format['title'];
      $descriptions[$format['id']] = (string) $format['description'];
    }

    $form['format'] = [
      '#type'     => 'radios',
      '#title'    => t('Format'),
      '#required' => TRUE,
      '#options'  => $options,
    ];

    foreach($options as $id => $description) {
      $form['format'][$id]['#description'] = $descriptions[$id];
    }

    if (isset($this->getConfiguration()['settings']['format'])) {
      $form['format']['#default_value'] = $this->getConfiguration()['settings']['format'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function action($entity) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'log_monitor';
    $key = 'email_reaction';
    $to = $this->getConfiguration()['settings']['email'];
    $langcode = 'en';
    $send = TRUE;

    $format = $this->getConfiguration()['settings']['format'];
    $formatter = \Drupal::service('plugin.manager.log_monitor.formatter')
      ->createInstance($format);

    $database = Database::getConnection();
    $logs = $database->query(
      'SELECT *
      FROM {log_monitor_log} l RIGHT JOIN {log_monitor_log_dependencies} ld
      ON l.wid = ld.wid
      WHERE l.status = :status AND ld.entity_id = :entity_id',
      [
        ':status'    => LogMonitorLog::STATUS_PROCESSED,
        ':entity_id' => $entity->id(),
      ]
    )->fetchAll();
    $params['message'] = $formatter->format($logs);
    $params['format'] = ucfirst($format);
    $message = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($message['result'] != TRUE) {
      \Drupal::logger('log_monitor')->error('The email to ' . $to . ' could not be sent.');
    }
    else {
      \Drupal::logger('log_monitor')->notice('Successfully sent email to ' . $to);
    }
  }

}
