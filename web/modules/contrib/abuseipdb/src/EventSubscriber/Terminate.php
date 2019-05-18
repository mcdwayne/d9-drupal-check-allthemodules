<?php

namespace Drupal\abuseipdb\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\abuseipdb\Controller\Report;
use Drupal\ban\BanIpManager;

class Terminate implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['pathBlacklist'];
    return $events;
  }

  public function pathBlacklist() {
    $blacklist = \Drupal::config('abuseipdb.settings')
      ->get('abuseipdb.paths');
    $current_path = \Drupal::service('path.current')->getPath();

    $match = \Drupal::service('path.matcher')->matchPath($current_path, $blacklist);

    if ($match) {
      $ip = \Drupal::request()->getClientIp();
      $report_params = [];
      $report_params['ip'] = $ip;
      $report_params['categories'] = [21];
      $report = new Report($report_params);
      $report->report();

      $ban_ip = \Drupal::config('abuseipdb.settings')
        ->get('abuseipdb.paths_ban_ip');
      
      if ($ban_ip) {
        $connection = \Drupal::service('database');
        $ipManager = new BanIpManager($connection);
        $ipManager->banIp($ip);
      }
    }
  }


}