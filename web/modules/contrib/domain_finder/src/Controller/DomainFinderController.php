<?php

namespace Drupal\domain_finder\Controller;

use Drupal\Core\Controller\ControllerBase;
use phpWhois\Whois;

/**
 * Controller routines for domain finder routes.
 */
class DomainFinderController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->getResult();
    $build = array(
      '#type' => 'container',
      $content,
    );

    return $build;
  }

  /**
   * Prepare phpWhois result.
   */
  public function getResult() {
    $domain_name = '';
    if (isset($_GET['domain_text'])) {
      $domain_name = $this->normalizeDomainName($_GET['domain_text']);
    }
    if (empty($domain_name)) {
      // Do not work with empty domain name.
      drupal_set_message(t('Please fill the domain search block informations and click to search button.'), 'warning');
      return array('#markup' => '');
    }

    // Prepare results array.
    $result = array(
      'domain' => $domain_name,
      'exts' => array(),
    );

    if (class_exists('phpWhois\Whois')) {
      // Create a whois class.
      $whois = new Whois();
      if ($whois) {
        $in_form = isset($_GET['domains_in_form']) ? $_GET['domains_in_form'] : 0;
        $domains = isset($_GET['domains']) ? $_GET['domains'] : array();
        foreach ($domains as $domain) {
          // Getting result from whois class.
          $whois_result = $whois->Lookup($result['domain'] . '.' . $domain);
          if (isset($whois_result['regrinfo']) &&
              isset($whois_result['regrinfo']['domain']['name']) &&
              isset($whois_result['regrinfo']['registered'])) {
            // Properly result get back.
            $result['exts'][$domain] = $whois_result;
          }
          else {
            // Prepare empty data to result
            $result['exts'][$domain]['regrinfo'] = array(
              'domain' => array(
                'name' => $domain_name . '.' . $domain,
              ),
              'registered' => 'n/a',
            );
          }
        }

        // Results page output.
        return array(
          '#theme' => 'domain_finder_results',
          '#results' => $result,
        );
      }
    }
  }

  /**
   * Normalize domain name to previous piece of last dot.
   */
  public function normalizeDomainName($domain_name) {
    $name = explode('.', $domain_name);
    $sum = count($name);
    $normalized_name = $domain_name;
    if ($sum > 1) {
      $normalized_name = $name[$sum - 2];
      drupal_set_message(t('Domain name reduced from %original to %name', array(
        '%original' => $domain_name,
        '%name' => $normalized_name,)));
    }
    return $normalized_name;
  }

}
