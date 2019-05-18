<?php

namespace Drupal\Tests\amp\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\amp\Functional\AmpTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests AMP view mode.
 *
 * @group amp
 */
class AmpBodyTransformTest extends AmpTestBase {

  /**
   * Test the AMP body transformations.
   */
  public function testAmpBodyTransform() {

    // Create some input/output values.
    $path =  drupal_get_path('module', 'amp') . '/tests/fixtures/';
    $image1 = trim(file_get_contents($path . 'img-test-fragment.html'));
    $amp_image1 = trim(file_get_contents($path . 'img-test-fragment.html.out'));

    // Create a node to test AMP body embed values.
    $header = '<h2>AMP body transform</h2>';

    $body = $header;
    $body .= '<p>Eum ex nulla quae tincidunt utrum. Commodo cui ea luptatum modo quae tamen voco. Augue capto distineo eligo molior tamen virtus.</p>';
    $body .= $image1;

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'body' => ['value' => $body, 'format' => 'full_html'],
    ]);

    $node_url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['absolute' => TRUE])->toString();
    $amp_node_url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['absolute' => TRUE, 'query' => ['amp' => NULL]])->toString();

    // Check the markup of the full display mode.
    $this->drupalGet($node_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($header);
    $this->assertSession()->responseContains($image1);

    // Check the markup of the AMP display mode.
    $this->drupalGet($amp_node_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($header);
    $this->assertSession()->responseContains($amp_image1);

  }
}
