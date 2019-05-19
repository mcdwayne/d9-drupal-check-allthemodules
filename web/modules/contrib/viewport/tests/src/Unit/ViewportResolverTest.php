<?php

namespace Drupal\Tests\viewport\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\viewport\ViewportResolver;

/**
 * @coversDefaultClass \Drupal\viewport\ViewportResolver
 * @group viewport
 */
class ViewportResolverTest extends UnitTestCase {

  /**
   * The tested ViewportResolver class.
   *
   * @var \Drupal\viewport\ViewportResolver
   */
  protected $viewportResolver;

  /**
   * The mocked path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The mocked current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Instantiate mocks and stubs.
    $configuredPaths = "<front>\n/node/*\n/present-path";
    $this->configFactory = $this->getConfigFactoryStub(array(
      'viewport.settings' => array(
        'selected_pages' => $configuredPaths,
        'width' => '1600',
        'height' => 'device-height',
        'initial_scale' => '2.0',
        'minimum_scale' => '1.0',
        'maximum_scale' => '5.0',
        'user_scalable' => TRUE,
      ),
    ));

    $requestStack = $this->getMock('\Symfony\Component\HttpFoundation\RequestStack');
    $this->currentPathStack = $this->getMock('\Drupal\Core\Path\CurrentPathStack', array(), [$requestStack]);
    $this->currentPathStack->expects($this->any())
      ->method('getPath')
      ->will($this->onConsecutiveCalls('/present-path', '/not-present-path'));

    // Mock PathMatcher class directly (instead of interface) to try and add
    // some coverage of '<front>'.
    $this->pathMatcher = $this->getMock('\Drupal\Core\Path\PathMatcher', array('getFrontPagePath'), array(), '', FALSE);
    $this->pathMatcher->expects($this->any())
      ->method('getFrontPagePath')
      ->will($this->returnValue('/frontpage-path'));

    $this->viewportResolver = new ViewportResolver($this->pathMatcher, $this->currentPathStack, $this->configFactory);
  }

  /**
   * Tests isPathSelected method solves filters out properly configured paths.
   */
  public function testIsPathSelected() {
    $this->assertTrue($this->viewportResolver->isPathSelected('/present-path'), 'Check for configured path.');
    $this->assertFalse($this->viewportResolver->isPathSelected('/not-present-path'), 'Check for non-configured path.');
  }

  /**
   * Tests isPathSelected method solves paths passed as uppercase.
   */
  public function testIsPathSelectedNormalisesPathString() {
    $this->assertTrue($this->viewportResolver->isPathSelected('/pReSent-PATH'), 'Check for configured path.');
  }

  /**
   * Tests isPathSelected method uses current path if none provided.
   */
  public function testIsPathSelectedWithoutPathProvided() {
    $this->assertTrue($this->viewportResolver->isPathSelected(), 'Check configured path as current path.');
    $this->assertFalse($this->viewportResolver->isPathSelected(), 'Check non-configured path as current path.');
  }

  /**
   * Tests isPathSelected matches frontpage paths not explicitly configured.
   *
   * Code smell?: This test is not really reliable. As it stands it relies on
   * the "<front>" translation to the current frontpage path being done by the
   * PathMatcher class. The mocks in this test overwrite the getFrontPagePath
   * method to return the one desired, but that wouldn't ensure correct behavior
   * if the current path stack service in Drupal changed to a different class.
   */
  public function testIsPathSelectedDetectsFrontPage() {
    $this->assertTrue($this->viewportResolver->isPathSelected('/frontpage-path'), 'Check non-configured path as front page path.');
  }

  /**
   * Tests generateViewportTagArray() generates the correct array structure.
   */
  public function testGenerateViewportTagArray() {
    // Check the tag array returned is exactly as expected, including the order
    // in which 'content' properties are set, although there's no real need to
    // be so strict.
    $expected = array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'viewport',
        'content' => "width=1600, height=device-height, initial-scale=2.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes",
      ),
    );
    $this->assertArrayEquals($expected, $this->viewportResolver->generateViewportTagArray());
  }

}
