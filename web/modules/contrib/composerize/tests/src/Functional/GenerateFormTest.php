<?php

namespace Drupal\Tests\composerize\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * @group composerize
 */
class GenerateFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['composerize'];

  public function testForm() {
    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/reports/composerize');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $value = $assert->fieldExists('json')->getValue();
    $this->assertNotEmpty($value);

    $json = Json::decode($value);
    $this->assertArrayHasKey("drupal/$this->profile", $json['require']);
  }

}
