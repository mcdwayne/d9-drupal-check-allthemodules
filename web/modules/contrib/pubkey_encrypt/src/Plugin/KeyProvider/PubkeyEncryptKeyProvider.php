<?php

namespace Drupal\pubkey_encrypt\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;
use Drupal\key\Exception\KeyValueNotSetException;
use Drupal\key\KeyInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Session\AccountInterface;

/**
 * Adds a key provider as per the requirements of Pubkey Encrypt module.
 *
 * @KeyProvider(
 *   id = "pubkey_encrypt",
 *   label = @Translation("Pubkey Encrypt"),
 *   description = @Translation("Stores and Retrieves the key as per the requirements of Pubkey Encrypt module."),
 *   storage_method = "pubkey_encrypt",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = FALSE
 *   }
 * )
 */
class PubkeyEncryptKeyProvider extends KeyProviderBase implements KeyPluginFormInterface, KeyProviderSettableValueInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $roleOptions = [];
    foreach (Role::loadMultiple() as $role) {
      $roleOptions[$role->id()] = $role->label();
    }
    unset($roleOptions[AccountInterface::ANONYMOUS_ROLE]);
    unset($roleOptions[AccountInterface::AUTHENTICATED_ROLE]);

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#description' => $this->t('Share keys would be generated and stored for all the users in this Role.'),
      '#options' => $roleOptions,
      '#default_value' => $this->getConfiguration()['role'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $key_value = '';

    $currentUserId = \Drupal::currentUser()->id();

    // Retrieve the stored Share keys for this key.
    $shareKeys = $this->configuration['share_keys'];

    // Put the Share keys in static cache for this page request.
    drupal_static("Share keys" . $key->id(), $shareKeys);

    // Retrieve the actual key value from the Share key of logged-in user.
    if (isset($shareKeys[$currentUserId])) {
      $shareKey = $shareKeys[$currentUserId];

      // The Private key of the user will be present here because of our
      // our event subscriber.
      $privateKey = $_COOKIE[\Drupal::currentUser()->id() . '_private_key'];

      // Delegate the task of encryption to perspective plugin.
      $config = \Drupal::config('pubkey_encrypt.initialization_settings');
      $manager = \Drupal::service('plugin.manager.pubkey_encrypt.asymmetric_keys');
      $plugin = $manager
        ->createInstance($config->get('asymmetric_keys_generator'), $config->get('asymmetric_keys_generator_configuration'));
      $key_value = $plugin
        ->decryptWithPrivateKey($shareKey, $privateKey);
    }
    else {
      drupal_set_message($this->t('You are attempting to access some encrypted data, but you are not a member of a role with access. Therefore, this data will not be displayed.'), 'warning');
    }

    return $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    // Retrieve all cached Share keys for this key, if made available by
    // getKeyValue.
    $cached_share_keys = &drupal_static("Share keys" . $key->id());

    $role = $this->configuration['role'];
    $shareKeys = [];
    $users = \Drupal::service('entity_type.manager')
      ->getStorage('user')
      ->loadMultiple();

    // Disable roles will only store Share keys for users with "administer
    // permissions" permission.
    $enabled_roles = \Drupal::config('pubkey_encrypt.admin_settings')
      ->get('enabled_roles');

    // Each user will have a Share key.
    foreach ($users as $user) {
      // Only if the specified role is enabled, generate Share keys for all
      // users from that role.  Also generate a Share key for any user with
      // "administer_permissions" permission since he should be given complete
      // complete control over all keys.
      if (($user->hasRole($role) && in_array($role, $enabled_roles)) || $user->hasPermission('administer permissions')) {
        $userId = $user->get('uid')->getString();

        // Check from the cache before generating any Share key to boost
        // performance.
        if (isset($cached_share_keys[$userId])) {
          $shareKeys[$userId] = $cached_share_keys[$userId];
          continue;
        }

        $publicKey = $user->get('field_public_key')->getString();

        // Delegate the task of encryption to perspective plugin.
        $config = \Drupal::config('pubkey_encrypt.initialization_settings');
        $manager = \Drupal::service('plugin.manager.pubkey_encrypt.asymmetric_keys');
        $plugin = $manager
          ->createInstance($config->get('asymmetric_keys_generator'), $config->get('asymmetric_keys_generator_configuration'));
        $shareKey = $plugin
          ->encryptWithPublicKey($key_value, $publicKey);
        $shareKeys[$userId] = $shareKey;
      }
    }

    // Store the Share keys.
    if ($this->configuration['share_keys'] = $shareKeys) {
      return TRUE;
    }
    else {
      throw new KeyValueNotSetException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    // Nothing needs to be done, since the value will have been deleted
    // with the Key entity.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function obscureKeyValue($key_value, array $options = []) {
    // Key values are not obscured when this provider is used.
    return $key_value;
  }

}
