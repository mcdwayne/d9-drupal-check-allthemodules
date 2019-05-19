<?php

namespace Drupal\Tests\nodeorder\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Tests user permissions.
 *
 * @group nodeorder
 */
class NodeorderPermissionsTest extends BrowserTestBase {

  use TaxonomyTestTrait {
    createVocabulary as drupalCreateVocabulary;
    createTerm as drupalCreateTerm;
  }

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'nodeorder'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Tests viewing a module configuration form.
   */
  public function testViewModuleConfigurationForm() {
    $this->drupalGet('/admin/config/content/nodeorder');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/content/nodeorder');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser(['administer nodeorder']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/content/nodeorder');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('xpath', "//form[@id='nodeorder-admin']");
  }

  /**
   * Tests viewing a term's nodes order page.
   */
  public function testViewOrderNodesPageInTerm() {
    // Orderable vocabulary.
    $vocabulary1 = $this->drupalCreateVocabulary();
    $this->configFactory->getEditable('nodeorder.settings')
      ->set('vocabularies', [$vocabulary1->id() => TRUE])
      ->save();
    $term1 = $this->drupalCreateTerm($vocabulary1);

    $vocabulary2 = $this->drupalCreateVocabulary();
    $term2 = $this->drupalCreateTerm($vocabulary2);

    $this->drupalGet('/taxonomy/term/' . $term1->id() . '/order');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/taxonomy/term/' . $term2->id() . '/order');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser(['administer taxonomy']);
    $this->drupalLogin($user);
    $this->drupalGet('/taxonomy/term/' . $term1->id() . '/order');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/taxonomy/term/' . $term2->id() . '/order');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser(['administer taxonomy', 'order nodes within categories']);
    $this->drupalLogin($user);
    $this->drupalGet('/taxonomy/term/' . $term1->id() . '/order');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/taxonomy/term/' . $term2->id() . '/order');
    $this->assertSession()->statusCodeEquals(403);
  }

}
