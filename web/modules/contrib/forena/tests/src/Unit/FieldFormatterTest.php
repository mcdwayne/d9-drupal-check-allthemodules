<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/28/2016
 * Time: 8:38 AM
 */

namespace Drupal\Tests\forena\Unit;

use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\forena\FrxPlugin\FieldFormatter\Formatter;
use Drupal\forena\Token\ReportReplacer;

/**
 * Test CSV
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\FieldFormatter\Formatter
 */
class FieldFormatterTest extends FrxTestCase {

  /** @var  \Drupal\forena\FrxPlugin\FieldFormatter\Formatter */
  protected $formatter;

  public function __construct() {
    parent::__construct();
    $this->formatter = new Formatter();
  }

  /**
   * Test call to make sure we get a list.
   */
  public function testFormatters() {
    $formatters = $this->formatter->formats();
    // Check to make sure we have at least one of the formatters.
    $this->assertArrayHasKey('iso_date', $formatters);
  }


  /**
   * Test the ISO Date formatter.
   */
  public function testISODateFormatter() {
    $vars = [
      'date' => '2016-01-13',
      'time' => '2016-01-13 15:30',
    ];
    $this->pushData($vars, 'vars');
    $r = new ReportReplacer();
    $field = [
      'format' => 'iso_date',
      'format-string' => 'm-d-Y',
    ];
    $r->defineField('date', $field);
    $text = $r->replace('{date}');
    $this->popData();

    $this->assertEquals('01-13-2016', $text);

  }
}