<?php


namespace Drupal\maintenance_notifications\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigCrudEvent;

/**
 * Description of MaintenanceNotificationSubscriber
 *
 * @author sandeepreddyg
 */
class MaintenanceNotificationSubscriber implements EventSubscriberInterface {

  //put your code here
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('sendNotificationMails');
    return $events;
  }

  function sendNotificationMails(ConfigCrudEvent $event) {
    $configObj = $event->getConfig();
    if($configObj->getName() == 'system.maintenance'){
      $config = \Drupal::config('maintenance_notifications.settings');
      $siteMode = \Drupal::state()->get('system.maintenance_mode');
      if($siteMode == 1 && $config->get('send_mail')){
        $to = $config->get('users_list');
        $params = array(
          'message' => $siteMode ? $config->get('mail_body_offline') : $config->get('mail_body_online'),
          'subject' => $siteMode ? $config->get('mail_subject_offline') : $config->get('mail_subject_online'),
        );
        $this->sendMail($params,$to);
      }
    }
  }


  function sendMail($params,$to) {
    $from = \Drupal::config('system.site')->get('name');
    $language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $result = \Drupal::service('plugin.manager.mail')->mail('maintenance_notifications', 'maintenance_notifications_key', $to, $language, $params, $from, TRUE);
    if ($result['result']) {
      drupal_set_message(t('Notifications are Sent Successfully to @to.', ['@to' => $to]));
    }
    else {
      drupal_set_message(t('There was an error sending notification. Please try again later'));
    }
  }

}
