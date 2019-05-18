<?php

namespace Drupal\Tests\og_sm_path\Kernel;

use Drupal\og_sm\OgSm;
use Drupal\og_sm_path\OgSmPath;
use Drupal\Tests\og_sm\Kernel\OgSmKernelTestBase;

/**
 * Tests the change access callback.
 *
 * @group og_sm
 */
class ChangeAccessTest extends OgSmKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_sm_config',
    'ctools',
    'path',
    'pathauto',
    'token',
    'og_sm_path',
  ];

  /**
   * Non-site content type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $typeNotSite;

  /**
   * Site content type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $typeIsSite;

  /**
   * The Site node to test with.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $siteNode;

  /**
   * Extra Site node to test with.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $siteNode2;

  /**
   * The admin user to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userAdministrator;

  /**
   * Global user without Change All permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userWithoutChangeAllPermission;

  /**
   * Global user with Change All permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userWithChangeAllPermission;

  /**
   * The user that is the site 2 owner to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $site2OwnerWithoutPermission;

  /**
   * The user that is the owner to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $siteOwnerWithPermission;

  /**
   * The user with permission to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $siteUserWithoutChangePermissions;

  /**
   * The site user without the permission to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $siteUserWithChangePermission;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('og_membership');

    // Disable full access for Site owners.
    \Drupal::configFactory()->getEditable('og.settings')->set('group_manager_full_access', FALSE);

    $this->userAdministrator = $this->createUser([], NULL, TRUE);
    $this->userWithoutChangeAllPermission = $this->createUser(
      ['bypass node access', 'administer nodes']
    );
    $this->userWithChangeAllPermission = $this->createUser(
      [
        'bypass node access',
        'administer nodes',
        'change all site paths',
      ]
    );

    // Site owner.
    $this->site2OwnerWithoutPermission = $this->createUser();
    $this->siteOwnerWithPermission = $this->createUser(
      ['change own site paths']
    );

    // Create Sites.
    $site_type_manager = OgSm::siteTypeManager();
    $this->typeNotSite = $this->createGroupNodeType('not_site_type');
    $this->typeIsSite = $this->createGroupNodeType('is_site_type');
    $site_type_manager->setIsSiteType($this->typeIsSite, TRUE);
    $this->typeIsSite->save();
    $this->siteNode = $this->createGroup(
      $this->typeIsSite->id(),
      ['uid' => $this->siteOwnerWithPermission->id()]
    );
    $this->siteNode2 = $this->createGroup(
      $this->typeIsSite->id(),
      ['uid' => $this->site2OwnerWithoutPermission->id()]
    );

    // Site user with edit but NO path change permissions.
    $this->siteUserWithoutChangePermissions = $this->createGroupUser(
      [],
      [$this->siteNode],
      ['update group']
    );

    // Site user with change path permissions.
    $this->siteUserWithChangePermission = $this->createGroupUser(
      [],
      [$this->siteNode],
      ['update group', 'change site path']
    );
  }

  /**
   * Test the permissions helper.
   */
  public function testAccessChangePath() {
    // Global permissions.
    $this->assertTrue(
      OgSmPath::changeAccess($this->siteNode, $this->userAdministrator),
      'The platform administrator should have access.'
    );
    $this->assertTrue(
      OgSmPath::changeAccess($this->siteNode, $this->userAdministrator),
      'The platform administrator should have access.'
    );
    $this->assertFalse(
      OgSmPath::changeAccess($this->siteNode, $this->userWithoutChangeAllPermission),
      'Users without the global change all paths permission should NOT have access.'
    );
    $this->assertTrue(
      OgSmPath::changeAccess($this->siteNode, $this->userWithChangeAllPermission),
      'Users with the global change all paths permission should have access.'
    );

    // Site owner without change path permission.
    $this->assertFalse(
      OgSmPath::changeAccess($this->siteNode2, $this->site2OwnerWithoutPermission),
      'Site owner without global change own permission should NOT have access.'
    );

    // Site owner with permission.
    $this->assertTrue(
      OgSmPath::changeAccess($this->siteNode, $this->siteOwnerWithPermission),
      'Site owner with the global change own permission should have access.'
    );

    // Site member without permission.
    $this->assertFalse(
      OgSmPath::changeAccess($this->siteNode, $this->siteUserWithoutChangePermissions),
      'Site member without the Site change path permission should NOT have access.'
    );

    // Site member with permission.
    $this->assertTrue(
      OgSmPath::changeAccess($this->siteNode, $this->siteUserWithChangePermission),
      'Site member with the Site change path permission should have access.'
    );
  }

}
