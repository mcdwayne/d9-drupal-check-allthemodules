<?php

namespace Drupal\Tests\services_path\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * @group services_path
 */
class ServicesPathFunctionalTest extends BrowserTestBase {

  public static $modules = [
    'ctools',
    'language',
    'node',
    'serialization',
    'services',
    'services_path',
    'services_path_test',
    'user'
  ];

  protected $user;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;


  public function setUp() {
    parent::setUp();

    $language = ConfigurableLanguage::createFromLangcode('ca');
    $language->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    \Drupal::configFactory()->getEditable('language.negotiation')
      ->set('url.prefixes.ca', 'ca')
      ->save();

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->user = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
    ]);

    $this->httpClient = $this->container->get('http_client_factory')
      ->fromOptions(['base_uri' => $this->baseUrl]);

  }


  function testCanonicalPath(){

    $values = [
      'uid' => ['target_id' => $this->user->id()],
      'type' => 'article',
      'language' => 'en',
      'title' => 'test',
    ];
    $node = $this->createNode($values);
    $node->addTranslation('ca', ['title' => $node->getTitle(). '(ca)']);
    $node->save();
    $this->assertNotNull($node->id());

    $output = $this->query('/node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->responseHeaderEquals('Content-Type', 'application/json');
    $this->assertNodeOutput($node, $output);
    $this->assertEquals('en', $output['language']);


    $output = $this->query( '/ca/node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->responseHeaderEquals('Content-Type', 'application/json');
    $this->assertNodeOutput($node, $output);
    $this->assertEquals('ca', $output['language']);

  }

  protected function query($path) {
    return Json::decode($this->drupalGet('/apitest/path',
      ['query' => ['path' => $path]],
      ['Accept' => 'application/json']
    ));
  }

  protected function assertNodeOutput(EntityInterface $entity, $output){
    $this->assertEquals($entity->getEntityTypeId(), $output['entity']['type']);
    $this->assertEquals($entity->bundle(), $output['entity']['bundle']);
    $this->assertEquals($entity->uuid(), $output['entity']['uuid']);
    $this->assertEquals($entity->id(), $output['entity']['id']);
  }


}
