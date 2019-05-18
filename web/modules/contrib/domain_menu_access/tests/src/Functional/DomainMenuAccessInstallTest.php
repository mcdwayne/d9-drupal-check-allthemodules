<?php

namespace Drupal\Tests\domain_menu_access\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Test installation of domain_menu_access module.
 *
 * @group domain_menu_access
 */
class DomainMenuAccessInstallTest extends BrowserTestBase {

  use DomainTestTrait;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The domain entity type storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['menu_link_content', 'domain', 'domain_access', 'domain_menu_access'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = $this->container->get('config.factory');
    $this->domainStorage = $this->container->get('entity_type.manager')->getStorage('domain');

    $this->domainCreateTestDomains(1, 'example');
  }

  /**
   * Test module install with domain access fields.
   */
  public function testDomainAccessFields() {
    $main_menu_id = 'main';

    $this->configFactory->getEditable('domain_menu_access.settings')
      ->set('menu_enabled', [$main_menu_id])
      ->save();

    $this->drupalLogin($this->rootUser);

    $add_link = Url::fromRoute('entity.menu.add_link_form', ['menu' => $main_menu_id]);
    $this->drupalGet($add_link);

    $domains = $this->domainStorage->loadMultiple();
    foreach ($domains as $domain) {
      $access_field = DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      $this->assertSession()->fieldExists($access_field);
    }

    $access_all_field = DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->assertSession()->fieldExists($access_all_field);
  }

}
