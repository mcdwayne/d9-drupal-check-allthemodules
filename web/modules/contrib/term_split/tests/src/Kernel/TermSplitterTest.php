<?php

namespace Drupal\Tests\term_split\Kernel;

use Drupal\term_split\TermSplitter;

/**
 * Class TermSplitterTest
 *
 * @group term_split
 */
class TermSplitterTest extends TermSplitTestBase {


  /**
   * @var \Drupal\term_reference_change\ReferenceMigrator
   */
  private $termReferenceMigrator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->termReferenceMigrator = \Drupal::getContainer()->get('term_reference_change.migrator');
  }

  /**
   * @test
   **/
  public function splittingTerm_DeletesOriginal() {
    $term = $this->createTerm($this->vocabulary);

    $sut = new TermSplitter($this->entityTypeManager, $this->termReferenceMigrator);
    $sut->splitInTo($term, 'Target 1', 'Target 2', [], []);

    self::assertNULL($this->termStorage->load($term->id()));
    return $term;
  }

  /**
   * @test
   **/
  public function splittingTerm_ResultsInTwoNewTerms() {
    $term = $this->splittingTerm_DeletesOriginal();

    $target1 = $this->termStorage->loadByProperties(['name' => 'Target 1']);
    self::assertCount(1, $target1);
    $target2 = $this->termStorage->loadByProperties(['name' => 'Target 2']);
    self::assertCount(1, $target2);

    self::assertSame($term->bundle(), reset($target1)->bundle());
    self::assertSame($term->bundle(), reset($target2)->bundle());
  }

  /**
   * @test
   **/
  public function splittingTermIntoExistingTerm_AddsToExistingTerm() {
    $this->createTerm($this->vocabulary, ['name' => 'Target 1']);
    $term = $this->splittingTerm_DeletesOriginal();

    $target1 = $this->termStorage->loadByProperties(['name' => 'Target 1']);
    self::assertCount(1, $target1);
    $target2 = $this->termStorage->loadByProperties(['name' => 'Target 2']);
    self::assertCount(1, $target2);

    self::assertSame($term->bundle(), reset($target1)->bundle());
    self::assertSame($term->bundle(), reset($target2)->bundle());
  }

  /**
   * @test
   **/
  public function splittingTermMigratesNodes() {
    $term = $this->createTerm($this->vocabulary);
    $node1 = $this->createNode(['field_terms' => ['target_id' => $term->id()]]);
    $node2 = $this->createNode(['field_terms' => ['target_id' => $term->id()]]);

    $sut = new TermSplitter($this->entityTypeManager, $this->termReferenceMigrator);
    $sut->splitInTo($term,"Term 1", "Term 2", [$node1->id()], [$node2->id()]);

    $term1Result = $this->termStorage->loadByProperties(['name' => 'Term 1']);
    $term1 = reset($term1Result);
    $term2Result = $this->termStorage->loadByProperties(['name' => 'Term 2']);
    $term2 = reset($term2Result);

    /** @var \Drupal\node\Entity\Node $node1 */
    $node1 = $this->entityTypeManager->getStorage('node')->load($node1->id());
    /** @var \Drupal\node\Entity\Node $node2 */
    $node2 = $this->entityTypeManager->getStorage('node')->load($node2->id());
    self::assertEquals($term1->id(), $node1->get('field_terms')->getString());
    self::assertEquals($term2->id(), $node2->get('field_terms')->getString());
  }

}
