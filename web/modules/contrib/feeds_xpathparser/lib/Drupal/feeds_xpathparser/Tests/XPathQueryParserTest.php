<?php

/**
 * @file
 * Contains Drupal\feeds_xpathparser\Tests\XPathQueryParserTest.
 */

namespace Drupal\feeds_xpathparser\Tests;

use Drupal\feeds_xpathparser\XPathQueryParser;
use Drupal\simpletest\UnitTestBase;


class XPathQueryParserTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => t('Query Parser'),
      'description' => t('Unit tests for the query parser inside Feeds XPath Parser.'),
      'group' => t('Feeds XPath Parser'),
    );
  }

  public function testSimple() {
    $parser = new XPathQueryParser('cow');
    $this->assertEqual($parser->getQuery(), '__default__:cow');
    $parser = new XPathQueryParser('/cow');
    $this->assertEqual($parser->getQuery(), '/__default__:cow');
    $parser = new XPathQueryParser('/cow/barn');
    $this->assertEqual($parser->getQuery(), '/__default__:cow/__default__:barn');
    $parser = new XPathQueryParser('/cow/barn[@id = "asdfsaf"]');
    $this->assertEqual($parser->getQuery(), '/__default__:cow/__default__:barn[@id = "asdfsaf"]');
    $parser = new XPathQueryParser('/cow/barn[@id=chair]');
    $this->assertEqual($parser->getQuery(), '/__default__:cow/__default__:barn[@id=__default__:chair]');
    $parser = new XPathQueryParser('/cow:asdf');
    $this->assertEqual($parser->getQuery(), '/cow:asdf');
    $parser = new XPathQueryParser('@cow');
    $this->assertEqual($parser->getQuery(), '@cow');
    $parser = new XPathQueryParser('starts-with(@id, "cat")');
    $this->assertEqual($parser->getQuery(), 'starts-with(@id, "cat")');
    $parser = new XPathQueryParser('starts-with(cat/dog/fire:breather, "cat")');
    $this->assertEqual($parser->getQuery(), 'starts-with(__default__:cat/__default__:dog/fire:breather, "cat")');
    $parser = new XPathQueryParser('//state[@id = ../city[name="CityName"]/state_id]/name');
    $this->assertEqual($parser->getQuery(), '//__default__:state[@id = ../__default__:city[__default__:name="CityName"]/__default__:state_id]/__default__:name');
    $parser = new XPathQueryParser('attribute::lang');
    $this->assertEqual($parser->getQuery(), 'attribute::lang');
    $parser = new XPathQueryParser('child::book');
    $this->assertEqual($parser->getQuery(), 'child::__default__:book');
    $parser = new XPathQueryParser('child::*');
    $this->assertEqual($parser->getQuery(), 'child::*');
    $parser = new XPathQueryParser('child::text()');
    $this->assertEqual($parser->getQuery(), 'child::text()');
    $parser = new XPathQueryParser('ancestor-or-self::book');
    $this->assertEqual($parser->getQuery(), 'ancestor-or-self::__default__:book');
    $parser = new XPathQueryParser('child::*/child::price');
    $this->assertEqual($parser->getQuery(), 'child::*/child::__default__:price');
    $parser = new XPathQueryParser("/asdfasfd[@id = 'a' or @id='b']");
    $this->assertEqual($parser->getQuery(), "/__default__:asdfasfd[@id = 'a' or @id='b']");
    // Go! difficult xpath queries from stack overflow.
    $parser = new XPathQueryParser("id('yui-gen2')/x:div[3]/x:div/x:a[1]");
    $this->assertEqual($parser->getQuery(), "id('yui-gen2')/x:div[3]/x:div/x:a[1]");
    $parser = new XPathQueryParser("/descendant::a[@class='buttonCheckout']");
    $this->assertEqual($parser->getQuery(), "/descendant::__default__:a[@class='buttonCheckout']");
    $parser = new XPathQueryParser("//a[@href='javascript:void(0)']");
    $this->assertEqual($parser->getQuery(), "//__default__:a[@href='javascript:void(0)']");
    $parser = new XPathQueryParser('//*/@attribute');
    $this->assertEqual($parser->getQuery(), '//*/@attribute');
    $parser = new XPathQueryParser('/descendant::*[attribute::attribute]');
    $this->assertEqual($parser->getQuery(), '/descendant::*[attribute::attribute]');
    $parser = new XPathQueryParser('//Event[not(System/Level = preceding::Level) or not(System/Task = preceding::Task)]');
    $this->assertEqual($parser->getQuery(), '//__default__:Event[not(__default__:System/__default__:Level = preceding::__default__:Level) or not(__default__:System/__default__:Task = preceding::__default__:Task)]');
    $parser = new XPathQueryParser("section[@type='cover']/line/@page");
    $this->assertEqual($parser->getQuery(), "__default__:section[@type='cover']/__default__:line/@page");
    $parser = new XPathQueryParser('/articles/article/*[name()="title" or name()="short"]');
    $this->assertEqual($parser->getQuery(), '/__default__:articles/__default__:article/*[name()="title" or name()="short"]');
    $parser = new XPathQueryParser("/*/article[@id='2']/*[self::title or self::short]");
    $this->assertEqual($parser->getQuery(), "/*/__default__:article[@id='2']/*[self::__default__:title or self::__default__:short]");
    $parser = new XPathQueryParser('not(/asdfasfd/asdfasf//asdfasdf) | /asdfasf/sadfasf/@asdf');
    $this->assertEqual($parser->getQuery(), 'not(/__default__:asdfasfd/__default__:asdfasf//__default__:asdfasdf) | /__default__:asdfasf/__default__:sadfasf/@asdf');

    $parser = new XPathQueryParser('Ülküdak');
    $this->assertEqual($parser->getQuery(), '__default__:Ülküdak');
  }

}
