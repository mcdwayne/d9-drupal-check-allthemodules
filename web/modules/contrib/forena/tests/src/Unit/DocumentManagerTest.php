<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/15/2016
 * Time: 4:29 PM
 */

namespace Drupal\Tests\forena\Unit;

/**
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\DocManager
 */
class DocumentManagerTest extends FrxTestCase {
  public function testDocumentTypes() {
    $types = $this->documentManager()->getDocTypes();
    $types = array_combine($types, $types);
    $this->assertArrayHasKey('csv', $types);
    $this->assertArrayHasKey('html', $types);
    $this->assertArrayHasKey('xml', $types);
    $this->assertArrayHasKey('doc', $types);
  }

  public function testDocumentFactory() {
    $this->documentManager()->setDocument('csv');
    $doc = $this->getDocument();
    $this->assertInstanceOf('\Drupal\forena\FrxPlugin\Document\CSV', $doc);
    $this->assertEquals('csv', $this->documentManager()->getDocumentType());
    $this->setDocument('drupal');
    $this->assertEquals('drupal', $this->documentManager()->getDocumentType());
    $doc = $this->getDocument();
    $this->assertInstanceOf('\Drupal\forena\FrxPlugin\Document\Drupal', $doc);
  }
}