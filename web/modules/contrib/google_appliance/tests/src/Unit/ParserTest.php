<?php

namespace Drupal\Tests\google_appliance\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\google_appliance\Service\Parser;
use Drupal\Tests\UnitTestCase;

/**
 * Tests parsing of result set.
 *
 * @group google_appliance
 */
class ParserTest extends UnitTestCase {

  /**
   * Tests parsing.
   */
  public function testParsing() {
    $response = file_get_contents(__DIR__ . '/../../fixtures/response.xml');
    $parser = new Parser($this->getMock(ModuleHandlerInterface::class));
    $results = $parser->parseResponse($response);
    $this->assertEquals(7040, $results->getTotal());
    $searchResults = $results->getResults();
    $this->assertCount(20, $searchResults);
    /** @var \Drupal\google_appliance\SearchResults\Result $result */
    $result = reset($searchResults);
    $this->assertEquals('http://www.uts.edu.au/research-and-teaching/future-researchers', $result->getAbsoluteUrl());
    $this->assertContains('Future researchers', strip_tags((string) $result->getTitle()));
    $this->assertContains('UTS is home to world-leading', (string) $result->getSnippet());
  }

}
