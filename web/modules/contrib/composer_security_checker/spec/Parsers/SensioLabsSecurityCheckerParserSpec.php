<?php namespace spec\Drupal\composer_security_checker\Parsers;
// @codingStandardsIgnoreFile
use Drupal\composer_security_checker\Collections\AdvisoryCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SensioLabsSecurityCheckerParserSpec extends ObjectBehavior {

  /**
   * Get monolog/monolog stub data.
   *
   * @return array
   *   An array representing a response from SensioLabs Security Checker.
   */
  private function getMonologStub() {
    return [
      'version' => '1.11.0',
      'advisories' => [
        'monolog/monolog/2014-12-29-1.yaml' => [
          'title' => 'Header injection in NativeMailerHandler',
          'link' => 'https://github.com/Seldaek/monolog/pull/448#issuecomment-68208704',
          'cve' => '',
        ],
      ],
    ];
  }

  function it_is_initializable() {
    $this->shouldHaveType('Drupal\composer_security_checker\Parsers\SensioLabsSecurityCheckerParser');
  }

  function let() {
    $this->beConstructedWith('monolog/monolog', $this->getMonologStub());
  }

  function it_should_return_a_collection() {
    $this->parse()->shouldReturnAnInstanceOf(AdvisoryCollection::class);
  }

}
