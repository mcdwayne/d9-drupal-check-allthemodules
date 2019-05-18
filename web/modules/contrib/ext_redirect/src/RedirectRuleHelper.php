<?php
/**
 * @file
 * RedirectRuleHelper.php
 *
 * <file description>
 *
 * @copyright © 2017 Unic AG
 * @author Jan Steffen <jan.steffen@unic.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License
 *   Version 3.0
 */

namespace Drupal\ext_redirect;


use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class RedirectRuleHelper.
 *
 * @package Drupal\ext_redirect
 */
class RedirectRuleHelper {

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructor.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Returns a basic redirect_rule query object.
   *
   * @param bool|null $status
   *   The status of the redirect rule.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query object.
   */
  public function getQuery($status = NULL) {
    $query = $this->database->select('redirect_rule', 'rr');
    $query->addField('rr', 'rid');
    $query->addField('rr', 'source_site');
    $query->addField('rr', 'source_path');
    $query->addField('rr', 'destination_uri__uri');
    $query->addField('rr', 'destination_uri__options');
    $query->addField('rr', 'status_code');
    $query->orderBy('weight', 'ASC');
    if (!is_null($status)) {
      $query->condition('status', $status);
    }
    return $query;
  }

  /**
   * Get a redirect rule by source site an source path.
   *
   * We need that to check if a rule already exists on entity save for example.
   *
   * @param string $source_site
   *   The source site.
   * @param string $source_path
   *   The source path.
   * @param int $status
   *   The status of the rule.
   *
   * @return object[]|bool
   *   Return an array with matching rules or false otherwise.
   */
  public function getRedirectRulesBySourceSiteAndPath($source_site, $source_path = '', $status = 1) {
    $matching_rules = FALSE;
    $query = $this->getQuery($status);
    $query
      ->condition('rr.source_site', $source_site);
    if (!empty($source_path)) {
      $query->condition('rr.source_path', '%' . $this->database->escapeLike($source_path) . '%', 'LIKE');
    }
    else {
      $query->isNull('rr.source_path');
    }

    // We only get candidates, cause of the %like% condition above. We now have
    // to check every "row" of each candidate if it matches exactly the passed
    // source path.
    $candidates = $query->execute()->fetchAll();
    if (empty($candidates)) {
      return FALSE;
    }

    foreach ($candidates as $candidate) {
      // Extract lines.
      $candidate_source_paths = preg_split('/\n|\r\n?/', $candidate->source_path);
      foreach ($candidate_source_paths as $candidate_source_path) {
        if ($candidate_source_path == $source_path) {
          $matching_rules[$candidate->rid] = $candidate;
        }
      }
    }
    return $matching_rules;
  }

  /**
   * Get primary host name.
   *
   * @return string
   *    Primary host name.
   *
   * @throws \Exception
   */
  public function extRedirectPrimarySite() {
    static $primary_host = NULL;

    if (!$primary_host) {
      $primary_host = \Drupal::service('ext_redirect.config')->getPrimaryHost();
    }

    if (!$primary_host) {
      /** @var \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch */
      $currentRouteMatch = \Drupal::service('current_route_match');
      $settingsRouteName = 'ext_redirect.ext_redirect_settings_form';
      $currentRouteName = $currentRouteMatch->getCurrentRouteMatch()
        ->getRouteName();

      if ($currentRouteName <> $settingsRouteName) {
        $url = Url::fromRoute('ext_redirect.ext_redirect_settings_form', [], ['absolute' => TRUE]);
        $link = Link::fromTextAndUrl(t('here'), $url)->toString();
        drupal_set_message(t('Primary host not specified. You can set it @link', ['@link' => $link]), 'warning');
      }
    }

    return $primary_host;
  }

  /**
   * Get list of available host sources.
   *
   * @return array
   *   List of available host sources.
   */
  public static function extRedirectSourceSitesAllowedValues() {
    // @TODO refactoring - use config service explicit, not via helper.
    /** @var \Drupal\ext_redirect\Service\ExtRedirectConfig $extRedirectConfig */
    $extRedirectConfig = \Drupal::service('ext_redirect.config');
    $sources = $extRedirectConfig->getAllowedHostAliases();
    $sources = array_combine($sources, $sources);
    $sources = ['any' => t('Any')->render()] + $sources;
    return $sources;
  }

  public static function extRedirectStatusCodes($code = NULL) {
    $codes = [
      300 => t('300 Multiple Choices'),
      301 => t('301 Moved Permanently'),
      302 => t('302 Found'),
      303 => t('303 See Other'),
      304 => t('304 Not Modified'),
      305 => t('305 Use Proxy'),
      307 => t('307 Temporary Redirect'),
    ];
    return isset($codes[$code]) ? $codes[$code] : $codes;
  }

}
