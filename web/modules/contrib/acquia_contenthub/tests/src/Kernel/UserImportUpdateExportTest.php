<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class UserImportUpdateExportTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class UserImportUpdateExportTest extends ImportExportTestBase {

  protected $fixtures = [
    [
      'cdf' => 'user/user.json',
      'expectations' => 'expectations/user/user.php',
    ],
    [
      'cdf' => 'user/user_update.json',
      'expectations' => 'expectations/user/user_update.php',
    ],
    [
      'cdf' => 'user/user_no_email.json',
      'expectations' => 'expectations/user/user_no_email.php',
    ],
    [
      'cdf' => 'user/user_no_email_update.json',
      'expectations' => 'expectations/user/user_no_email_update.php',
    ],
    [
      'cdf' => 'user/user_email_match.json',
      'expectations' => 'expectations/user/user_email_match.php',
    ],
    [
      'cdf' => 'user/user_email_match_update.json',
      'expectations' => 'expectations/user/user_email_match_update.php',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'depcalc',
    'acquia_contenthub',
    'acquia_contenthub_subscriber',
  ];

  /**
   * UserData service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Entity Repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installSchema('acquia_contenthub_subscriber', 'acquia_contenthub_subscriber_import_tracking');
    $this->createUser(['uuid' => 'b7a60b03-3ae2-4480-b261-f72021817346', 'name' => 'foo', 'mail' => 'foo@foo.com']);

    $this->userData = $this->container->get('user.data');
    $this->entityRepository = $this->container->get('entity.repository');
  }

  /**
   * Tests "user" Drupal entity.
   *
   * @param int $delta
   *   Fixture delta.
   * @param int $update_delta
   *   "Update" fixture delta.
   * @param array $validate_data
   *   Data.
   * @param string $export_type
   *   Exported entity type.
   * @param string $export_uuid
   *   Entity UUID.
   * @param bool $compare_exports
   *   Runs extended fixture/export comparison. FALSE for mismatched uuids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider userImportExportDataProvider
   */
  public function testUserImportExport($delta, $update_delta, array $validate_data, $export_type, $export_uuid, $compare_exports = TRUE) {
    parent::contentEntityImportExport($delta, $validate_data, $export_type, $export_uuid, $compare_exports);
    parent::contentEntityImportExport($update_delta, $validate_data, $export_type, $export_uuid, $compare_exports);
  }

  /**
   * Tests User Data import/export.
   *
   * @param int $delta
   *   Fixture delta.
   * @param array $validate_data
   *   Data.
   * @param string $export_type
   *   Exported entity type.
   * @param string $export_uuid
   *   Entity UUID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider userDataImportExportDataProvider
   */
  public function testUserDataImportExport($delta, $validate_data, $export_type, $export_uuid) {
    parent::contentEntityImportExport($delta, $validate_data, $export_type, $export_uuid);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $user = $this->entityRepository->loadEntityByUuid('user', $validate_data[0]['uuid']);
    $expected_1 = ['enabled' => 1];
    $this->assertEquals($expected_1, $this->userData->get('contact', $user->id()));
    $expected_2 = [
      'attribute1' => '0',
      'attribute2' => '1',
      'attribute3' => TRUE,
      'attribute4' => 345,
      'attribute5' => [1, 3, 4, 6],
      'attribute6' => 'a string',
    ];
    $this->assertEquals($expected_2, $this->userData->get('test', $user->id()));
  }

  /**
   * Data provider for testUserImport.
   *
   * @return array
   *   Data provider set.
   */
  public function userImportExportDataProvider() {
    return [
      // Match on uuid, update username and email.
      [
        0,
        1,
        [['type' => 'user', 'uuid' => 'f150c156-ef63-4f08-8d69-f15e5ee11106']],
        'user',
        'f150c156-ef63-4f08-8d69-f15e5ee11106',
      ],
      // No-email address and then update the right user.
      [
        2,
        3,
        [['type' => 'user', 'uuid' => 'f150c156-ef63-4f08-8d69-f15e5ee11106']],
        'user',
        'f150c156-ef63-4f08-8d69-f15e5ee11106',
      ],
      // Pre-existing local user, match on email, update email.
      [
        4,
        5,
        [['type' => 'user', 'uuid' => 'b7a60b03-3ae2-4480-b261-f72021817346']],
        'user',
        'b7a60b03-3ae2-4480-b261-f72021817346',
        FALSE,
      ],
    ];
  }

  /**
   * Data provider for testUserDataImportExport.
   *
   * @return array
   *   Data provider set.
   */
  public function userDataImportExportDataProvider() {
    return [
      [
        0,
        [['type' => 'user', 'uuid' => 'f150c156-ef63-4f08-8d69-f15e5ee11106']],
        'user',
        'f150c156-ef63-4f08-8d69-f15e5ee11106',
      ],
    ];
  }

}
