<?php

namespace Drupal\agreement\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Agreement entity.
 *
 * @ConfigEntityType(
 *   id = "agreement",
 *   label = @Translation("Agreement"),
 *   admin_permission = "administer agreements",
 *   handlers = {
 *     "list_builder" = "Drupal\agreement\Entity\AgreementListBuilder",
 *     "form" = {
 *       "default" = "Drupal\agreement\Entity\AgreementForm",
 *       "delete" = "Drupal\agreement\Entity\AgreementDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\agreement\Entity\Routing\AgreementRouteProvider",
 *     },
 *   },
 *   config_prefix = "agreement",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/people/agreement/manage/{agreement}/delete",
 *     "edit-form" = "/admin/config/people/agreement/manage/{agreement}",
 *     "collection" = "/admin/config/people/agreement/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "path",
 *     "settings",
 *     "agreement",
 *   }
 * )
 */
class Agreement extends ConfigEntityBase {

  /**
   * The machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * Agreement frequency setting.
   *
   * @return bool
   *   TRUE if the agreement is configured for users to agree only once.
   */
  public function agreeOnce() {
    $settings = $this->getSettings();
    return $settings['frequency'] == -1 ? TRUE : FALSE;
  }

  /**
   * Agreement frequency timestamp.
   *
   * @return int
   *   The timestamp modifier to use for the frequency.
   */
  public function getAgreementFrequencyTimestamp() {
    $timestamp = 0;
    $settings = $this->getSettings();
    if ($settings['frequency'] > 0) {
      $timestamp = round(time() - ($settings['frequency'] * 24 * 60 * 60));
    }

    return max($settings['reset_date'], $timestamp);
  }

  /**
   * Returns the settings as an array.
   *
   * @return array
   *   The stored settings or some sane defaults.
   */
  public function getSettings() {
    $defaults = $this->getDefaultSettings();
    $settings = $this->get('settings');
    if ($settings === NULL) {
      $settings = [];
    }

    return NestedArray::mergeDeep($defaults, $settings);
  }

  /**
   * Find if the agreement applies to an user account by role.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check roles for.
   *
   * @return bool
   *   TRUE if the user account has a role configured for this agreement.
   */
  public function accountHasAgreementRole(AccountProxyInterface $account) {
    $account_roles = $account->getRoles();

    $settings = $this->getSettings();
    $roles = $settings['roles'];
    $has_roles = array_intersect($roles, $account_roles);
    return !empty($has_roles);
  }

  /**
   * Get a formatted visibility pages as a string.
   *
   * @return string
   *   Get the visibility pages setting as a string.
   */
  public function getVisibilityPages() {
    $settings = $this->getSettings();
    return html_entity_decode(strtolower(implode("\n", $settings['visibility']['pages'])));
  }

  /**
   * Get the visibility setting.
   *
   * @return int
   *   The visibility setting: 0 for match all except, and 1 for match any.
   */
  public function getVisibilitySetting() {
    $settings = $this->getSettings();
    return $settings['visibility']['settings'];
  }

  /**
   * Provides default keys for settings.
   *
   * @return array
   *   Default keys and values for settings array.
   *
   * @internal
   */
  public static function getDefaultSettings() {
    return [
      'frequency' => -1,
      'title' => '',
      'format' => '',
      'submit' => '',
      'checkbox' => '',
      'success' => '',
      'revoked' => '',
      'failure' => '',
      'destination' => '',
      'recipient' => '',
      'roles' => [],
      'reset_date' => 0,
      'visibility' => [
        'settings' => -1,
        'pages' => [],
      ],
    ];
  }

}
