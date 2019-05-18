<?php

namespace Drupal\Tests\multiversion\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\multiversion\Workspace\DefaultWorkspaceNegotiator;

/**
 * @coversDefaultClass \Drupal\multiversion\Workspace\DefaultWorkspaceNegotiator
 * @group multiversion
 */
class DefaultWorkspaceNegotiatorTest extends UnitTestCase {

  /**
   * The workspace negotiator.
   *
   * @var \Drupal\multiversion\Workspace\DefaultWorkspaceNegotiator
   */
  protected $workspaceNegotiator;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->setParameter('workspace.default', 1);
    \Drupal::setContainer($container);
    $this->workspaceNegotiator = new DefaultWorkspaceNegotiator();
    $this->workspaceNegotiator->setContainer($container);
    $this->request = Request::create('<front>');
  }

  /**
   * Tests the applies() method.
   */
  public function testApplies() {
    $this->assertTrue($this->workspaceNegotiator->applies($this->request));
  }

  /**
   * Tests the getWorkspaceId() method.
   */
  public function testGetWorkspaceId() {
    $this->assertSame(1, $this->workspaceNegotiator->getWorkspaceId($this->request));
  }

}
