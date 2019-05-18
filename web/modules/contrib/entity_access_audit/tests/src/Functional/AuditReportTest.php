<?php

namespace Drupal\Tests\entity_access_audit\Functional;

use Drupal\local_testing\LocalTestingTrait;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional test for the audit reports.
 *
 * @group entity_access_audit
 */
class AuditReportTest extends BrowserTestBase {

  use LocalTestingTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'entity_access_audit',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'foo',
      'label' => 'bar',
    ])->save();
  }

  /**
   * Test the audit report.
   */
  public function testAuditReport() {
    $this->drupalLogin($this->createUser(['audit entity access']));

    $this->drupalGet('admin/reports/entity-access-audit');
    $this->assertSession()->pageTextContains('Content');
    $this->assertSession()->linkByHrefExists('/admin/reports/entity-access-audit/node');

    $this->clickLink('More Info');
    $this->assertSession()->pageTextContains('Content Access Audit');
    $this->assertSession()->pageTextContains('Anonymous user');
    $this->assertSession()->pageTextContains('Total access checks: 16');
  }

}
