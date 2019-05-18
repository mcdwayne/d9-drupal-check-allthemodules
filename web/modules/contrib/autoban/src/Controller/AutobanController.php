<?php

namespace Drupal\autoban\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\autoban\Entity\Autoban;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AutobanController extends ControllerBase {

  /**
   * Retrieve IP addresses for autoban rule.
   *
   * @param string $rule
   *   Autoban rule ID.
   * @param array $params
   *   Params (type, message) for special query.
   *
   * @return array
   *   IP addresses as query result.
   */
  public function getBannedIp($rule, array $params = []) {
    $query_mode = $this->config('autoban.settings')->get('autoban_query_mode');
    $regexp_query_mode = $query_mode == 'regexp';

    $from_analyze = ($rule == AUTOBAN_FROM_ANALYZE) && !empty($params);

    if ($from_analyze) {
      $entity = NULL;
      $message = $params['message'];
      $type = $params['type'];
      $threshold = 1;
      $referer = NULL;
      $user_type = 0;
    }
    else {
      $entity = autoban::load($rule);
      $message = trim($entity->message);
      $type = trim($entity->type);
      $threshold = (int) $entity->threshold;
      $referer = trim($entity->referer);
      $user_type = (int) $entity->user_type;
    }

    $connection = Database::getConnection();
    $query = $connection->select('watchdog', 'log');
    $query->fields('log', ['hostname']);

    $group = $query->orConditionGroup();

    // Checking for multiple messages divided by separator.
    $message_items = explode('|', $message);
    if (count($message_items) > 1) {
      foreach ($message_items as $message_item) {
        if ($regexp_query_mode) {
          $group->condition('log.message', trim($message_item), 'REGEXP')
            ->condition('log.variables', trim($message_item), 'REGEXP');
        }
        else {
          $group->condition('log.message', '%' . $query->escapeLike(trim($message_item)) . '%', 'LIKE')
            ->condition('log.variables', '%' . $query->escapeLike(trim($message_item)) . '%', 'LIKE');
        }
      }
    }
    else {
      if ($regexp_query_mode) {
        $group->condition('log.message', $message, 'REGEXP')
          ->condition('log.variables', $message, 'REGEXP');
      }
      else {
        $group->condition('log.message', '%' . $query->escapeLike($message) . '%', 'LIKE')
          ->condition('log.variables', '%' . $query->escapeLike($message) . '%', 'LIKE');
      }
    }

    $query->condition('log.type', $type, '=')
      ->condition($group);

    if (!empty($referer)) {
      $query->condition('log.referer', '%' . $query->escapeLike($referer) . '%', 'LIKE');
    }
    if ($user_type > 0) {
      switch ($user_type) {
        case 1:
          // Anonymous.
          $query->condition('log.uid', 0, '=');
          break;

        case 2:
          // Authenticated.
          $query->condition('log.uid', 0, '>');
          break;
      }
    }
    $query->groupBy('log.hostname');
    $query->addExpression('COUNT(log.hostname)', 'hcount');
    $query->having('COUNT(log.hostname) >= :cnt', [':cnt' => $threshold]);

    $result = $query->execute()->fetchAll();
    return $result;
  }

  /**
   * Get IP Ban Manager data from provider name.
   *
   * @param string $provider
   *   Ban provider ID.
   *
   * @return array
   *   Ban manager object and ban_type.
   */
  public function getBanManagerData($provider) {
    // Retrieve Ban provider data for the current provider.
    $banProvider = $this->getBanProvidersList($provider);
    if ($banProvider) {
      // Get Ban Manager object from AutobanProviderInterface implementation.
      $service = $banProvider['service'];
      if ($service) {
        $connection = Database::getConnection();
        // Return Ban Provider's Ban IP Manager and Ban Type.
        return [
          'ban_manager' => $service->getBanIpManager($connection),
          'ban_type' => $service->getBanType(),
        ];
      }
    }

    return NULL;
  }

  /**
   * Get IP Ban Manager data from autoban rule.
   *
   * @param string $rule
   *   Autoban rule ID.
   *
   * @return array
   *   Ban manager object and ban_type.
   */
  public function getBanManagerDataRule($rule) {
    $entity = autoban::load($rule);
    return $this->getBanManagerData($entity->provider);
  }

  /**
   * Get Ban providers list.
   *
   * @param string $provider_id
   *   Ban provider ID.
   *
   * @return array
   *   List ban providers or provider's data.
   */
  public function getBanProvidersList($provider_id = NULL) {
    $banProvidersList = [];
    $container = \Drupal::getContainer();
    $kernel = $container->get('kernel');

    // Get all services list.
    $services = $kernel->getCachedContainerDefinition()['services'];
    foreach ($services as $service_id => $value) {
      $service_def = unserialize($value);
      if (!empty($service_def['properties']) && !empty($service_def['properties']['_serviceId'])) {
        $service_id = $service_def['properties']['_serviceId'];
        $aservices = explode('.', $service_id);
        // Filter for services with ban_provider.
        if (!empty($aservices[1]) && $aservices[1] == 'ban_provider') {
          $service = \Drupal::service($service_id);
          $id = $service->getId();
          $name = $service->getName();
          $banProvidersList[$id] = ['name' => $name, 'service' => $service];
        }
      }
    }

    if (!empty($provider_id)) {
      return isset($banProvidersList[$provider_id]) ? $banProvidersList[$provider_id] : NULL;
    }
    else {
      return $banProvidersList;
    }
  }

  /**
   * Direct ban controller.
   *
   * @param string $ips
   *   IP addresses (comma delimited).
   * @param string $provider
   *   Ban provider name.
   *
   * @return bool
   *   IP banned status.
   */
  public function banIpAction($ips, $provider) {
    $banManagerData = $this->getBanManagerData($provider);
    $ips_arr = explode(',', $ips);
    foreach ($ips_arr as $ip) {
      $banned = $this->banIp($ip, $banManagerData, TRUE);

      if ($banned) {
        drupal_set_message($this->t('IP %ip has been banned', ['%ip' => $ip]));
      }
      else {
        drupal_set_message($this->t('IP %ip has not been banned', ['%ip' => $ip]), 'warning');
      }
    }

    $destination = $this->getDestinationArray();
    if (!empty($destination)) {
      $url = Url::fromUserInput($destination['destination']);
      return new RedirectResponse($url->toString());
    }
  }

  /**
   * Ban address.
   *
   * @param string $ip
   *   IP address.
   * @param array $banManagerData
   *   Ban manager data.
   * @param bool $debug
   *   Show debug message.
   *
   * @return bool
   *   IP banned status.
   */
  public function banIp($ip, array $banManagerData, $debug = FALSE) {
    if (empty($banManagerData)) {
      if ($debug) {
        drupal_set_message($this->t('Empty banManagerData.'), 'warning');
      }
      return FALSE;
    }

    $banManager = $banManagerData['ban_manager'];

    if (!$this->canIpBan($ip)) {
      if ($debug) {
        drupal_set_message($this->t('Cannot ban this IP.'), 'warning');
      }
      return FALSE;
    }

    if ($banManager->isBanned($ip)) {
      if ($debug) {
        drupal_set_message($this->t('This IP already banned.'), 'warning');
      }
      return FALSE;
    }

    $banType = $banManagerData['ban_type'];

    switch ($banType) {
      case 'single':
        $banManager->banIp($ip);
        break;

      case 'range':
        $ip_range = $this->createIpRange($ip);
        if (empty($ip_range)) {
          // If cannot create IP range banned single IP.
          $banManager->banIp($ip);
        }
        else {
          $banManager->banIp($ip_range['ip_start'], $ip_range['ip_end']);
        }
        break;
    }

    return TRUE;
  }

  /**
   * Ban addresses.
   *
   * @param array $ip_list
   *   IP addresses list.
   * @param string $rule
   *   Autoban rule ID.
   *
   * @return int
   *   IP banned count.
   */
  public function banIpList(array $ip_list, $rule) {
    $count = 0;
    if (!empty($ip_list) && $rule) {
      // Retrieve Ban manager object for current rule.
      $banManagerData = $this->getBanManagerDataRule($rule);
      if ($banManagerData) {
        foreach ($ip_list as $item) {
          $banStatus = $this->banIp($item->hostname, $banManagerData);
          if ($banStatus) {
            $count++;
            drupal_set_message($this->t('IP %ip has been banned.', ['%ip' => $item->hostname]));
          }
        }
      }
      else {
        drupal_set_message($this->t('No ban manager for rule %rule', ['%rule' => $rule]), 'error');
      }
    }

    return $count;
  }

  /**
   * Callback for test autoban rule.
   *
   * @param string $rule
   *   Autoban rule ID.
   */
  public function test($rule) {
    $from_analyze = FALSE;
    if ($rule == AUTOBAN_FROM_ANALYZE) {
      $params = \Drupal::request()->query->all();
      $from_analyze = !empty($params['type']) && !empty($params['message']);
    }
    else {
      $params = [];
    }

    $header = [
      $this->t('Count'),
      $this->t('Ip address'),
    ];
    if ($from_analyze) {
      $result = $this->getBannedIP($rule, $params);
      $ips_arr = [];
    }
    else {
      $result = $this->getBannedIP($rule);
      $header[] = $this->t('Ban status');
    }

    $rows = [];
    $build = [];

    if ($result !== NULL) {
      $banManager = NULL;
      if (!$from_analyze) {
        $banManagerData = $this->getBanManagerDataRule($rule);
        if ($banManagerData) {
          $banManager = $banManagerData['ban_manager'];
        }
      }

      // Rows collect.
      foreach ($result as $item) {
        $data = [
          $item->hcount,
          $item->hostname,
        ];
        if ($from_analyze) {
          $ips_arr[] = $item->hostname;
        }
        else {
          $data[] = $banManager ? ($banManager->isBanned($item->hostname) ? $this->t('Banned') : $this->t('Not banned')) : '?';
        }

        $rows[] = ['data' => $data];
      }

      // Add action buttons.
      $buttons = [];
      $destination = ['destination' => Url::fromRoute('<current>', $params)->toString()];
      if (!$from_analyze) {
        $entity = autoban::load($rule);
        $url = Url::fromRoute('autoban.ban', ['rule' => $entity->id()],
          ['query' => $destination, 'attributes' => ['class' => 'button button-action button--primary button--small']]
        );
        $buttons['ban'] = Link::fromTextAndUrl($this->t('Ban IP'), $url)->toString();
      }
      else {
        if (count($ips_arr)) {
          $banManagerList = $this->getBanProvidersList();
          $ips = implode(',', $ips_arr);
          if (!empty($banManagerList)) {
            foreach ($banManagerList as $id => $item) {
              $buttons[$id] = $this->l($item['name'],
                new Url('autoban.direct_ban', ['ips' => $ips, 'provider' => $id],
                  ['query' => $destination, 'attributes' => ['class' => 'button button-action button--primary button--small']]
                ));
            }
          }
        }
      }
      $build['buttons'] = [
        '#theme' => 'item_list',
        '#items' => $buttons,
        '#attributes' => ['class' => 'action-links'],
      ];
    }

    $build['test_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No hostnames was found.'),
    ];

    return $build;
  }

  /**
   * Check IP address for ban.
   *
   * @param string $ip
   *   IP candidate for ban.
   *
   * @return bool
   *   Can ban.
   */
  public function canIpBan($ip) {
    // You cannot ban your current IP address.
    if ($ip == \Drupal::request()->getClientIp()) {
      return FALSE;
    }

    // The IP address must not be whitelisted.
    if ($this->whitelistIp($ip)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Is IP address in subnet?
   *
   * @param string $ip
   *   IP address for match check.
   * @param string $network
   *   IP subnet.
   * @param string $cidr
   *   CIDR.
   *
   * @return bool
   *   IP mathes for subnet.
   */
  private function cidrMatch($ip, $network, $cidr) {
    return ((ip2long($ip) & ~((1 << (32 - $cidr)) - 1)) == ip2long($network));
  }

  /**
   * Is IP address in whitelist?
   *
   * @param string $ip
   *   IP address for check.
   *
   * @return bool
   *   IP address in whitelist.
   */
  private function whitelistIp($ip) {
    $autoban_whitelist = $this->config('autoban.settings')->get('autoban_whitelist');
    if (!empty($autoban_whitelist)) {
      $real_host = gethostbyaddr($ip);
      $autoban_whitelist_arr = explode("\n", $autoban_whitelist);
      foreach ($autoban_whitelist_arr as $whitelist_ip) {
        $whitelist_ip = trim($whitelist_ip);
        if (empty($whitelist_ip)) {
          continue;
        }

        // Block comment.
        if (substr($whitelist_ip, 0, 1) == '#') {
          continue;
        }

        // Inline comment.
        $whitelist_ip_arr = explode('#', $whitelist_ip);
        if (count($whitelist_ip_arr) > 1) {
          $whitelist_ip = trim($whitelist_ip_arr[0]);
        }

        $whitelist_ip_arr = explode('/', $whitelist_ip);
        // CIDR match.
        if (count($whitelist_ip_arr) > 1) {
          $in_list = $this->cidrMatch($ip, $whitelist_ip_arr[0], (int) $whitelist_ip_arr[1]);
        }
        else {
          $in_list = ($whitelist_ip == $ip);
        }
        if ($in_list) {
          return TRUE;
        }

        // Check for domain.
        if ($real_host) {
          $real_host_arr = explode($whitelist_ip, $real_host);
          if (count($real_host_arr) == 2 && empty($real_host_arr[1])) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Create IP range from single IP.
   *
   * @param string $hostname
   *   IP address for ban.
   *
   * @return array
   *   IP range string for insert to ban table.
   */
  private function createIpRange($hostname) {
    // Make range IP from aaa.bbb.ccc.ddd to aaa.bbb.ccc.0 - aaa.bbb.ccc.255 .
    if (!ip2long($hostname)) {
      // Only IPV4 is available for IP range.
      return NULL;
    }
    $parts = explode('.', $hostname);
    if (count($parts) == 4) {
      $parts[3] = '0';
      $ip_start = implode('.', $parts);
      $parts[3] = '255';
      $ip_end = implode('.', $parts);
      return ['ip_start' => $ip_start, 'ip_end' => $ip_end];
    }
    return NULL;
  }

  /**
   * Get user type names list.
   *
   * @param int $index
   *   User type index (optional).
   *
   * @return string|array
   *   User type name or user type names list.
   */
  public function userTypeList($index = NULL) {
    $user_types = [
      0 => $this->t('Any'),
      1 => $this->t('Anonymous'),
      2 => $this->t('Authenticated'),
    ];

    if ($index === NULL) {
      return $user_types;
    }
    else {
      if (!isset($user_types[$index])) {
        $index = 0;
      }
      return $user_types[$index];
    }
  }

}
