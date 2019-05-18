<?php

namespace Drupal\Tests\forena\Unit;
use Drupal\forena\Token\ReportReplacer;
use Drupal\Tests\forena\Unit\Mock\TestingDataManager;

/**
 * Class AReportTokenTest
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\Token\ReportReplacer
 */
class AReportTokenTest extends FrxTestCase {

  private $replacer;

  public function __construct() {
    parent::__construct();
    $this->replacer = new ReportReplacer($this->dataService());
  }

  public function testReplacement() {
    $data = ['name' => 'Bob'];
    $this->pushData($data, 'p');
    $rep = new ReportReplacer($this->dataService());
    $this->assertNotNull($rep);

    // Replace the data
    $text = $rep->replace('Hello {name}');
    $this->assertEquals('Hello Bob', $text);
    $this->popData();
  }

  /**
   * Verify that basic Context specific token replacement works.
   */
  public function testContextReplacement() {
    $data = ['name' => 'Bob'];
    $this->pushData($data, 'p');

    // Replace the data
    $text = $this->replacer->replace('Hello {p.name}');
    $this->assertEquals('Hello Bob', $text);
    $this->popData();

    $messages = ['messages' => ['deep' => [
      'Hello {p.name}',
    ]]];

    $this->replacer->replaceNested($messages);
    $this->assertEquals('Hello Bob', $messages['messages']['deep'][0]);
  }

  /**
   * Test token replacement from a custom data context. 
   */
  public function testCustomContext() {
    $text = $this->replacer->replace('you are {custom_security.secure} secure.');
    $this->assertEquals('you are not secure.', $text);
  }

  public function testReportContext() {
    $text = $this->replacer->replace('Report follows: {FrxReport.sample}');
    $this->assertContains('col1', $text); 
  }

  /**
   * basic test for field generation.
   */
  public function testLinkGeneration() {
    $data = ['title' => 'Title'];
    $data['link'] = 'some/page';
    $data['data-test'] = 'foo';
    $this->pushData($data);
    $text="See {title}";
    $this->replacer->defineField('title', $data);
    $text = $this->replacer->replace($text);
    $this->assertContains("<a title='Title' data-test='foo' href='some/page'>Title</a>", $text);
    $this->popData();
  }


}