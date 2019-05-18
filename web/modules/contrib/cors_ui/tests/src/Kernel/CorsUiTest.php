<?php

namespace Drupal\Tests\cors_ui\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * The CORS UI test.
 *
 * @group cors_ui
 */
class CorsUiTest extends KernelTestBase {

  /**
   * The initials CORS configuration.
   *
   * @var array
   */
  protected $initialCorsConfig;

  /**
   * The config key where cors settings are stored.
   *
   * @var string
   */
  protected $configKey = 'cors_ui.configuration';

  /**
   * The config form class.
   *
   * @var string
   */
  protected $formClass = '\Drupal\cors_ui\Form\CorsConfigurationForm';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * Test the CORS UI.
   */
  public function testCorsUi() {
    $cors_parameter = $this->container->getParameter('cors.config');
    $this->assertTrue(count($cors_parameter) > 0);
    $this->assertFalse($this->container->has('http_middleware.cors'));

    // Install and run the install hook for the cors_ui module.
    $this->enableModules(['cors_ui']);
    module_load_install('cors_ui');
    cors_ui_install();

    $this->container->get('kernel')->rebuildContainer();
    $this->assertFalse($this->container->has('http_middleware.cors'));

    // The state of the config object on install should match what appears in
    // the container.
    $this->assertEquals($cors_parameter, $this->config($this->configKey)->get());

    $form_state = (new FormState())->setValues([
      'configuration' => [
        'enabled' => TRUE,
        'maxAge' => 500,
        'supportsCredentials' => FALSE,
        'allowedHeaders' => "foo\r\nbar",
        'allowedMethods' => "bar\nbaz",
        'allowedOrigins' => 'baz',
        'exposedHeaders' => 'qux',
      ],
    ]);
    $this->container->get('form_builder')->submitForm($this->formClass, $form_state);

    $expected_config = [
      'enabled' => TRUE,
      'maxAge' => 500,
      'supportsCredentials' => FALSE,
      'allowedHeaders' => ['foo', 'bar'],
      'allowedMethods' => ['bar', 'baz'],
      'allowedOrigins' => ['baz'],
      'exposedHeaders' => ['qux'],
    ];
    $this->assertEquals($expected_config, $this->config($this->configKey)->get());

    $this->container->get('kernel')->rebuildContainer();
    $this->assertEquals($expected_config, $this->container->getParameter('cors.config'));
    $this->assertTrue($this->container->has('http_middleware.cors'));
  }

}
