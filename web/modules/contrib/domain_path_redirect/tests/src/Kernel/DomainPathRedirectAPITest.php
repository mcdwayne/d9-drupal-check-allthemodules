<?php

namespace Drupal\Tests\domain_path_redirect\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\domain_path_redirect\Entity\DomainPathRedirect;
use Drupal\Core\Language\Language;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Redirect entity and redirect API test coverage.
 *
 * @group redirect
 */
class DomainPathRedirectAPITest extends KernelTestBase {

  use DomainTestTrait;

  /**
   * The redirect entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $controller;

  /**
   * An array of all testing domains.
   *
   * @var array
   */
  protected $domains;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain_path_redirect',
    'redirect',
    'domain',
    'link',
    'field',
    'system',
    'user',
    'language',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('domain');
    $this->installEntitySchema('redirect');
    $this->installEntitySchema('domain_path_redirect');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['router']);

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    $this->domainCreateTestDomains(2);
    $this->domains = \Drupal::service('entity_type.manager')->getStorage('domain')->loadMultiple(NULL, TRUE);
    $this->controller = $this->container->get('entity_type.manager')->getStorage('domain_path_redirect');
  }

  /**
   * Test redirect entity logic.
   */
  public function testRedirectEntity() {
    $domain = reset($this->domains);
    $domain1 = end($this->domains);

    // Create a redirect and test if hash has been generated correctly.
    /** @var \Drupal\domain_path_redirect\Entity\DomainPathRedirect $redirect */
    $redirect = $this->controller->create([
      'domain' => $domain->id(),
    ]);
    $redirect->setSource('some-url', ['key' => 'val']);
    $redirect->setRedirect('node');
    $redirect->setLanguage(Language::LANGCODE_NOT_SPECIFIED);
    $redirect->save();
    $this->assertEquals(DomainPathRedirect::generateDomainHash('some-url', $domain->id(), ['key' => 'val'], Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    // Update the redirect source query and check if hash has been updated as
    // expected.
    $redirect->setSource('some-url', ['key1' => 'val1']);
    $redirect->save();
    $this->assertEquals(DomainPathRedirect::generateDomainHash('some-url', $domain->id(), ['key1' => 'val1'], Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    // Update the redirect source path and check if hash has been updated as
    // expected.
    $redirect->setSource('another-url', ['key1' => 'val1']);
    $redirect->save();
    $this->assertEquals(DomainPathRedirect::generateDomainHash('another-url', $domain->id(), ['key1' => 'val1'], Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    // Update the redirect language and check if hash has been updated as
    // expected.
    $redirect->setLanguage('de');
    $redirect->save();
    $this->assertEquals(DomainPathRedirect::generateDomainHash('another-url', $domain->id(), ['key1' => 'val1'], 'de'), $redirect->getHash());

    // Update the redirect domain id and check if hash has been updated as
    // expected.
    $redirect->setDomain($domain1);
    $redirect->save();
    $this->assertEquals(DomainPathRedirect::generateDomainHash('another-url', $domain1->id(), ['key1' => 'val1'], 'de'), $redirect->getHash());

    // Create a few more redirects to test the select.
    for ($i = 0; $i < 5; $i++) {
      $redirect = $this->controller->create([
        'domain' => $domain->id(),
      ]);
      $redirect->setSource($this->randomMachineName());
      $redirect->save();
    }

    /** @var \Drupal\domain_path_redirect\DomainPathRedirectRepository $repository */
    $repository = \Drupal::service('domain_path_redirect.repository');
    $redirect = $repository->findMatchingRedirect('another-url', $domain1->id(), ['key1' => 'val1'], 'de');
    if (!empty($redirect)) {
      $this->assertEquals($redirect->getSourceUrl(), '/another-url?key1=val1');
    }
    else {
      $this->fail(t('Failed to find matching redirect.'));
    }

    // Load the redirect based on url.
    $redirects = $repository->findBySourcePath('another-url');
    $redirect = array_shift($redirects);
    if (!empty($redirect)) {
      $this->assertEquals($redirect->getSourceUrl(), '/another-url?key1=val1');
    }
    else {
      $this->fail(t('Failed to find redirect by source path.'));
    }
  }

}
