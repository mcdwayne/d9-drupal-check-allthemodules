<?php

namespace Drupal\whatsnew_dashboard\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\whatsnew_dashboard\SiteInterface;

/**
 * Defines the Site entity.
 *
 * @ConfigEntityType(
 *   id = "site",
 *   label = @Translation("Site"),
 *   module = "whatsnew_dashboard",
 *   handlers = {
 *     "list_builder" = "Drupal\whatsnew_dashboard\Controller\SiteListBuilder",
 *     "form" = {
 *       "default" = "Drupal\whatsnew_dashboard\Form\SiteForm",
 *       "delete" = "Drupal\whatsnew_dashboard\Form\SiteDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "site_url" = "site_url",
 *     "site_key" = "site_key",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/development/whatsnew/dashboard/site/{site}",
 *     "delete-form" = "/admin/config/development/whatsnew/dashboard/site/{site}/delete",
 *   }
 * )
 */
class Site extends ConfigEntityBase implements SiteInterface {

  /**
   * Site ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Site URL.
   *
   * @var string
   */
  protected $site_url;

  /**
   * Site Key.
   *
   * @var string
   */
  protected $site_key;

  /**
   * Return the site Url.
   *
   * @return string
   *   Site Url
   */
  public function getSiteUrl() {
    return $this->site_url;
  }

  /**
   * Return the site key.
   *
   * @return string
   *   Site key
   */
  public function getSiteKey() {
    return $this->site_key;
  }

  /**
   * Fetch a report from a site.
   *
   * @return array
   *   Report data stored in an associative array
   */
  public function fetchReport() {

    $uri = sprintf("%s/system/whatsnew?key=%s&timestamp=%d", $this->site_url, $this->site_key, time());
    if ($jsonResponse = @file_get_contents($uri)) {
      if ($response = json_decode($jsonResponse, TRUE)) {
        return $response;
      }
      else {
        \Drupal::logger('whatsnew')->error("Unable to parse response from {$this->site_url}");
        return FALSE;
      }
    }
    else {
      \Drupal::logger('whatsnew')->error("Unable to fetch response from {$this->site_url}");
      return FALSE;
    }

  }

}
