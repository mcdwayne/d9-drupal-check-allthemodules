<?php

namespace Drupal\Tests\admin_menu_search\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Unit test for MenuTree.
 */
class MenuTreeTest extends UnitTestCase {

  /**
   * Unit test setup.
   */
  protected function setUp() {
    parent::setUp();
    $this->menu_tree = $this->getMockBuilder('Drupal\admin_menu_search\MenuTree')
      ->setMethods(['getCache', 'setCache', 'getToolbarMenuTree', 't'])
      ->disableOriginalConstructor()
      ->getMock();
    $this->menu_tree->expects($this->any())
      ->method('getCurrentLanguageId')
      ->willReturn('en');
    $this->menu_tree->expects($this->any())
      ->method('t')
      ->will($this->returnCallback([$this, 'returnCallbackT']));
  }

  /**
   * Custom return callback for t().
   *
   * @return string
   *   String
   */
  public function returnCallbackT() {
    $args = func_get_args();

    return $args[0];
  }

  /**
   * Test one level of menu tree.
   *
   * Test one level of menu tree without cache.
   * Also avoid indexing dynamic cache flush routes.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\MenuTree
   */
  public function testOneLevelBuildMenuTree() {
    $expected_array = [
      [
        'name' => 'menu.one',
        'title' => 'Menu One',
        'parameters' => [],
      ],
      [
        'name' => 'menu.two',
        'title' => 'Menu Two',
        'parameters' => [],
      ],
    ];
    $this->menu_links = $this->getMockBuilder('Drupal\Core\Menu\MenuLinkTreeElement')
      ->disableOriginalConstructor()
      ->setMethods(['getPluginDefinition'])
      ->getMock();
    $this->menu_links->expects($this->at(0))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.one',
          'title' => 'Menu One',
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(1))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.two',
          'title' => 'Menu Two',
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(2))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.flush',
          'title' => 'Menu Flush',
          'route_parameters' => [],
      ]);
    $tree = [
      (object) [
        'link' => $this->menu_links,
        'subtree' => []
      ],
      (object) [
        'link' => $this->menu_links,
        'subtree' => []
      ],
      (object) [
        'link' => $this->menu_links,
        'subtree' => []
      ],
    ];
    $actual_array = [];
    $this->menu_tree->buildMenuTreeIndex($tree, $actual_array);
    $this->assertArrayEquals($expected_array, $actual_array);
  }

  /**
   * Test menu tree without cache and having translatable title.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\MenuTree
   */
  public function testBuildMenuTreeWithTranslatableTitle() {
    $expected_array = [
      [
        'name' => 'menu.one',
        'title' => 'Menu One',
        'parameters' => [],
      ],
      [
        'name' => 'menu.two',
        'title' => 'Menu Two',
        'parameters' => [],
      ],
    ];
    $this->menu_links = $this->getMockBuilder('Drupal\Core\Menu\MenuLinkTreeElement')
      ->disableOriginalConstructor()
      ->setMethods(['getPluginDefinition'])
      ->getMock();
    $translatable_title = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslatableMarkup')
      ->disableOriginalConstructor()
      ->setMethods(['render'])
      ->getMock();
    $translatable_title->expects($this->at(0))
      ->method('render')
      ->willReturn('Menu One');
    $translatable_title->expects($this->at(1))
      ->method('render')
      ->willReturn('Menu Two');
    $this->menu_links->expects($this->at(0))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.one',
          'title' => $translatable_title,
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(1))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.two',
          'title' => $translatable_title,
          'route_parameters' => [],
      ]);
    $tree = [
      (object) [
        'link' => $this->menu_links,
        'subtree' => []
      ],
      (object) [
        'link' => $this->menu_links,
        'subtree' => []
      ],
    ];
    $actual_array = [];
    $this->menu_tree->buildMenuTreeIndex($tree, $actual_array);
    $this->assertArrayEquals($expected_array, $actual_array);
  }

  /**
   * Test multi level of menu tree.
   *
   * Test multi level of menu tree without cache.
   * Also avoid indexing dynamic cron run routes.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\MenuTree
   */
  public function testMultiLevelBuildMenuTree() {
    $expected_array = [
      [
        'name' => 'menu.one',
        'title' => 'Menu One',
        'parameters' => [],
      ],
      [
        'name' => 'menu.one.one',
        'title' => 'Menu One » Menu One One',
        'parameters' => [],
      ],
      [
        'name' => 'menu.one.two',
        'title' => 'Menu One » Menu One Two',
        'parameters' => [],
      ],
      [
        'name' => 'menu.two',
        'title' => 'Menu Two',
        'parameters' => [],
      ],
      [
        'name' => 'menu.two.one',
        'title' => 'Menu Two » Menu Two One',
        'parameters' => [],
      ],
    ];
    $this->menu_links = $this->getMockBuilder('Drupal\Core\Menu\MenuLinkTreeElement')
      ->disableOriginalConstructor()
      ->setMethods(['getPluginDefinition'])
      ->getMock();
    $this->menu_links->expects($this->at(0))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.one',
          'title' => 'Menu One',
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(1))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.one.one',
          'title' => 'Menu One One',
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(2))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.one.two',
          'title' => 'Menu One Two',
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(3))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'admin_toolbar.run.cron',
          'title' => 'Run Cron',
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(4))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.two',
          'title' => 'Menu Two',
          'route_parameters' => [],
      ]);
    $this->menu_links->expects($this->at(5))
      ->method('getPluginDefinition')
      ->willReturn([
          'route_name' => 'menu.two.one',
          'title' => 'Menu Two One',
          'route_parameters' => [],
      ]);
    $tree = [
      (object) [
        'link' => $this->menu_links,
        'subtree' => [
          (object) [
            'link' => $this->menu_links,
            'subtree' => []
          ],
          (object) [
            'link' => $this->menu_links,
            'subtree' => []
          ],
        ],
      ],
      (object) [
        'link' => $this->menu_links,
        'subtree' => [
          (object) [
            'link' => $this->menu_links,
            'subtree' => []
          ],
        ],
      ],
      (object) [
        'link' => $this->menu_links,
        'subtree' => [
          (object) [
            'link' => $this->menu_links,
            'subtree' => []
          ],
        ],
      ],
    ];
    $actual_array = [];
    $this->menu_tree->buildMenuTreeIndex($tree, $actual_array);
    $this->assertArrayEquals($expected_array, $actual_array);
  }

  /**
   * Test menu tree with cache.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\MenuTree
   */
  public function testMenuTreeWithCache() {
    $expected_array = [
      [
        'name' => 'menu.one',
        'title' => 'Menu One',
        'parameters' => [],
      ],
      [
        'name' => 'menu.two',
        'title' => 'Menu Two',
        'parameters' => [],
      ],
    ];
    $this->menu_tree->expects($this->any())
      ->method('getCache')
      ->willReturn((object) [
        'data' => [
          [
            'name' => 'menu.one',
            'title' => 'Menu One',
            'parameters' => [],
          ],
          [
            'name' => 'menu.two',
            'title' => 'Menu Two',
            'parameters' => [],
          ],
        ]
      ]);
    $menu_tree_index = $this->menu_tree->getAdminToolbarMenuIndex();
    $this->assertArrayEquals($expected_array, $menu_tree_index);
  }

  /**
   * Test menu tree with cache.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\MenuTree
   */
  public function testMenuTreeWithoutCache() {
    $this->menu_tree = $this->getMockBuilder('Drupal\admin_menu_search\MenuTree')
      ->setMethods(['getCache', 'setCache', 'getToolbarMenuTree', 'buildMenuTreeIndex', 't'])
      ->disableOriginalConstructor()
      ->getMock();
    $expected_array = [
      [
        'name' => 'menu.one',
        'title' => 'Menu One',
        'parameters' => [],
      ],
      [
        'name' => 'menu.two',
        'title' => 'Menu Two',
        'parameters' => [],
      ],
    ];
    $this->menu_tree->expects($this->exactly(1))
      ->method('getCache')
      ->willReturn(NULL);
    $this->menu_tree->expects($this->exactly(1))
      ->method('getToolbarMenuTree')
      ->willReturn([
        (object) [
          'link' => [
              'route_name' => 'menu.one',
              'title' => 'Menu One',
              'route_parameters' => [],
          ],
          'subtree' => []
        ],
        (object) [
          'link' => [
              'route_name' => 'menu.two',
              'title' => 'Menu Two',
              'route_parameters' => [],
          ],
          'subtree' => []
        ],
      ]);
    $this->menu_tree->expects($this->exactly(1))
      ->method('buildMenuTreeIndex')
      ->will($this->returnCallback(function ($tree, &$menu_tree_index) {
        $menu_tree_index = [
          [
            'name' => 'menu.one',
            'title' => 'Menu One',
            'parameters' => [],
          ],
          [
            'name' => 'menu.two',
            'title' => 'Menu Two',
            'parameters' => [],
          ],
        ];
      }));
    $menu_tree_index = $this->menu_tree->getAdminToolbarMenuIndex();
    $this->assertArrayEquals($expected_array, $menu_tree_index);
  }

}
