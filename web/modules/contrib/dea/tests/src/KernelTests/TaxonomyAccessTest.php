<?php

namespace Drupal\Tests\dea\KernelTests;

use Drupal\dea\SolutionInterface;
use Drupal\dea_magic\EntityReferenceSolution;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Test DEA by simulating a "taxonomy access" behavior.
 */
class TaxonomyAccessTest extends KernelTestBase {

  /**
   * The admin group.
   *
   * Has *view* and *edit* access for all nodes.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $groupAdmin;

  /**
   * The root group.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $groupRoot;

  /**
   * Group A.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $groupA;

  /**
   * Group B.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $groupB;

  /**
   * Root user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userRoot;

  /**
   * User assigned to group A.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userA;

  /**
   * User assigned to group B.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userB;

  /**
   * Node accessible to group A.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $nodeA;

  /**
   * Node accessible to group A.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $nodeB;


  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'system',
    'field',
    'text',
    'node',
    'taxonomy',
    'dea',
    'dea_magic',
    'dea_request',
    'dea_test_taxonomy_access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('dea_test_taxonomy_access');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    Role::load('authenticated')
      ->grantPermission('access content')
      ->save();

    // Create the admin group. Grants view and edit access to articles.
    $this->groupAdmin = Term::create([
      'name' => 'Admin',
      'vid' => 'tags',
      'field_access_control' => [
        0 => [
          'entity_type' => 'node',
          'bundle' => 'article',
          'operation' => 'view',
        ],
        1 => [
          'entity_type' => 'node',
          'bundle' => 'article',
          'operation' => 'edit',
        ],
      ],
    ]);
    $this->groupAdmin->save();

    // Create the root group. Will not be attached to any users or nodes, but
    // pass it's `field_access_control` values to sub-groups.
    $this->groupRoot = Term::create([
      'name' => 'Root',
      'vid' => 'tags',
      'field_access_control' => [
        0 => [
          'entity_type' => 'node',
          'bundle' => 'article',
          'operation' => 'view',
        ],
      ],
    ]);
    $this->groupRoot->save();

    // Create group a, which will grant read access for node A by inheriting
    // root groups access control settings.
    $this->groupA = Term::create([
      'name' => 'Group A',
      'vid' => 'tags',
      'parent' => $this->groupRoot,
    ]);
    $this->groupA->save();

    // Create group a, which will grant read access for node B by inheriting
    // root groups access control settings.
    $this->groupB = Term::create([
      'name' => 'Group B',
      'vid' => 'tags',
      'parent' => $this->groupRoot,
    ]);
    $this->groupB->save();

    // Root user with view and edit rights for all nodes.
    $this->userRoot = User::create([
      'uid' => 2,
      'name' => 'Root User',
      'mail' => 'root@dea.test',
      'pass' => user_password(),
      'field_tags' => [
        0 => $this->groupAdmin,
      ],
    ]);
    $this->userRoot->save();

    // This user is allowed to view node A.
    $this->userA = User::create([
      'uid' => 3,
      'name' => 'User A',
      'mail' => 'a@dea.test',
      'pass' => user_password(),
      'field_tags' => [
        0 => $this->groupA,
      ],
    ]);
    $this->userA->save();

    // This user is allowed to view node B.
    $this->userB = User::create([
      'uid' => 4,
      'name' => 'User B',
      'mail' => 'b@dea.test',
      'pass' => user_password(),
      'field_tags' => [
        0 => $this->groupB,
      ],
    ]);
    $this->userB->save();

    // Node A - viewable by group A and admins only.
    $this->nodeA = Node::create([
      'title'     => 'Node A',
      'type'      => 'article',
      'uid'       => \Drupal::currentUser()->id(),
      'field_tags' => [
        0 => $this->groupAdmin,
        1 => $this->groupA,
      ],
    ]);
    $this->nodeA->save();

    // Node B - viewable by group B and admins only.
    $this->nodeB = Node::create([
      'title'     => 'Node B',
      'type'      => 'article',
      'uid'       => \Drupal::currentUser()->id(),
      'field_tags' => [
        0 => $this->groupAdmin,
        1 => $this->groupB,
      ],
    ]);
    $this->nodeB->save();
  }

  /**
   * Verify test setup executed properly.
   */
  public function testSetup() {
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('taxonomy_term');

    $children = $term_storage->loadChildren($this->groupRoot->id());
    $this->assertEquals(2, count($children), 'Root term has two children');
  }

  /**
   * Test inital access permissions.
   */
  public function testInitialAccess() {
    // Root user is allowed to view and edit everything.
    $this->assertTrue($this->nodeA->access('view', $this->userRoot), 'Root user is able to view node A.');
    $this->assertTrue($this->nodeB->access('view', $this->userRoot), 'Root user is able to view node B.');
    $this->assertTrue($this->nodeA->access('edit', $this->userRoot), 'Root user is able to edit node A.');
    $this->assertTrue($this->nodeB->access('edit', $this->userRoot), 'Root user is able to edit node B.');

    // User A is only allowed to view node A.
    $this->assertTrue($this->nodeA->access('view', $this->userA), 'User A is able to view node A.');
    $this->assertFalse($this->nodeB->access('view', $this->userA), 'User A is not able to view node B.');
    $this->assertFalse($this->nodeA->access('edit', $this->userA), 'User A is not able to edit node A.');
    $this->assertFalse($this->nodeB->access('edit', $this->userA), 'User A is not able to edit node B.');

    // User B is only allowed to view node B.
    $this->assertFalse($this->nodeA->access('view', $this->userB), 'User B is not able to view node A.');
    $this->assertTrue($this->nodeB->access('view', $this->userB), 'User B is able to view node B.');
    $this->assertFalse($this->nodeA->access('edit', $this->userB), 'User B is not able to edit node A.');
    $this->assertFalse($this->nodeB->access('edit', $this->userB), 'User B is not able to edit node B.');
  }

  /**
   * Test automatic access solutions.
   *
   * Based on requirements and grants the system is able to search for solutions
   * on how to grant or revoke access to certain resources.
   */
  public function testSolutionDiscovery() {
    /** @var \Drupal\dea\SolutionDiscoveryInterface $solution_manager */
    $solution_manager = $this->container->get('dea.discovery.solution');

    /** @var \Drupal\field\FieldStorageConfigStorage $field_storage */
    $field_storage = $this->container->get('entity_type.manager')->getStorage('field_config');
    $tags_field = $field_storage->load('user.user.field_tags');

    // There are 3 ways for User A to gain view access for node B.
    // - Become member of group B.
    // - Become member of group Root.
    // - Become admin.
    $view_expected = $this->solutionDescriptions([
      new EntityReferenceSolution($this->userA, $this->groupB, $tags_field),
      new EntityReferenceSolution($this->userA, $this->groupRoot, $tags_field),
      new EntityReferenceSolution($this->userA, $this->groupAdmin, $tags_field),
    ]);
    $view = $this->solutionDescriptions($solution_manager->solutions($this->nodeB, $this->userA, 'view'));
    $this->assertEquals($view_expected, $view, 'There are three solutions to give user A view access to node B.');

    // The only way to get write access is by becoming admin.
    $edit_expected = $this->solutionDescriptions([
      new EntityReferenceSolution($this->userA, $this->groupAdmin, $tags_field),
    ]);
    $edit = $this->solutionDescriptions($solution_manager->solutions($this->nodeB, $this->userA, 'edit'));
    $this->assertEquals($edit_expected, $edit, 'There is one solution to give user A edit access to node B.');
  }

  /**
   * Test the entity reference solution.
   */
  public function testEntityReferenceSolutionGrant() {
    /** @var \Drupal\field\FieldStorageConfigStorage $field_storage */
    $field_storage = $this->container->get('entity_type.manager')->getStorage('field_config');
    $tags_field = $field_storage->load('user.user.field_tags');

    // Create a "Add user A to group B"-solution and apply it.
    $solution = new EntityReferenceSolution($this->userA, $this->groupB, $tags_field);
    $solution->apply();

    $this->assertTrue($this->nodeB->access('view', $this->userA), 'View access of node B granted to user A.');
  }

  /**
   * Test the entity reference solution.
   */
  public function testEntityReferenceSolutionRevoke() {
    /** @var \Drupal\field\FieldStorageConfigStorage $field_storage */
    $field_storage = $this->container->get('entity_type.manager')->getStorage('field_config');
    $tags_field = $field_storage->load('user.user.field_tags');

    // Create a "Add user A to group A"-solution and revoke it.
    $solution = new EntityReferenceSolution($this->userA, $this->groupA, $tags_field);
    $solution->revoke();

    $this->assertFalse($this->nodeA->access('edit', $this->userA), 'View access of node A revoked from user A.');
  }

  /**
   * Transform a list of solution objects into strings.
   *
   * Transform a list of solution objects into strings and sort them for easy
   * comparison tests.
   *
   * @param \Drupal\dea\SolutionInterface[] $solutions
   *   The list of solutions.
   *
   * @return string[]
   *   The list of descriptions.
   */
  protected static function solutionDescriptions(array $solutions) {
    $descriptions = array_map(function (SolutionInterface $solution) {
      return $solution->applyDescription();
    }, $solutions);
    sort($descriptions);
    return $descriptions;
  }

}
