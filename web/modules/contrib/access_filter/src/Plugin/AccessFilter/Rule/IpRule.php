<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Rule;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\access_filter\Plugin\RuleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Filter rule using IP address.
 *
 * @AccessFilterRule(
 *   id = "ip",
 *   description = @Translation("IP address."),
 *   examples = {
 *     "- { type: ip, action: deny, address: '*' }",
 *     "- { type: ip, action: deny, address: 192.168.1.100 }",
 *     "- { type: ip, action: deny, address: 192.168.1.0/24 }",
 *     "- { type: ip, action: allow, address: 192.168.1.10-192.168.1.20 }"
 *   }
 * )
 */
class IpRule extends PluginBase implements RuleInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $action = $this->configuration['action'];
    if ($action == 'allow') {
      return $this->t('Allow from @ip', ['@ip' => $this->configuration['address']]);
    }
    elseif ($action == 'deny') {
      return $this->t('Deny from @ip', ['@ip' => $this->configuration['address']]);
    }
    else {
      return $this->t('Invalid configuration.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfiguration(array $configuration) {
    $errors = [];

    if (!isset($configuration['address']) || !strlen($configuration['address'])) {
      $errors[] = $this->t("'@property' is required.", ['@property' => 'address']);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    $pattern = isset($this->configuration['address']) ? $this->configuration['address'] : '*';
    if ($pattern == '*') {
      $is_ip_matched = TRUE;
    }
    else {
      $ip = $request->getClientIp();
      $ip_long = ip2long($ip);

      // Check as 2 IP address range format.
      $patterns = explode('-', $pattern);
      if (isset($patterns[1])) {
        return ($ip_long >= ip2long($patterns[0]) && $ip_long <= ip2long($patterns[1]));
      }

      // Check as single IP address and subnet format.
      $check = explode('/', $pattern);
      if (!isset($check[1])) {
        $check[1] = 32;
      }

      $network_long = ip2long($check[0]);
      $mask_long = bindec(str_repeat('1', $check[1]) . str_repeat('0', 32 - $check[1]));
      $is_ip_matched = ($ip_long & $mask_long) == $network_long;
    }

    if ($is_ip_matched) {
      if ($this->configuration['action'] == 'allow') {
        return AccessResult::allowed();
      }
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

}
