<?php

namespace Drupal\Tests\og_sm_config\Kernel;

use Drupal\og_sm\OgSm;
use Drupal\Tests\og_sm\Kernel\OgSmKernelTestBase;

/**
 * Tests about the config defaults when new Site is created.
 *
 * @group og_sm
 */
class ConfigDefaultsTest extends OgSmKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_sm_config',
    'og_sm_config_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['og_sm_config_test']);
  }

  /**
   * Test the defaults hooks during Site creation.
   */
  public function testVariableDefaultsOnSiteCreation() {
    // Create a new Site.
    $type = $this->createGroupNodeType(OgSmKernelTestBase::TYPE_IS_GROUP);
    OgSm::setSiteType($type, TRUE);
    $type->save();
    $site = $this->createGroup($type->id());

    /* @var \Drupal\og_sm_config\Config\SiteConfigFactoryOverrideInterface $config_factory_override */
    $config_factory_override = $this->container->get('og_sm.config_factory_override');

    // Get the default variables as they should be have set during Site insert.
    $defaults = [
      'test_1' => TRUE,
      'test_2' => 'test value 2',
    ];

    // Verify that the global config matches with what is configured.
    $this->assertConfig($defaults, 'og_sm_config_test.settings');

    // Verify the site override takes over the global config when the site
    // config is not set yet.
    $config_factory_override->setSite($site);
    $this->assertConfig($defaults, 'og_sm_config_test.settings');

    // Override a variable with a site specific value.
    $site_config = $config_factory_override->getOverride($site, 'og_sm_config_test.settings');
    $site_defaults = $defaults;
    $site_defaults['test_1'] = FALSE;
    $site_config->set('test_1', $site_defaults['test_1'])->save();

    // Check that setting a site specific config value doesn't override the
    // global config.
    $config_factory_override->setSite();
    $this->assertConfig($defaults, 'og_sm_config_test.settings');

    // Verify that the site override now fetches the site specific
    // configuration.
    $config_factory_override->setSite($site);
    $this->assertConfig($site_defaults, 'og_sm_config_test.settings');

    // Verify that a other site still gets the global configuration.
    $second_site = $this->createGroup($type->id());
    $config_factory_override->setSite($second_site);
    $this->assertConfig($defaults, 'og_sm_config_test.settings');
  }

  /**
   * Asserts that the config contains the expected values.
   *
   * @param array $expected
   *   The expected values, keyed with name.
   * @param string $config_name
   *   The config object.
   */
  protected function assertConfig(array $expected, $config_name) {
    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory->reset();
    $config = $config_factory->get($config_name);
    foreach ($expected as $name => $value) {
      $this->assertEquals(
        $value,
        $config->get($name),
        'Site variable "' . $name . '"" is set to the default value "' . $value . '".'
      );
    }
  }

}
