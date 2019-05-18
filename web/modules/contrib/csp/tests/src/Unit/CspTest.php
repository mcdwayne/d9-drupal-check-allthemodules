<?php

namespace Drupal\Tests\csp\Unit;

use Drupal\csp\Csp;
use Drupal\Tests\UnitTestCase;

/**
 * Test Csp behaviour.
 *
 * @coversDefaultClass \Drupal\csp\Csp
 * @group csp
 */
class CspTest extends UnitTestCase {

  /**
   * Test that changing the policy's report-only flag updates the header name.
   *
   * @covers ::reportOnly
   * @covers ::getHeaderName
   */
  public function testReportOnly() {
    $policy = new Csp();

    $this->assertEquals(
      "Content-Security-Policy",
      $policy->getHeaderName()
    );

    $policy->reportOnly();
    $this->assertEquals(
      "Content-Security-Policy-Report-Only",
      $policy->getHeaderName()
    );

    $policy->reportOnly(FALSE);
    $this->assertEquals(
      "Content-Security-Policy",
      $policy->getHeaderName()
    );
  }

  /**
   * Test that invalid directive names cause an exception.
   *
   * @covers ::setDirective
   * @covers ::isValidDirectiveName
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidPolicy() {
    $policy = new Csp();

    $policy->setDirective('foo', Csp::POLICY_SELF);
  }

  /**
   * Test that invalid directive names cause an exception.
   *
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAppendInvalidPolicy() {
    $policy = new Csp();

    $policy->appendDirective('foo', Csp::POLICY_SELF);
  }

  /**
   * Test setting a single value to a directive.
   *
   * @covers ::setDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testSetSingle() {
    $policy = new Csp();

    $policy->setDirective('default-src', Csp::POLICY_SELF);

    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test appending a single value to an uninitialized directive.
   *
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testAppendSingle() {
    $policy = new Csp();

    $policy->appendDirective('default-src', Csp::POLICY_SELF);

    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that a directive is overridden when set with a new value.
   *
   * @covers ::setDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testSetMultiple() {
    $policy = new Csp();

    $policy->setDirective('default-src', Csp::POLICY_SELF);
    $policy->setDirective('default-src', [Csp::POLICY_SELF, 'one.example.com']);
    $policy->setDirective('script-src', Csp::POLICY_SELF . ' two.example.com');
    $policy->setDirective('report-uri', 'example.com/report-uri');

    $this->assertEquals(
      "default-src 'self' one.example.com; script-src 'self' two.example.com; report-uri example.com/report-uri",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that appending to a directive extends the existing value.
   *
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testAppendMultiple() {
    $policy = new Csp();

    $policy->appendDirective('default-src', Csp::POLICY_SELF);
    $policy->appendDirective('script-src', [Csp::POLICY_SELF, 'two.example.com']);
    $policy->appendDirective('default-src', 'one.example.com');

    $this->assertEquals(
      "default-src 'self' one.example.com; script-src 'self' two.example.com",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that setting an empty value removes a directive.
   *
   * @covers ::setDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testSetEmpty() {
    $policy = new Csp();
    $policy->setDirective('default-src', Csp::POLICY_SELF);
    $policy->setDirective('script-src', [Csp::POLICY_SELF]);
    $policy->setDirective('script-src', []);

    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );


    $policy = new Csp();
    $policy->setDirective('default-src', Csp::POLICY_SELF);
    $policy->setDirective('script-src', [Csp::POLICY_SELF]);
    $policy->setDirective('script-src', '');

    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that appending an empty value doesn't change the directive.
   *
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testAppendEmpty() {
    $policy = new Csp();

    $policy->appendDirective('default-src', Csp::POLICY_SELF);
    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );

    $policy->appendDirective('default-src', '');
    $policy->appendDirective('script-src', []);
    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that source values are not repeated in the header.
   *
   * @covers ::setDirective
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testDuplicate() {
    $policy = new Csp();

    // Provide identical sources in an array.
    $policy->setDirective('default-src', [Csp::POLICY_SELF, Csp::POLICY_SELF]);
    // Provide identical sources in a string.
    $policy->setDirective('script-src', 'one.example.com one.example.com');

    // Provide identical sources through both set and append.
    $policy->setDirective('style-src', ['two.example.com', 'two.example.com']);
    $policy->appendDirective('style-src', ['two.example.com', 'two.example.com']);

    $this->assertEquals(
      "default-src 'self'; script-src one.example.com; style-src two.example.com",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that removed directives are not output in the header.
   *
   * @covers ::removeDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testRemove() {
    $policy = new Csp();

    $policy->setDirective('default-src', [Csp::POLICY_SELF]);
    $policy->setDirective('script-src', 'example.com');

    $policy->removeDirective('script-src');

    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that removing an invalid directive name causes an exception.
   *
   * @covers ::removeDirective
   * @covers ::isValidDirectiveName
   *
   * @expectedException \InvalidArgumentException
   */
  public function testRemoveInvalid() {
    $policy = new Csp();

    $policy->removeDirective('foo');
  }

  /**
   * Test that invalid directive values cause an exception.
   *
   * @covers ::appendDirective
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidValue() {
    $policy = new Csp();

    $policy->appendDirective('default-src', 12);
  }

  /**
   * Test optimizing policy based on directives which fallback to default-src.
   *
   * @covers ::getHeaderValue
   * @covers ::getDirectiveFallbackList
   * @covers ::reduceSourceList
   */
  public function testDefaultSrcFallback() {
    $policy = new Csp();
    $policy->setDirective('default-src', Csp::POLICY_SELF);

    // Directives which fallback to default-src.
    $policy->setDirective('script-src', Csp::POLICY_SELF);
    $policy->setDirective('style-src', Csp::POLICY_SELF);
    $policy->setDirective('worker-src', Csp::POLICY_SELF);
    $policy->setDirective('child-src', Csp::POLICY_SELF);
    $policy->setDirective('connect-src', Csp::POLICY_SELF);
    $policy->setDirective('manifest-src', Csp::POLICY_SELF);
    $policy->setDirective('prefetch-src', Csp::POLICY_SELF);
    $policy->setDirective('object-src', Csp::POLICY_SELF);
    $policy->setDirective('frame-src', Csp::POLICY_SELF);
    $policy->setDirective('media-src', Csp::POLICY_SELF);
    $policy->setDirective('font-src', Csp::POLICY_SELF);
    $policy->setDirective('img-src', Csp::POLICY_SELF);

    // Directives which do not fallback to default-src.
    $policy->setDirective('base-uri', Csp::POLICY_SELF);
    $policy->setDirective('form-action', Csp::POLICY_SELF);
    $policy->setDirective('frame-ancestors', Csp::POLICY_SELF);
    $policy->setDirective('navigate-to', Csp::POLICY_SELF);

    $this->assertEquals(
      "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; navigate-to 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test optimizing policy based on the worker-src fallback list.
   *
   * @covers ::getHeaderValue
   * @covers ::getDirectiveFallbackList
   * @covers ::reduceSourceList
   */
  public function testWorkerSrcFallback() {
    $policy = new Csp();

    // Fallback should progresses as more policies in the list are added.
    $policy->setDirective('worker-src', Csp::POLICY_SELF);
    $this->assertEquals(
      "worker-src 'self'",
      $policy->getHeaderValue()
    );

    $policy->setDirective('child-src', Csp::POLICY_SELF);
    $this->assertEquals(
      "child-src 'self'",
      $policy->getHeaderValue()
    );

    $policy->setDirective('script-src', Csp::POLICY_SELF);
    $this->assertEquals(
      "script-src 'self'",
      $policy->getHeaderValue()
    );

    $policy->setDirective('default-src', Csp::POLICY_SELF);
    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );

    // A missing directive from the list should not prevent fallback.
    $policy->removeDirective('child-src');
    $this->assertEquals(
      "default-src 'self'",
      $policy->getHeaderValue()
    );

    // Fallback should only progress to the nearest matching directive.
    // Since child-src differs from worker-src, both should be included.
    // script-src does not appear since it matches default-src.
    $policy->setDirective('child-src', [Csp::POLICY_SELF, 'example.com']);
    $this->assertEquals(
      "worker-src 'self'; default-src 'self'; child-src 'self' example.com",
      $policy->getHeaderValue()
    );

    // Fallback should only progress to the nearest matching directive.
    // worker-src now matches child-src, so it should be removed.
    $policy->setDirective('worker-src', [Csp::POLICY_SELF, 'example.com']);
    $this->assertEquals(
      "default-src 'self'; child-src 'self' example.com",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test optimizing policy based on the script-src fallback list.
   *
   * @covers ::getHeaderValue
   * @covers ::getDirectiveFallbackList
   * @covers ::reduceSourceList
   */
  public function testScriptSrcFallback() {
    $policy = new Csp();

    $policy->setDirective('default-src', Csp::POLICY_SELF);
    $policy->setDirective('script-src', [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE]);
    // script-src-elem should not fall back to default-src.
    $policy->setDirective('script-src-elem', Csp::POLICY_SELF);
    $policy->setDirective('script-src-attr', Csp::POLICY_UNSAFE_INLINE);
    $this->assertEquals(
      "default-src 'self'; script-src 'self' 'unsafe-inline'; script-src-elem 'self'; script-src-attr 'unsafe-inline'",
      $policy->getHeaderValue()
    );

    $policy->setDirective('script-src-attr', [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE]);
    $this->assertEquals(
      "default-src 'self'; script-src 'self' 'unsafe-inline'; script-src-elem 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test optimizing policy based on the style-src fallback list.
   *
   * @covers ::getHeaderValue
   * @covers ::getDirectiveFallbackList
   * @covers ::reduceSourceList
   */
  public function testStyleSrcFallback() {
    $policy = new Csp();

    $policy->setDirective('default-src', Csp::POLICY_SELF);
    $policy->setDirective('style-src', [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE]);
    // style-src-elem should not fall back to default-src.
    $policy->setDirective('style-src-elem', Csp::POLICY_SELF);
    $policy->setDirective('style-src-attr', Csp::POLICY_UNSAFE_INLINE);
    $this->assertEquals(
      "default-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'",
      $policy->getHeaderValue()
    );

    $policy->setDirective('style-src-attr', [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE]);
    $this->assertEquals(
      "default-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * @covers ::__toString
   */
  public function testToString() {
    $policy = new Csp();

    $policy->setDirective('default-src', Csp::POLICY_SELF);
    $policy->setDirective('script-src', [Csp::POLICY_SELF, 'example.com']);

    $this->assertEquals(
      "Content-Security-Policy: default-src 'self'; script-src 'self' example.com",
      $policy->__toString()
    );
  }

}
