<?php namespace spec\Drupal\composer_security_checker\Models;
// @codingStandardsIgnoreFile
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AdvisorySpec extends ObjectBehavior {

  function let() {
    $library_name = 'monolog/monolog';
    $library_version = '1.11.0';
    $advisory_date_or_cve = '2014-12-29';
    $advisory_title = 'Header injection in NativeMailerHandler';
    $advisory_link = 'https://github.com/Seldaek/monolog/pull/448#issuecomment-68208704';

    $this->beConstructedWith($library_name, $library_version, $advisory_date_or_cve, $advisory_title, $advisory_link);
  }

  function it_is_initializable() {
    $this->shouldHaveType('Drupal\composer_security_checker\Models\Advisory');
  }

  function it_should_be_able_to_get_the_advisory_library_title() {
    $this->getLibraryName()->shouldReturn('monolog/monolog');
  }

  function it_should_be_able_to_get_the_advisory_library_version() {
    $this->getLibraryVersion()->shouldReturn('1.11.0');
  }

  function it_should_be_able_to_get_the_advisory_identifier() {
    $this->getAdvisoryIdentifier()->shouldReturn('2014-12-29');
  }

  function it_should_be_able_to_get_the_advisory_link() {
    $this->getAdvisoryLink()
      ->shouldReturn('https://github.com/Seldaek/monolog/pull/448#issuecomment-68208704');
  }

  function it_should_be_able_to_get_the_advisory_title() {
    $this->getAdvisoryTitle()
      ->shouldReturn('Header injection in NativeMailerHandler');
  }

}
