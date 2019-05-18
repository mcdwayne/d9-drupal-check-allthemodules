<?php

namespace Drupal\Tests;

use Drupal\joomag_filter\Plugin\Filter\JoomagFilter;
/**
 * @coversDefaultClass \Drupal\joomag_filter\Plugin\Filter\JoomagFilter
 * @group joomag_filter
 *
 * @author mbarcia
 *
 */
class JoomagFilterTest extends UnitTestCase {

  public function testGetBookshelf() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function testGetMagazine() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function testParser() {
    $p = new JoomagFilter();

    $expRes = '<iframe name="Joomag_embed_dc6c5d54-fca8-42c5-96c3-c189a55383cf" style="width:100%;height:100%" hspace="0" vspace="0" src="//www.joomag.com/magazine/octubre-1-parets/0569341001442564197?page=1&amp;e=1&amp;embedInfo=solid,878787,50;solid,ffffff" frameborder="0" height="100%" width="100%"></iframe>';
    $text = '[joomag autoFit=true title=octubre-1-parets magazineId=0569341001442564197 backgroundColor=ffffff toolbar=878787,50 ]';
    $this->assertEquals($p->parser($text), $expRes);

    $expRes = '<iframe name="Joomag_embed_37f86433-d4a3-4f34-9fbe-1fba43461f0c" style="width:100%;height:100%" hspace="0" vspace="0" src="//www.joomag.com/magazine/digital-ordenacion-parets/0645236001439289535?page=1&amp;e=1&amp;embedInfo=solid,878787,50;solid,ffffff" frameborder="0" height="100%" width="100%"></iframe>';
    $text = '[joomag autoFit=true title=digital-ordenacion-parets magazineId=0645236001439289535 backgroundColor=ffffff toolbar=878787,50 ]';
    $this->assertEquals($p->parser($text), $expRes);
  }

}
