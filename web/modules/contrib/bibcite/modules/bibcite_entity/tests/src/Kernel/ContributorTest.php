<?php

namespace Drupal\Tests\bibcite_entity\Kernel;

use Drupal\bibcite_entity\Entity\Contributor;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test contributor entity.
 *
 * @group bibcite
 */
class ContributorTest extends KernelTestBase {

  public static $modules = [
    'system',
    'field',
    'bibcite',
    'bibcite_entity',
  ];

  /**
   * Test rendering Reference entity to citation.
   */
  public function testContributorName() {
    // @todo Add leading title to name.
    $name = 'Mr. Jüan Martinez (Martin) de Lorenzo y Gutierez Jr.';
    $parts = [
      'prefix' => 'Mr.',
      'first_name' => 'Jüan',
      'middle_name' => 'Martinez',
      'last_name' => 'de Lorenzo y Gutierez',
      'nick' => 'Martin',
      'suffix' => 'Jr.',
    ];

    $config = \Drupal::configFactory()->getEditable('bibcite_entity.contributor.settings');

    $config->set('full_name_pattern', '@prefix @first_name @middle_name @nick @last_name @suffix')->save();
    $entity = Contributor::create($parts);
    $this->assertEquals('Mr. Jüan Martinez Martin de Lorenzo y Gutierez Jr.', $entity->name->value);
    $config->set('full_name_pattern', '@prefix @first_name @last_name @suffix')->save();
    $this->assertEquals('Mr. Jüan de Lorenzo y Gutierez Jr.', $entity->name->value);

    $entity = Contributor::create();
    $entity->name = $name;
    foreach ($parts as $part => $value) {
      $this->assertEquals($value, $entity->{$part}->value);
    }

    $entity = Contributor::create();
    $entity->name = [$name];
    foreach ($parts as $part => $value) {
      $this->assertEquals($value, $entity->{$part}->value);
    }

    $entity = Contributor::create();
    $entity->name = ['value' => $name];
    foreach ($parts as $part => $value) {
      $this->assertEquals($value, $entity->{$part}->value);
    }

    $entity = Contributor::create();
    $entity->name = [['value' => $name]];
    foreach ($parts as $part => $value) {
      $this->assertEquals($value, $entity->{$part}->value);
    }
  }

}
