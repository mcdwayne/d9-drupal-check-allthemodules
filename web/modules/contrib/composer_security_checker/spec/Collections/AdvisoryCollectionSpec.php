<?php namespace spec\Drupal\composer_security_checker\Collections;
// @codingStandardsIgnoreFile
use Drupal\composer_security_checker\Collections\AdvisoryCollection;
use Drupal\composer_security_checker\Models\Advisory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AdvisoryCollectionSpec extends ObjectBehavior {

  function it_is_initializable() {
    $this->shouldHaveType('Drupal\composer_security_checker\Collections\AdvisoryCollection');
  }

  function it_stores_advisories(Advisory $advisory) {
    $this->add($advisory);

    $this->shouldHaveCount(1);
  }

  function it_gets_advisories(Advisory $advisoryOne, Advisory $advisoryTwo) {
    $this->add($advisoryOne);
    $this->add($advisoryTwo);

    $advisories = $this->getAdvisories();

    $advisories->shouldBeArray();
    $advisories->shouldHaveCount(2);
  }

  function it_should_be_able_to_ingest_other_collections() {
    $advisoryOne = new Advisory('foo', 'foo', 'foo', 'foo', 'foo');
    $advisoryTwo = new Advisory('bar', 'bar', 'bar', 'bar', 'bar');

    $collectionOne = new AdvisoryCollection();
    $collectionOne->add($advisoryOne);
    $collectionOne->add($advisoryTwo);

    $collectionTwo = new AdvisoryCollection();
    $collectionTwo->add($advisoryOne);
    $collectionTwo->add($advisoryTwo);

    $this->ingest($collectionOne);
    $this->ingest($collectionTwo);

    $this->getAdvisories()->shouldHaveCount(4);
  }

}
