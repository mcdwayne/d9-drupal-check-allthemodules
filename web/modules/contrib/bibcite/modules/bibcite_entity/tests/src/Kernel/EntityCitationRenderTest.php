<?php

namespace Drupal\Tests\bibcite_entity\Kernel;

use Drupal\bibcite_entity\Entity\Reference;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Test rendering of entity to citation.
 *
 * @group bibcite
 */
class EntityCitationRenderTest extends KernelTestBase {

  public static $modules = [
    'system',
    'user',
    'serialization',
    'bibcite',
    'bibcite_entity',
  ];

  /**
   * Styler service.
   *
   * @var \Drupal\bibcite\CitationStylerInterface
   */
  protected $styler;

  /**
   * Serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig(['user', 'bibcite', 'bibcite_entity']);

    $this->styler = $this->container->get('bibcite.citation_styler');
    $this->serializer = $this->container->get('serializer');
  }

  /**
   * Test rendering Reference entity to citation.
   *
   * @dataProvider providerReferenceEntity
   */
  public function testEntityRender($entity_values, $expected) {
    $entity = Reference::create($entity_values);

    $data = $this->serializer->normalize($entity, 'csl');
    $citation = $this->styler->render($data);

    $this->assertEquals($expected, strip_tags($citation));
  }

  /**
   * Get test data from YAML.
   *
   * @return array
   *   Data for test.
   */
  public function providerReferenceEntity() {
    $yaml_text = file_get_contents(__DIR__ . '/data/testEntityRender.data.yml');
    return Yaml::parse($yaml_text);
  }

}
