<?php
/**
 * @file
 * Contains \Drupal\mailmute\Plugin\Field\FieldType\SendStateItem.
 */

namespace Drupal\mailmute\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * The 'sendstate' entity field type references a send state plugin.
 *
 * @ingroup field
 *
 * @FieldType(
 *   id = "sendstate",
 *   label = @Translation("Send state"),
 *   description = @Translation("An e-mail send state."),
 *   default_widget = "sendstate",
 *   default_formatter = "sendstate",
 *   no_ui = TRUE,
 *   list_class = "\Drupal\mailmute\Plugin\Field\FieldType\SendStateFieldItemList"
 * )
 */
class SendStateItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * Definitions of all send states.
   *
   * @var array[]
   *   Definition info arrays, keyed by plugin ID.
   */
  protected $sendstateDefinitions = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->sendstateDefinitions = \Drupal::service('plugin.manager.sendstate')->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['plugin_id'] = DataDefinition::create('string')
      ->setLabel(new TranslationWrapper('The ID of a SendState plugin'));

    $properties['configuration'] = DataDefinition::create('map')
      ->setLabel(new TranslationWrapper('Serialized plugin configuration, with a structure matching the referenced plugin'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        // The columns reference a SendState plugin ID and configuration.
        'plugin_id' => array(
          'description' => 'The ID of a SendState plugin',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
        'configuration' => array(
          'description' => 'Serialized plugin configuration, with a structure matching the referenced plugin',
          'type' => 'blob',
          'serialize' => TRUE,
          'not null' => TRUE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    // Return the plugin IDs of all states.
    return array_keys($this->sendstateDefinitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    // Return the labels of all states.
    return array_map(function($definition) {
      return $definition['label'];
    }, $this->sendstateDefinitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    // Filter states by access and return the plugin IDs.
    return array_keys($this->getSettableSendStates($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    // Filter states by access and return the labels.
    return array_map(function($definition) {
      return $definition['label'];
    }, $this->getSettableSendStates($account));
  }

  /**
   * Returns the send state definitions to which a given account has access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return array
   *   A subset of the state definitions.
   */
  protected function getSettableSendStates(AccountInterface $account) {
    return array_filter(
      $this->sendstateDefinitions,
      function($sendstate) use ($account) {
        return $this->hasChangeAccess($account, $sendstate);
      }
    );
  }

  /**
   * Check that an account has access to change to a given send state.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param array $sendstate
   *   The send state plugin definition.
   *
   * @return bool
   *   Whether the account may set the send state.
   */
  protected function hasChangeAccess(AccountInterface $account, array $sendstate) {
    // Keeping the current plugin_id is always allowed.
    if (isset($this->plugin_id) && $this->plugin_id == $sendstate['id']) {
      return TRUE;
    }

    // The admin permission allows setting any state.
    if ($account->hasPermission('administer mailmute')) {
      return TRUE;
    }

    // At least the "change own send state" permission is required.
    return isset($account) && empty($sendstate['admin']) && $account->hasPermission('change own send state');
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'plugin_id';
  }

}
