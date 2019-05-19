<?php

namespace Drupal\spammaster\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Firewall Subscriber.
 */
class SpamMasterFirewallSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function checkForRedirection(GetResponseEvent $event) {

    $spammaster_ip = \Drupal::request()->getClientIp();
    $spammaster_db_ip = \Drupal::database()->select('spammaster_threats', 'u');
    $spammaster_db_ip->fields('u', ['threat']);
    $spammaster_db_ip->where('(threat = :ip)', [':ip' => $spammaster_ip]);
    $spammaster_db_ip_result = $spammaster_db_ip->execute()->fetchObject();
    $spammaster_anonymous = \Drupal::currentUser()->isAnonymous();
    if (!empty($spammaster_db_ip_result) && $spammaster_anonymous) {
      \Drupal::logger('spammaster-firewall')->notice('Spam Master: firewall block, Ip: ' . $spammaster_ip);

      $spammaster_date = date("Y-m-d H:i:s");
      $spammaster_db_ip_insert = db_insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-firewall',
        'spamvalue' => 'Spam Master: firewall block, Ip: ' . $spammaster_ip,
      ])->execute();

      $spammaster_settings = \Drupal::config('spammaster.settings');
      $spammaster_total_block_count = $spammaster_settings->get('spammaster.total_block_count');
      $spammaster_total_block_count_1 = ++$spammaster_total_block_count;

      \Drupal::configFactory()->getEditable('spammaster.settings')
        ->set('spammaster.total_block_count', $spammaster_total_block_count_1)
        ->save();

      $page = '/firewall';
      $host = \Drupal::request()->getHost();
      $response = new RedirectResponse($page);
      $response->send();

      return;

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];

    return $events;
  }

}
