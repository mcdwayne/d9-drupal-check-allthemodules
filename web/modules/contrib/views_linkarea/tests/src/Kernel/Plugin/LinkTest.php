<?php


namespace Drupal\Tests\views_linkarea\Kernel\Plugin;

use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests the page display plugin.
 *
 * @group views
 * @see \Drupal\views_linkarea\Plugin\views\area\Link
 */
class LinkAreaTest extends ViewsKernelTestBase {

  protected $entityId;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'user', 'views_linkarea', 'views_test_linkarea'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_linkarea'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();
    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['views_test_linkarea']);
    }
    $random_label = $this->randomMachineName();
    $data = ['bundle' => 'entity_test', 'name' => $random_label];
    $entity_test = $this->container->get('entity.manager')
      ->getStorage('entity_test')
      ->create($data);
    $entity_test->save();
    $this->entityId = $entity_test->id();
    \Drupal::state()
      ->set('entity_test_entity_access.view.' . $entity_test->id(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['entity_test']);

    parent::setUpFixtures();
  }

  /**
   * Tests the area handler.
   *
   *  @param $options
   * @param $expected_text
   * @param $expected_link
   * @param bool $no_dest
   *
   * @dataProvider providerTestLinkArea
   */
  public function testLinkArea($options, $expected_text, $expected_link, $no_dest = TRUE) {
    if ($no_dest) {
      // Set destination query string to off.
      $options['destination'] = 0;
    }
    $view = Views::getView('test_entity_linkarea');
    $display =  $view->getDisplay();
    $plugin = Views::pluginManager('area')->createInstance('linkarea');
    // Initialize the plugin.
    $plugin->init($view, $display, $options);
    $build = $plugin->render();
    $this->assertEquals($expected_text, $build['#title']);
    $this->assertEquals($expected_link, $build['#url']->toString());
  }

  /**
   * @return array
   */
  public function providerTestLinkArea() {
    $data = [];
    $data[] = [
      [
        'path' => '<front>',
        'link_text' => 'SSSSSS',
      ],
      'SSSSSS',
      '/'
    ];
    $data[] = [
      [
        'path' => 'route:user.pass',
        'link_text' => '<b>Pass</b>',
      ],
      'Pass',
      '/user/password'
    ];
    $data[] = [
      [
        'path' => 'entity:entity_test/1',
        'link_text' => '<b>Pass</b>',
      ],
      'Pass',
      '/entity_test/1'
    ];
    return $data;
  }
}
