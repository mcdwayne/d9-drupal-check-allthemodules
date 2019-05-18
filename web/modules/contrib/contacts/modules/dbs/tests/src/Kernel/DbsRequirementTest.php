<?php

namespace Drupal\Tests\contacts_dbs\Kernel;

use Drupal\contacts_dbs\Entity\DBSStatus;
use Drupal\contacts_dbs\Entity\DBSStatusInterface;
use Drupal\contacts_dbs\Entity\DBSWorkforce;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Test the checking of dbs requirements.
 *
 * @group contacts_dbs
 */
class DbsRequirementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'contacts_dbs',
    'datetime',
    'field',
    'options',
    'user',
    'system',
  ];

  /**
   * The dbs status owner.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The a dbs status to be checked.
   *
   * @var \Drupal\contacts_dbs\Entity\DBSStatusInterface
   */
  protected $dbsStatus;

  /**
   * The dbs manager.
   *
   * @var \Drupal\contacts_dbs\DBSManager
   */
  protected $dbsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('dbs_status');
    $this->installSchema('system', 'sequences');

    $this->user = User::create([
      'uid' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $this->user->save();

    $this->dbsStatus = DBSStatus::create([
      'workforce' => 'default',
      'uid' => $this->user->id(),
    ]);
    $this->dbsStatus->save();

    DBSWorkforce::create([
      'id' => 'default',
      'valid' => 1,
      'alternatives' => [],
    ])->save();
    DBSWorkforce::create([
      'id' => 'other',
      'valid' => 1,
      'alternatives' => [],
    ])->save();

    $this->dbsManager = $this->container->get('contacts_dbs.dbs_manager');

  }

  /**
   * Test checks outcome of DBS check start method for various situations.
   *
   * @param string $workforce
   *   The dbs workforce to check for.
   * @param bool $expected_started
   *   Whether we expect a new dbs check to be started.
   * @param bool $expected_created
   *   Whether we expect a new dbs status to be created.
   * @param string|null $status
   *   (Optional) The status to set the DBS Status to.
   * @param string|null $valid_at
   *   (Optional) A date increment to add to current time to check validity.
   *
   * @dataProvider dataOnStatusStart
   */
  public function testStatusStart($workforce, $expected_started, $expected_created, $status = NULL, $valid_at = NULL) {
    if ($status) {
      $this->dbsStatus->set('status', $status)->save();
    }

    if ($valid_at) {
      $interval = new \DateInterval($valid_at);
      $valid_at = new DrupalDateTime();
      $valid_at->add($interval);
      $valid_at = $valid_at->format(DBSStatusInterface::DATE_FORMAT);
    }

    // Check whether a new dbs check was started.
    $return = $this->dbsManager->start($this->user->id(), $workforce, $valid_at);
    static::assertEquals($expected_started, $return);

    // Check whether a new DBS Status was created as part of the check.
    $status = $this->dbsManager->getDbs($this->user->id(), $workforce);
    if ($expected_created) {
      static::assertEquals($status->id(), 2);
    }
    else {
      static::assertEquals($status->id(), 1);
    }
  }

  /**
   * Data provider for testStatusStart.
   */
  public function dataOnStatusStart() {
    $data['default_not_clear'] = [
      'workforce' => 'default',
      'expected_started' => TRUE,
      'expected_created' => FALSE,
    ];

    $data['default_clear'] = [
      'workforce' => 'default',
      'expected_started' => FALSE,
      'expected_created' => FALSE,
      'status' => 'dbs_clear',
    ];

    $data['default_disclosure_accepted'] = [
      'workforce' => 'default',
      'expected_started' => FALSE,
      'expected_created' => FALSE,
      'status' => 'disclosure_accepted',
    ];

    $data['default_clear_invalid'] = [
      'workforce' => 'default',
      'expected_started' => TRUE,
      'expected_created' => FALSE,
      'status' => 'dbs_clear',
      'valid_at' => 'P2Y',
    ];

    $data['other_clear'] = [
      'workforce' => 'other',
      'expected_started' => TRUE,
      'expected_created' => TRUE,
      'status' => 'dbs_clear',
    ];

    return $data;
  }

}
