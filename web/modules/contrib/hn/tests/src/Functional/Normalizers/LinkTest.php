<?php

namespace Drupal\Tests\hn\Functional\Normalizers;

use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\Tests\BrowserTestBase;

/**
 * This tests the LinkNormalizer.
 *
 * @group hn
 */
class LinkTest extends BrowserTestBase {

  protected static $modules = ['link', 'hn', 'serialization'];

  /**
   * Test normalizing a LinkItem.
   */
  public function testNormalizing() {
    /** @var \Drupal\hn\Normalizer\LinkNormalizer $normalizer */
    $normalizer = \Drupal::service('serializer.normalizer.hn.link');
    $normalizer->setSerializer(\Drupal::service('serializer'));

    // Test a link without an uri. See issue #2921663.
    $link = new LinkItem(FieldItemDataDefinition::createFromDataType('field_item:link'));
    $result = $normalizer->normalize($link);
    $this->assertEquals(NULL, $result);

    // Test an external link.
    $link->set('uri', 'http://headless.ninja');
    $result = $normalizer->normalize($link);
    $this->assertEquals('http://headless.ninja', $result['uri']);
  }

}
