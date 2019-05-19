<?php

namespace Drupal\Tests\lopd\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\lopd\LopdServiceInterface;

/**
 * Test for testing the LopdService service.
 *
 * @coversDefaultClass Drupal\lopd\LopdService
 * @group lopd
 */
class LopdServiceKernelTest extends KernelTestBase {

  /**
   * The user object.
   *
   * @var $user
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array modules
   */
  public static $modules = ['lopd', 'user', 'system'];

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installSchema('lopd', ['lopd']);

    $this->user = User::create([
      'name' => 'user_name',
      'status' => 1,
    ]);
    $this->user->save();
  }

  /**
   * Test the lopdRegisterLogin method.
   */
  public function testLopdRegisterLogin() {
    $result = \Drupal::service('lopd.operation')
      ->lopdRegisterLogin($this->user);
    $this->assertNotNull($result);
  }

  /**
   * Test the lopdRegisterOperation method.
   *
   * @dataProvider LopdOperationTypeProvider
   */
  public function testLopdRegisterOperation($operation) {
    $result = \Drupal::service('lopd.operation')
      ->lopdRegisterOperation($this->user, $operation);
    $this->assertNotNull($result);

    // Check that the data introduced is correct:
    $lopd_entry = $this->getLOPDEntries('', 1)->fetchObject();
    $this->checkOperation($lopd_entry, $operation);
  }

  /**
   * Test the lopdRegisterLogout method.
   */
  public function testLopdRegisterLogout() {
    $result = \Drupal::service('lopd.operation')
      ->lopdRegisterLogout($this->user);
    $this->assertNotNull($result);
  }

  /**
   * Test the lopdDeleteRegisters method.
   */
  public function testLopdDeleteRegisters() {
    // Check that lopd_messages_to_keep variable is set correctly:
    $allowed_values = array(2, 3, 4, 5);
    $lopd_config = $this->config('lopd.settings');

    foreach ($allowed_values as $value) {
      // Set the the new configuration.
      $lopd_config->set('messages_to_keep', $value)->save();

      $max_timestamp = strtotime("- $value years");
      $error_message = new FormattableMarkup("Cron proccess didn't remove a entry less than @maxtime for @value value",
        array('@maxtime' => $max_timestamp, '@value' => $value));

      $this->createRandomEntries(15);
      \Drupal::service('lopd.operation')->lopdDeleteRegisters();

      $entries = $this->getLOPDEntries()->fetchAll();
      foreach ($entries as $entry) {
        $this->assertAttributeGreaterThanOrEqual($max_timestamp, 'timestamp', $entry, $error_message);
      }
    }
  }

  /**
   * Provides a list of LOPD operation types.
   */
  public function LopdOperationTypeProvider() {
    return [
      [ LopdServiceInterface::LOPD_OPERATION_LOGIN ],
      [ LopdServiceInterface::LOPD_OPERATION_LOGOUT ],
      [ LopdServiceInterface::LOPD_OPERATION_LOGIN_FAILED ],
    ];
  }

  /**
   * Create random LOPD entries into DB. This is used to simulate some test as
   * testDeleteLopdEntriesWithCron.
   * @param $num_entries
   *         Number of entries set into DB.
   */
  protected function createRandomEntries($num_entries) {
    for ($i = 0; $i < $num_entries; $i++) {
      \Drupal::database()->insert('lopd')
        ->fields(array(
          'uid' => 1,
          'authname' => 'user',
          'ip' => \Drupal::request()->getClientIp(),
          'operation' => 'operation',
          'timestamp' => mt_rand(0, time())
        ))
        ->execute();
    }
  }

  /**
   * Check a $operation row in DB. This is used to check that a lopd entry is correct.
   * @param  Object $lopd_entry
   *         Obeject with data of a LOPD entry in DB.
   * @param  String $operation_type
   *         LOPD operation type.
   */
  protected function checkOperation($lopd_entry, $operation_type) {
    // Check $operation_object is not null:
    $this->assertNotNull($lopd_entry,
      new FormattableMarkup('@operation: LOPD entry object returned from DB', array('@operation' => $operation_type)),
      'Lopd');

    // Check $operation_object timestamp field:
    $this->assertNotNull($lopd_entry->timestamp,
      new FormattableMarkup('@operation: timestamp collumn is set', array('@operation' => $operation_type)),
      'Lopd');

    // Check $operation_object uid field:
    $this->assertNotNull($lopd_entry->uid,
      new FormattableMarkup('@operation: uid collumn is set', array('@operation' => $operation_type)),
      'Lopd');

    // Check $operation_object authname field:
    $this->assertNotNull($lopd_entry->authname,
      new FormattableMarkup('@operation: authname collumn is set', array('@operation' => $operation_type)),
      'Lopd');

    //Check $operation_object ip field:
    $this->assertNotNull($lopd_entry->ip,
      new FormattableMarkup('@operation: Ip collumn is set', array('@operation' => $operation_type)),
      'Lopd');

    //Check $operation_object operation name field:
    $this->assertEqual($lopd_entry->operation, $operation_type,
      new FormattableMarkup('The @operation operation has been logged', array('@operation' => $operation_type)), 'Lopd');
  }

  /**
   * Return de last entry to LOPD table. If $operation is set, it will
   * filtered for this $operation.
   *
   * @param $operation
   *        Operation to filter.
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function getLOPDEntries($operation = '', $limit = 0) {
    $query = \Drupal::database()->select('lopd', 'l')
      ->fields('l')
      ->orderBy('l.lopdid', 'DESC');
    if (!empty($operation)) {
      $query->codition('l.operation', $operation);
    }
    if (!empty($limit)) {
      $query->range(0, $limit);
    }

    return $query->execute();
  }

}
