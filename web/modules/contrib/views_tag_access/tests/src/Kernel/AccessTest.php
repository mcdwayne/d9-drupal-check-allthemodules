<?php

namespace Drupal\tests\views_tag_access\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;
use Drupal\views\Entity\View;

/**
 * Access tests for Views Tag Access.
 *
 * @group ViewsTagAccess
 */
class AccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['views', 'views_ui', 'views_tag_access', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('view');
    $this->installEntitySchema('user');

    $this->installSchema('system', 'key_value_expire');
    $this->installSchema('system', 'sequences');

    $this->config('views_tag_access.settings')
      ->set('tags', ['test_tag', 'test_other'])
      ->save();

    // Create an account to get past user 1 having all permissions and set it to
    // the current user so that the private tempstore can be initialized.
    $this->container->get('current_user')->setAccount($this->createUser());
  }

  /**
   * Test that viewing a view is unaffected by our changes.
   *
   * @param string $tags
   *   A comma separated list of tags for the view.
   * @param FALSE|string $view_permission
   *   The permissions to use for the view or FALSE if there are no access
   *   checks.
   * @param array $user_permissions
   *   An array of permissions for the user.
   * @param bool $own_view
   *   Whether the view should be created by the user we are testing access for.
   * @param array $expected
   *   The expected result of the access check.
   *
   * @dataProvider viewAccessData
   */
  public function testViewAccess($tags, $view_permission, array $user_permissions, $own_view, $expected) {
    // Create our user with permissions.
    $user = $this->createUser($user_permissions);

    // If this is going to be a view they have created, update the current user.
    if ($own_view) {
      $this->container->get('current_user')->setAccount($user);
    }

    // Set up our view with permission access.
    /* @var \Drupal\views\Entity\View $view */
    $view = View::create([
      'id' => 'test_view',
      'label' => 'Test view',
      'tag' => $tags,
    ]);

    // Set an execute access permission if required.
    if ($view_permission) {
      $display_config = &$view->getDisplay('default');
      $display_config['display_options']['access'] = [
        'type' => 'perm',
        'options' => [
          'perm' => $view_permission,
        ],
      ];
    }

    // Save the view otherwise the delete operation will always deny.
    $view->save();

    // Check each of our access.
    foreach ($expected as $operation => $expected_access) {
      if ($operation == 'execute') {
        $result_access = $view->getExecutable()->access('default', $user);
      }
      else {
        $result_access = $view->access($operation, $user);
      }
      $this->assertEquals($expected_access, $result_access, "Check access for {$operation}");
    }
  }

  /**
   * Data provider for ::testViewAccess().
   */
  public function viewAccessData() {
    // Execution access variations.
    $access = [
      // View with no access should allow.
      'access-none' => [
        'view_permission' => FALSE,
        'user_permissions' => [],
        'expected' => ['execute' => TRUE],
      ],
      // View with permission access that user has should allow.
      'access-allow' => [
        'view_permission' => 'access user profiles',
        'user_permissions' => ['access user profiles'],
        'expected' => ['execute' => TRUE],
      ],
      // View with permission access that user doesn't have should deny.
      'access-deny' => [
        'view_permission' => 'administer users',
        'user_permissions' => [],
        'expected' => ['execute' => FALSE],
      ],
    ];

    // Tag variations.
    $tag = [
      // Doesn't have the tag for granular permissions.
      'tag-default' => [
        'tag' => 'default',
        'expected' => FALSE,
      ],
      // Has the tag for granular permissions.
      'tag-access' => [
        'tag' => 'test_tag',
        'expected' => TRUE,
      ],
      // Has the tag for granular permissions as well as another tag.
      'tag-both' => [
        'tag' => 'default,test_tag',
        'expected' => TRUE,
      ],
      // Has an access tag for granular permissions that the user doesn't have
      // permissions for.
      'tag-other' => [
        'tag' => 'test_other',
        'expected' => FALSE,
      ],
    ];

    // Full list of operations we check.
    $operations = ['update', 'duplicate', 'enable', 'disable', 'delete'];

    // Tag permission variations.
    $permission = [
      'permissions-none' => [
        'user_permissions' => [],
        'expected' => [],
      ],
      'permissions-tag-admin' => [
        'user_permissions' => ['administer views tagged test_tag'],
        'expected' => array_diff($operations, ['create']),
      ],
      'permissions-tag-update' => [
        'user_permissions' => ['update views tagged test_tag'],
        'expected' => ['update'],
      ],
      'permissions-tag-duplicate' => [
        'user_permissions' => ['duplicate views tagged test_tag'],
        'expected' => ['duplicate'],
      ],
      'permissions-tag-enable' => [
        'user_permissions' => ['enable views tagged test_tag'],
        'expected' => ['enable'],
      ],
      'permissions-tag-disable' => [
        'user_permissions' => ['disable views tagged test_tag'],
        'expected' => ['disable'],
      ],
      'permissions-tag-delete' => [
        'user_permissions' => ['delete views tagged test_tag'],
        'expected' => ['delete'],
      ],
    ];

    // Create permission variations.
    $create = [
      'create-yes' => TRUE,
      'create-no' => FALSE,
    ];

    // View creator variations.
    $own = [
      'own-yes' => TRUE,
      'own-no' => FALSE,
    ];

    // Administer views variations.
    $admin = [
      'admin-yes' => TRUE,
      'admin-no' => FALSE,
    ];

    // Build all our variations by combining each of the above.
    $data = [];
    foreach ($access as $access_name => $access_item) {
      foreach ($tag as $tag_name => $tag_item) {
        foreach ($permission as $perm_name => $perm_item) {
          foreach ($admin as $admin_name => $is_admin) {
            foreach ($own as $own_name => $is_own) {
              foreach ($create as $create_name => $can_create) {
                // Administrators need the administer views permission.
                $additional_permissions = $is_admin ? ['administer views'] : [];
                if ($can_create) {
                  $additional_permissions[] = 'create views';
                }

                // Merge our execute, tag permissions and default expectations.
                $expected = $access_item['expected'];
                // Admins and owners within the session always have access.
                if ($is_admin || $is_own) {
                  $expected += array_fill_keys($operations, TRUE);
                }
                if ($tag_item['expected']) {
                  $expected += array_fill_keys($perm_item['expected'], TRUE);
                }
                // By default everything is denied.
                $expected += array_fill_keys($operations, FALSE);
                // Admins and people with the create permission can create.
                $expected['create'] = $is_admin || $can_create;

                // Put all this information into our data entry.
                $data["{$access_name}:{$tag_name}:{$perm_name}:{$own_name}:{$create_name}:{$admin_name}"] = [
                  'tags' => $tag_item['tag'],
                  'view_permission' => $access_item['view_permission'],
                  // Merge the access item and permission check permissions.
                  'user_permissions' => array_merge($access_item['user_permissions'], $perm_item['user_permissions'], $additional_permissions),
                  'own_view' => $is_own,
                  'expected' => $expected,
                ];
              }
            }
          }
        }
      }
    }

    return $data;
  }

}
