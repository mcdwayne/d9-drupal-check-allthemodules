<?php

namespace Drupal\copyscape\Copyscape;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;

class Utility {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The $entityTypeManager definition.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Utility constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param \Drupal\Core\Session\AccountProxy $CurrentUser
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManager $entity_type_manager,
    AccountProxy $CurrentUser
  ) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $CurrentUser;
  }

  /**
   * Checks if the user is bypassing the copyscape checks.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The user proxy to check against.
   *
   * @return bool
   *   TRUE if the user can bypass copyscape.
   */
  public function userCanBypass(AccountProxyInterface $user) {
    // The admin user bypass.
    // if ($user->id() === '1') {
    //   return TRUE;
    // }

    $config = $this->configFactory->get('copyscape.settings');

    // Check in bypass users list.
    $bypassingUsers = explode(',', $config->get('users_bypass'));

    if (in_array($user->id(), $bypassingUsers)) {
      return TRUE;
    }

    // Check in bypass roles list.
    $bypassingRoles = $config->get('roles_bypass');
    $userRoles = $user->getRoles();

    foreach ($bypassingRoles as $rid => $value) {
      if ($value === 0) {
        continue;
      }

      if (isset($userRoles[$rid])) {
        return TRUE;
      }
    }

    // The user is not bypassing the copyscape verification.
    return FALSE;
  }

  /**
   * Check if a node bundle or field is selected for copyscape check.
   *
   * @param $bundle
   *   The node bundle name.
   *
   * @param string $field
   *   Optional parameter to check for a particular field in the bundle.
   *
   * @return bool
   */
  public function isCopyscaped($bundle, $field = NULL) {
    $config = $this->configFactory->get('copyscape.content');

    if ($config->get('reject_content') === 0) {
      return FALSE;
    }

    if ($config->get('reject_value') === '') {
      return FALSE;
    }

    $configName = "copyscape_ct.{$bundle}";
    $bundleSettings = $config->get($configName);

    // Check if atleast 1 field is set.
    if (empty($bundleSettings)) {
      return FALSE;
    }

    // Return true if a field is set to be copyscaped.
    foreach ($bundleSettings as $fieldMachineName => $value) {
      if ($fieldMachineName === $value) {
        if ($field === NULL) {
          return TRUE;
        }

        if ($fieldMachineName === $field) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Updates the 'copyscaped' value for a bundle field.
   *
   * @param string $bundle
   * @param string $field
   * @param integer $value
   */
  public function updateField($bundle, $field, $value) {
    $config = $this->configFactory->get('copyscape.content');
    $configData = $config->getRawData();

    $configName = "copyscape_ct.{$bundle}";

    // The bundle config is not saved yet, add it.
    if (!isset($configData[$configName])) {
      $configData[$configName] = [];
    }

    $bundleConfig = &$configData[$configName];

    // The field is not saved yet, add it.
    if (!isset($bundleConfig[$field])) {
      $bundleConfig[$field] = 0;
    }

    // Update the value.
    $bundleConfig[$field] = ($value === 1) ? $field : 0;

    // Update the config data.
    $this->configFactory->getEditable('copyscape.content')
      ->setData($configData)
      ->save();
  }

  /**
   * Returns an array with the copyscaped fields for the given bundle.
   *
   * @param string $bundle
   *
   * @return array
   */
  public function copyscapedFields($bundle) {
    $config = $this->configFactory->get('copyscape.content');

    $configName = "copyscape_ct.{$bundle}";
    $bundleSettings = $config->get($configName);

    $fields = [];
    foreach ($bundleSettings as $field => $value) {
      if ($field === $value) {
        $fields[] = $field;
      }
    }

    return $fields;
  }

  /**
   * Check a copyscape response.
   *
   * @param array $response
   *   The response was already parsed from XML to an array.
   *
   * @return bool
   */
  public function wasSuccessful(array $response) {
    $config = $this->configFactory->get('copyscape.content');

    // Copyscape returned no results.
    if (!isset($response['result'])) {
      return TRUE;
    }

    foreach ($response['result'] as $result) {
      // Copyscape didn't return a percentmatched.
      if (!isset($result['percentmatched'])) {
        continue;
      }

      // The percentmatched returned is higher (or equal) than the threshold..
      if ($config->get('reject_content')) {
        if ($result['percentmatched'] > $config->get('reject_value')) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Update (or initialize) the number of fails for the current user.
   *
   * @return int
   *   The updated fail count.
   */
  public function updateUserFails() {
    $user = $this->currentUser;

    $storage = $this->entityTypeManager->getStorage('copyscape_fail');

    $fails = $storage->loadByProperties(['uid' => $user->id()]);
    $fails = reset($fails);

    $count = empty($fails) ? 1 : $fails->getFails() + 1;

    // Create new fail, or update existing fail.
    if ($count == 1) {
      $fails = $storage->create([
        'uid' => $user->id(),
        'fails' => $count
      ]);
    }
    else {
      $fails->setFails($count);
    }

    $fails->save();

    return $count;
  }


  /**
   * Reset the number of fails for the current user.
   */
  public function resetUserFails() {
    $user = $this->currentUser;

    $storage = $this->entityTypeManager->getStorage('copyscape_fail');
    $fails = $user->loadByProperties(['uid' => $user->id()]);

    if (is_array($fails)) {
      foreach ($fails as $fail) {
        $fail->delete();
      }
    }
    else {
      $fails->delete();
    }
  }

  /**
   * Check if the user has reached the maximum accepted fail count.
   *
   * @param int $fails
   *
   * @return bool
   */
  public function failsCapped($fails) {
    $config = $this->configFactory->get('copyscape.settings');

    $maxFails = $config->get('failures');

    return $fails >= $maxFails && $maxFails > 0;
  }

  /**
   * Create a record for the copyscape results.
   *
   * @param array $results
   * @param int $nid
   *
   */
  public function saveResults($results, $nid) {
    $config = $this->configFactory->get('copyscape.settings');

    // Only save the response if the logging has been enabled in the settings.
    if (!$config->get('logs')) {
      return;
    }

    // Save only result array if results are set.
    if (isset($results['result'])) {
      $results = $results['result'];
    }

    $storage = $this->entityTypeManager->getStorage('copyscape_result');
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    $result = $storage->create([
      'nid' => $nid,
      'name' => $node->getTitle(),
      'response' => serialize($results),
      'uid' => $this->currentUser->id(),
    ]);

    $result->save();
  }
}
