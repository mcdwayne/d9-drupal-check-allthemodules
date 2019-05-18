<?php

namespace Drupal\Tests\hn\Kernel;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * HnFrontendKernelTest.
 *
 * @group hn_frontend
 */
class HnFrontendKernelTest extends HnKernelTestBase {
  /**
   * User who has access to view content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userWithAccess;

  /**
   * An anonymous user session.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * A array with all the routes which are installed by hn_frontend config.
   *
   * @var array
   */
  protected $baseRoutes;

  /**
   * A custom entity for route testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entityTest;

  /**
   * The current_user service, used to login and logout.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'hn',
    'hn_frontend',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    $this->installConfig('hn_frontend');
    $this->installConfig('user');

    // Create custom Entity.
    $entity_test = EntityTest::create();
    $entity_test->save();
    $id = $entity_test->id();
    $this->entityTest = EntityTest::load($id);

    // These routes are used in the function hasAccess, for every request
    // we need these routes to prevent loops.
    $this->baseRoutes = $this->config('hn_frontend.settings')->get('routes');

    // Give anonymous users permission to view test entities.
    $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access content');
    $anonymous_role->grantPermission('view test entity');
    $anonymous_role->save();

    $this->anonymousUser = new AnonymousUserSession();
    $this->userWithAccess = $this->createUser();

    $this->currentUser = $this->container->get('current_user');
  }

  /**
   * Test if user has access or get redirected.
   */
  public function testAccess() {
    $this->checkAccess(301, [], 'doesn\'t have access by default.');

    $this->checkAccess(200, ['entity.entity_test.canonical'], 'has access with explicit route.');

    $this->checkAccess(200, ['entity.*'], 'has access with wildcard.');

    $this->checkAccess(301, ['entity.*', '~entity.entity_test.canonical'], 'doesn\'t have access with exclusion.');

    $this->checkAccess(301, ['entity.*', '~entity.entity_test.*'], 'doesn\'t have access with wildcard exclusion.');

    $this->checkAccess(200, [
      'entity.*',
      '~entity.entity_test.*',
      'entity.entity_test.canonical',
    ], 'has access with an exception to a wildcard exclusion.');
  }

  /**
   * Tests if the route has to correct status.
   *
   * @param int $status
   *   The status code.
   * @param array $routes
   *   Array with the routes.
   * @param string $message_for_anonymous
   *   Message for assert of the anonymous user.
   */
  protected function checkAccess($status, array $routes, $message_for_anonymous) {
    // Save the routes config.
    $routes += $this->baseRoutes;
    $this->config('hn_frontend.settings')->setData(['routes' => $routes])->save();

    // Check if the status code is as expected.
    $this->assertEquals($status, $this->getStatusCodeForEntityRequest(), 'Anonymous user ' . $message_for_anonymous);

    // Log user in.
    $this->currentUser->setAccount($this->userWithAccess);

    // Logged in users should always have access.
    $this->assertEquals(200, $this->getStatusCodeForEntityRequest(), 'Authenticated user has access.');

    // Log user out.
    $this->currentUser->setAccount($this->anonymousUser);
  }

  /**
   * Requests the url of the entity and returns the status of the response.
   *
   * @return int
   *   The status code of the request.
   */
  protected function getStatusCodeForEntityRequest() {
    $response = $this->container->get('http_kernel')->handle(Request::create($this->entityTest->toUrl()->toString()));
    return $response->getStatusCode();
  }

}
