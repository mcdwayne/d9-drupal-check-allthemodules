<?php

namespace Drupal\ipstack\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ipstack\Ipstack;

class IpstackController extends ControllerBase {

  /**
  * Show Ipstack information.
  *
  * @param string $ip
  *   IP address for the test.
  */
  public function page() {
    $ip = \Drupal::request()->query->get('ip');
    if (!empty($ip)) {
      $output = $this->t('Ipstack test for IP %ip', ['%ip' => $ip]);

      $config = \Drupal::config('ipstack.settings');
      $options = [];
      $options_keys = ['fields', 'hostname', 'security', 'language', 'output'];
      foreach ($options_keys as $key) {
        $value = $config->get($key);
        if (!empty($value)) {
          if (is_array($value)) {
            $value = implode(',', $value);
          }
        $options[$key] = $value;
        }
      }
      $ipstack = new Ipstack($ip, $options);
      $ipstack->showResult();
    }
    else {
      $output = $this->t('Need IP parameter like ?ip=xxx.xxx.xxx.xxx ');
    }

	  return ['#markup' => $output];
  }

}
