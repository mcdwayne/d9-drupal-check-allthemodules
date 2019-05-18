<?php

namespace Drupal\Tests\gridstack\Kernel\Views;

use Drupal\Core\Form\FormState;
use Drupal\views\Views;
use Drupal\Tests\blazy\Kernel\Views\BlazyViewsTestBase;

/**
 * Test GridStack Views integration.
 *
 * @coversDefaultClass \Drupal\gridstack\Plugin\views\style\GridStackViews
 * @group gridstack
 */
class GridStackViewsTest extends BlazyViewsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_gridstack'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'filter',
    'link',
    'node',
    'text',
    'options',
    'entity_test',
    'views',
    'views_test_config',
    'views_test_data',
    'blazy',
    'blazy_test',
    'gridstack',
    'gridstack_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installConfig([
      'field',
      'node',
      'views',
      'blazy',
      'gridstack',
    ]);

    $bundle = $this->bundle;
    $this->setUpContentTypeTest($bundle);

    $data['settings'] = $this->getFormatterSettings();
    $this->display = $this->setUpFormatterDisplay($bundle, $data);

    $this->setUpContentWithItems($bundle);
  }

  /**
   * Make sure that the HTML list style markup is correct.
   */
  public function testViews() {
    $view = Views::getView('test_gridstack');
    $this->executeView($view);
    $view->setDisplay('default');

    $style_plugin = $view->style_plugin;

    $style_plugin->options['id'] = 'gridstack-hotdamn';

    $this->assertInstanceOf('\Drupal\gridstack\GridStackManagerInterface', $style_plugin->manager(), 'GridStackManager implements interface.');
    $this->assertInstanceOf('\Drupal\gridstack\Form\GridStackAdminInterface', $style_plugin->admin(), 'GridStackAdmin implements interface.');

    $form = [];
    $form_state = new FormState();
    $style_plugin->buildOptionsForm($form, $form_state);
    $this->assertArrayHasKey('closing', $form);

    $style_plugin->submitOptionsForm($form, $form_state);

    // Render.
    $render = $view->getStyle()->render();
    $this->assertEquals('gridstack', $render['#theme']);

    $style_plugin->options['vanilla'] = TRUE;
    $render = $view->getStyle()->render();
    $this->assertEquals('gridstack', $render['#theme']);

    $output = $view->preview();
    $output = $this->blazyManager->getRenderer()->renderRoot($output);
    $this->assertTrue(strpos($output, 'gridstack-hotdamn') !== FALSE, 'GridStack ID attribute is added to DIV.');

    $view->destroy();
  }

}
