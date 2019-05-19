<?php

namespace Drupal\Tests\ssf\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ssf\Bayes;

/**
 * Tests for the Bayes class.
 *
 * @coversDefaultClass \Drupal\ssf\Bayes
 * @group ssf
 */
class BayesTest extends UnitTestCase {
  protected $bayes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $lexer = $this->createMock('\Drupal\ssf\Lexer');
    $degenerator = $this->createMock('\Drupal\ssf\Degenerator');
    $entity_type_manager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $logger_factory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');

    $this->bayes = new Bayes($lexer, $degenerator, $entity_type_manager, $logger_factory);

  }

  /**
   * @covers ::learn
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Trainer text must not be empty.
   */
  public function testLearnFailTextEmpty() {
    $this->bayes->learn();
  }

  /**
   * @covers ::learn
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Trainer text must be a string.
   */
  public function testLearnFailTextNotString() {
    $this->bayes->learn(123);
  }

  /**
   * @covers ::learn
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Category must be either "Bayes::HAM" or "Bayes::SPAM".
   */
  public function testLearnFailCategory() {
    $this->bayes->learn('text');
  }

  /**
   * @covers ::classify
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Classifier text must not be empty.
   */
  public function testClassifyFailTextEmpty() {
    $this->bayes->classify();
  }

  /**
   * @covers ::classify
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Classifier text must be a string.
   */
  public function testClassifyFailTextNotString() {
    $this->bayes->classify(123);
  }

  /**
   * @covers ::unlearn
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Trainer text must not be empty.
   */
  public function testUnlearnFailTextEmpty() {
    $this->bayes->learn();
  }

  /**
   * @covers ::unlearn
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Trainer text must be a string.
   */
  public function testUnlearnFailTextNotString() {
    $this->bayes->learn(123);
  }

  /**
   * @covers ::unlearn
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Category must be either "Bayes::HAM" or "Bayes::SPAM".
   */
  public function testUnlearnFailCategory() {
    $this->bayes->learn('text');
  }

  /**
   * @covers ::calculateProbability
   */
  public function testCalculateProbability() {
    $calc_probability = new \ReflectionMethod($this->bayes, 'calculateProbability');
    $calc_probability->setAccessible(TRUE);

    $data = ['count_ham' => 10, 'count_spam' => 10];
    $this->assertEquals(0.5, $calc_probability->invokeArgs(
      $this->bayes,
      [$data, 10, 10]));

    $data = ['count_ham' => 0, 'count_spam' => 20];
    $this->assertGreaterThan(0.99, $calc_probability->invokeArgs(
      $this->bayes,
      [$data, 10, 10]));

    $data = ['count_ham' => 20, 'count_spam' => 0];
    $this->assertLessThan(0.01, $calc_probability->invokeArgs(
      $this->bayes,
      [$data, 10, 10]));
  }

}
