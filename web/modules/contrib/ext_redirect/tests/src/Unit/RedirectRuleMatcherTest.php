<?php
/**
 * Created by PhpStorm.
 * User: marek.kisiel
 * Date: 07/09/2017
 * Time: 19:05
 */

namespace Drupal\Tests\ext_redirect\Unit;

use Drupal\ext_redirect\Service\RedirectRuleMatcher;
use Drupal\ext_redirect\Service\RedirectRuleRepository;
use Drupal\Tests\UnitTestCase;

/**
 * Class RedirectRuleMatcherTest
 *
 * @package Drupal\Tests\ext_redirect\Unit
 * @group ext_redirect
 */
class RedirectRuleMatcherTest extends UnitTestCase {

  /**
   * @var
   */
  private $redirectRuleRepository;

  public function setUp() {
    parent::setUp();


  }

  /**
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::lookup
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::findMatchRule
   */
  public function testMatchForNoPathRedirectRule() {
    $alias = 'alias.dev';

    /** @var RedirectRuleRepository $redirectRuleRepository */
    $redirectRuleRepository = $this->getMockBuilder('\Drupal\ext_redirect\Service\RedirectRuleRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $redirectRuleRepository->expects($this->once())
      ->method('getRuleForHostWithoutPath')
      ->willReturn($this->getRedirectRuleWithoutOrWithAnyPathMock($alias));

    $redirectRuleRepository->expects($this->never())
      ->method('getHostRules');

    $redirectRuleRepository->expects($this->never())
      ->method('getGlobalRules');

    $redirectRuleMatcher = new RedirectRuleMatcher($redirectRuleRepository);

    $redirectRuleMatcher->lookup($alias);
  }

  /**
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::lookup
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::findMatchRule
   */
  public function testMatchForGlobalRedirectRule() {

    $alias = 'alias.dev';

    /** @var RedirectRuleRepository $redirectRuleRepository */
    $redirectRuleRepository = $this->getMockBuilder('\Drupal\ext_redirect\Service\RedirectRuleRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $redirectRuleRepository->expects($this->never())
      ->method('getRuleForHostWithoutPath');

    $redirectRuleRepository->expects($this->once())
      ->method('getGlobalRules')
      ->willReturn([$this->getGlobalRedirectRuleMock('/global-joe')]);

    $redirectRuleRepository->expects($this->once())
      ->method('getHostRules')
      ->willReturn([
        $this->getRedirectRuleMock($alias, "/sample\n/road-to-hell/dead/end\n/car/toyota"),
        $this->getRedirectRuleMock($alias, "/foobar\n/heaven/no-entry\n/fruit/forbidden"),
        $this->getRedirectRuleMock($alias, "/alone-single-path"),
      ]);

    $redirectRuleMatcher = new RedirectRuleMatcher($redirectRuleRepository);

    $rule = $redirectRuleMatcher->lookup($alias, '/global-joe');


    $this->assertEquals('/global-joe', $rule->getSourcePath());
  }

  /**
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::lookup
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::findMatchRule
   */
  public function testMatchForAnyPathRedirectRule() {
    $alias = 'alias.dev';

    /** @var RedirectRuleRepository $redirectRuleRepository */
    $redirectRuleRepository = $this->getMockBuilder('\Drupal\ext_redirect\Service\RedirectRuleRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $redirectRuleRepository->expects($this->never())
      ->method('getRuleForHostWithoutPath');

    $redirectRuleRepository->expects($this->never())
      ->method('getGlobalRules');

    $redirectRuleRepository->expects($this->once())
      ->method('getHostRules')
      ->willReturn([
        $this->getRedirectRuleMock($alias, "/sample\n/road-to-hell/dead/end\n/car/toyota"),
        $this->getRedirectRuleWithAnyPathMock($alias),
        $this->getRedirectRuleMock($alias, "/foobar\n/heaven/no-entry\n/fruit/forbidden"),
        $this->getRedirectRuleMock($alias, "/alone-single-path"),
      ]);

    $redirectRuleMatcher = new RedirectRuleMatcher($redirectRuleRepository);

    $rule = $redirectRuleMatcher->lookup($alias, '/heaven/no-entry');

    $this->assertEquals('*', $rule->getSourcePath());
  }

  /**
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::lookup
   * @covers \Drupal\ext_redirect\Service\RedirectRuleMatcher::findMatchRule
   */
  public function testMatchForStandardRedirectRule() {
    $alias = 'alias.dev';

    /** @var RedirectRuleRepository $redirectRuleRepository */
    $redirectRuleRepository = $this->getMockBuilder('\Drupal\ext_redirect\Service\RedirectRuleRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $redirectRuleRepository->expects($this->never())
      ->method('getRuleForHostWithoutPath');

    $redirectRuleRepository->expects($this->never())
      ->method('getGlobalRules');

    $redirectRuleRepository->expects($this->once())
      ->method('getHostRules')
      ->willReturn([
        $this->getRedirectRuleMock($alias, "/sample\n/road-to-hell/dead/end\n/car/toyota"),
        $this->getRedirectRuleMock($alias, "/foobar\n/heaven/no-entry\n/fruit/forbidden"),
        $this->getRedirectRuleMock($alias, "/alone-single-path"),
      ]);

    $redirectRuleMatcher = new RedirectRuleMatcher($redirectRuleRepository);

    $rule = $redirectRuleMatcher->lookup($alias, '/heaven/no-entry');

    $this->assertContains('/heaven/no-entry', $rule->getSourcePath());
  }



  private function getGlobalRedirectRuleMock($path) {

    $rule = $this->getMockBuilder('\Drupal\ext_redirect\Entity\RedirectRule')
      ->disableOriginalConstructor()
      ->getMock();

    $rule->expects($this->any())
      ->method('getSourcePath')
      ->willReturn($path);

    $rule->expects($this->any())
      ->method('getSourceSite')
      ->willReturn('any');

    return $rule;
  }

  private function getRedirectRuleWithoutOrWithAnyPathMock($host) {
    $rule = $this->getMockBuilder('\Drupal\ext_redirect\Entity\RedirectRule')
      ->disableOriginalConstructor()
      ->getMock();

    $rule->expects($this->any())
      ->method('getSourceSite')
      ->willReturn($host);

    $rule->expects($this->any())
      ->method('getSourcePath')
      ->willReturnCallback(function () {
        $return_values = [NULL, '*'];
        return array_rand($return_values);
      });

    return $rule;
  }

  private function getRedirectRuleWithAnyPathMock($host) {
    $rule = $this->getMockBuilder('\Drupal\ext_redirect\Entity\RedirectRule')
      ->disableOriginalConstructor()
      ->getMock();

    $rule->expects($this->any())
      ->method('getSourceSite')
      ->willReturn($host);

    $rule->expects($this->any())
      ->method('getSourcePath')
      ->willReturn('*');

    return $rule;
  }

  private function getRedirectRuleMock($host, $source_path) {
    $rule = $this->getMockBuilder('\Drupal\ext_redirect\Entity\RedirectRule')
      ->disableOriginalConstructor()
      ->getMock();

    $rule->expects($this->any())
      ->method('getSourceSite')
      ->willReturn($host);

    $rule->expects($this->any())
      ->method('getSourcePath')
      ->willReturn($source_path);

    return $rule;
  }


}