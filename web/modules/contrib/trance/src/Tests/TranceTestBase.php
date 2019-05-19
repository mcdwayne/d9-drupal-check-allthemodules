<?php

namespace Drupal\trance\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\trance\TranceInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Sets up content entity types and provides assert methods.
 */
abstract class TranceTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['trance', 'datetime', 'locale'];

  /**
   * The trance access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * Entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Bundle entity type id.
   *
   * @var string
   */
  protected $bundleEntityTypeId;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create trance_basic type.
    if ($this->profile != 'standard') {
      $this->drupalCreateTranceType([
        'type' => 'trance_basic',
        'name' => 'Trance type for tests',
        'display_submitted' => FALSE,
      ]);
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler($this->entityTypeId);
  }

  /**
   * Asserts that trance access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected trance access grants for the trance
   *   and account, with each key as the name of an operation (e.g. 'view',
   *   'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\trance\TranceInterface $trance
   *   The trance object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  protected function assertTranceAccess(array $ops, TranceInterface $trance, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEqual($result, $this->accessHandler->access($trance, $op, $account), $this->tranceAccessAssertMessage($op, $result, $trance->language()->getId()));
    }
  }

  /**
   * Asserts that trance create access correctly grants or denies access.
   *
   * @param string $bundle
   *   The trance bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the trance
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  protected function assertTranceCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEqual($result, $this->accessHandler->createAccess($bundle, $account, [
      'langcode' => $langcode,
    ]), $this->tranceAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which trance access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the trance
   *   to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the trance access permission test that was performed.
   */
  protected function tranceAccessAssertMessage($operation, $result, $langcode = NULL) {
    return format_string('Trance access returns @result with operation %op, language code %langcode.', [
      '@result' => $result ? 'true' : 'false',
      '%op' => $operation,
      '%langcode' => !empty($langcode) ? $langcode : 'empty',
    ]
    );
  }

  /**
   * Creates a trance based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the trance, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array, for example.
   *
   * @code
   *     $this->drupalCreateTrance([
   *       'name' => t('Hello, world!'),
   *       'type' => 'trance_test',
   *     ]);
   * @endcode
   *
   *  The following defaults are provided:
   *   - title: Random string.
   *   - type: 'trance_test'.
   *   - uid: The currently logged in user, or anonymous.
   *
   * @return \Drupal\trance\TranceInterface
   *   The created trance entity.
   */
  protected function drupalCreateTrance(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'name'     => $this->randomMachineName(8),
      'type'      => 'trance_test',
      'uid'       => \Drupal::currentUser()->id(),
    ];
    $trance = entity_create($this->entityTypeId, $settings);
    $trance->save();

    return $trance;
  }

  /**
   * Creates a custom trance type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\trance\TranceType
   *   Created trance type.
   */
  protected function drupalCreateTranceType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
        // @todo fix
      } while (TranceType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
    ];
    $type = entity_create($this->entityTypeId . '_type', $values);
    $status = $type->save();
    \Drupal::service('router.builder')->rebuild();

    $fm = new FormattableMarkup('Created the %label @type.', [
      '%label' => $this->entityTypeId,
      '@type' => $type->id(),
    ]);
    $this->assertEqual($status, SAVED_NEW, $fm->__toString());

    return $type;
  }

  /**
   * Get permission.
   *
   * @param \string $op
   *   Operation.
   */
  protected function getPermission(string $op) {
    switch ($op) {
      case 'view':
        $formattable = 'access :id content';
        break;

      case 'administer':
        $formattable = 'administer :id entities';
        break;

      case 'administer types':
        $formattable = 'administer :id types';
        break;

      case 'bypass':
        $formattable = 'bypass :id access';
        break;

      case 'add':
        $formattable = 'add :id entities';
        break;

      case 'delete':
        $formattable = 'delete :id entities';
        break;

      case 'update':
        $formattable = 'edit :id entities';
        break;

      case 'view unpublished':
        $formattable = 'view unpublished :id entities';
        break;

      default:
        return '';
    }
    $fm = new FormattableMarkup($formattable, [':id' => $this->entityTypeId]);
    return $fm->__toString();
  }

}
