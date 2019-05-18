<?php

namespace Drupal\Tests\pdb_vue\Unit\Plugin\Block;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pdb_vue\Plugin\Block\VueBlock;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Component\Uuid\UuidInterface;

/**
 * @coversDefaultClass \Drupal\pdb_vue\Plugin\Block\VueBlock
 * @group pdb_vue
 */
class VueBlockTest extends UnitTestCase {

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * Instance of the Plugin.
   *
   * @var \Drupal\pdb_vue\Plugin\Block\VueBlock
   */
  protected $plugin;

  /**
   * Create the setup for constants and configFactory stub.
   */
  protected function setUp() {
    parent::setUp();

    // Stub the Config Factory.
    $this->configFactory = $this->getConfigFactoryStub([
      'pdb_vue.settings' => [
        'development_mode' => 1,
        'use_spa' => 1,
        'spa_element' => '#page-wrapper',
      ],
    ]);

    // Mock the UUID service.
    $uuid = $this->prophesize(UuidInterface::CLASS);
    $uuid->generate()->willReturn('uuid');

    // Create a container needed by PdbBlock.
    $container = new ContainerBuilder();
    $container->set('uuid', $uuid->reveal());
    \Drupal::setContainer($container);

    $configuration = [
      'pdb_configuration' => [
        'testField' => 'test',
        'second_field' => 1,
      ],
    ];
    $plugin_id = 'pdb_vue';
    $plugin_definition = [
      'provider' => 'pdb_vue',
      'info' => [
        'machine_name' => 'vue-example-1',
        'component' => TRUE,
      ],
    ];

    $this->plugin = new VueBlock(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $this->configFactory
    );
  }

  /**
   * Tests the create method.
   *
   * @see ::create()
   */
  public function testCreate() {
    $configuration = [];
    $plugin_id = 'pdb_vue';
    $plugin_definition['provider'] = 'pdb_vue';

    $container = $this->prophesize(ContainerInterface::CLASS);
    $container->get('config.factory')->willReturn($this->configFactory);

    $instance = VueBlock::create(
      $container->reveal(),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->assertInstanceOf('Drupal\pdb_vue\Plugin\Block\VueBlock', $instance);
  }

  /**
   * Tests the build() method.
   */
  public function testBuild() {
    $expected = [
      '#allowed_tags' => ['vue-example-1'],
      '#markup' => '<vue-example-1 test-field="test" :second_field="1" instance-id="uuid"></vue-example-1>',
      '#attached' => [
        'drupalSettings' => [
          'pdbVue' => [
            'developmentMode' => 1,
            'spaElement' => '#page-wrapper',
          ],
          'pdb' => [
            'configuration' => [
              'uuid' => [
                'testField' => 'test',
                'second_field' => 1,
              ],
            ],
          ],
        ],
        'library' => [
          'pdb_vue/vue.spa-init',
        ],
      ],
    ];

    $return = $this->plugin->build();
    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the attachSettings() method.
   */
  public function testAttachSettings() {
    $component = [
      'machine_name' => 'vue-example-1',
      'component' => TRUE,
      'add_js' => [
        'footer' => [
          'vue-example-1.js' => [],
        ],
      ],
    ];

    $expected = [
      'drupalSettings' => [
        'pdbVue' => [
          'developmentMode' => 1,
          'spaElement' => '#page-wrapper',
        ],
      ],
    ];

    $return = $this->plugin->attachSettings($component);
    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the attachLibraries() method.
   *
   * @see ::create()
   */
  public function testAttachLibraries() {
    $component = [
      'machine_name' => 'vue-example-1',
      'component' => TRUE,
      'add_js' => [
        'footer' => [
          'vue-example-1.js' => [],
        ],
      ],
    ];

    $expected = [
      'library' => [
        'pdb/vue-example-1/footer',
        'pdb_vue/vue.spa-init',
      ],
    ];

    $return = $this->plugin->attachLibraries($component);
    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the attachLibraries() method of "libraries" with inline scripts.
   *
   * @see ::create()
   */
  public function testAttachLibrariesInline() {
    $component = [
      'machine_name' => 'vue-example-1',
      'component' => TRUE,
      'add_js' => [
        'footer' => [
          'vue-example-1.js' => [],
        ],
      ],
      'libraries' => [
        'pdb_vue/vuex',
      ],
    ];

    // Add the Vuex library between inline and spa-init.
    $expected = [
      'library' => [
        'pdb/vue-example-1/footer',
        'pdb_vue/vuex',
        'pdb_vue/vue.spa-init',
      ],
    ];

    $return = $this->plugin->attachLibraries($component);
    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the attachLibraries() method of "libraries" without inline scripts.
   *
   * @see ::create()
   */
  public function testAttachLibrariesNoInline() {
    $component = [
      'machine_name' => 'vue-example-1',
      'libraries' => [
        'pdb_vue/vuex',
      ],
    ];

    // The main Vue library should be attached in addition to the declared Vuex.
    $expected = [
      'library' => [
        'pdb_vue/vue',
        'pdb_vue/vuex',
      ],
    ];

    $return = $this->plugin->attachLibraries($component);
    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the buildPropertyString method.
   */
  public function testBuildPropertyString() {
    $expected = ' test-field="test" :second_field="1"';

    $return = $this->plugin->buildPropertyString();
    $this->assertEquals($expected, $return);
  }

  /**
   * Tests the convertKebabCase.
   *
   * @dataProvider convertKebabCaseProvider
   */
  public function testConvertKebabCase($value, $expected) {
    $return = $this->plugin->convertKebabCase($value);
    $this->assertEquals($expected, $return);
  }

  /**
   * Provider for testConvertKebabCase()
   */
  public function convertKebabCaseProvider() {
    return [
      ['simpleTest', 'simple-test'],
      ['easy', 'easy'],
      ['HTML', 'html'],
      ['simpleXML', 'simple-xml'],
      ['PDFLoad', 'pdf-load'],
      ['startMIDDLELast', 'start-middle-last'],
      ['AString', 'a-string'],
      ['Some4Numbers234', 'some4-numbers234'],
      ['TEST123String', 'test123-string'],
      ['hello_world', 'hello_world'],
      ['hello_-world', 'hello_-world'],
      ['-hello-world-', 'hello-world-'],
      ['hello_World', 'hello_-world'],
      ['HelloWorld', 'hello-world'],
      ['helloWorldFoo', 'hello-world-foo'],
      ['hello-world', 'hello-world'],
      ['myHTMLFiLe', 'my-html-fi-le'],
      ['aBaBaB', 'a-ba-ba-b'],
      ['BaBaBa', 'ba-ba-ba'],
      ['libC', 'lib-c'],
    ];
  }

}
