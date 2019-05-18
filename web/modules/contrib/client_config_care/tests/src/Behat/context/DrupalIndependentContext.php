<?php

namespace Drupal\client_config_care\Behat\Context;

use Behat\Mink\Exception\ResponseTextException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\HookDispatcher;
use WebDriver\Exception\StaleElementReference;


class DrupalIndependentContext extends RawMinkContext {

	private const MAX_DURATION_SECONDS = 1200;
	private const MAX_SHORT_DURATION_SECONDS = 10;

	/**
	 * {@inheritdoc}
	 */
	public function setDispatcher(HookDispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @param $name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getDrupalSelector($name) {
		$text = $this->getDrupalParameter('selectors');
		if (!isset($text[$name])) {
			throw new \Exception(sprintf('No such selector configured: %s', $name));
		}
		return $text[$name];
	}

	/**
	 * Get driver's random generator.
	 */
	public function getRandom() {
		return $this->getDriver()->getRandom();
	}

	/**
	 * @Then /^I should see "([^"]*)" exactly "([^"]*)" times$/
	 */
	public function iShouldSeeTextSoManyTimes($sText, $iExpected)
	{
		$sContent = $this->getSession()->getPage()->getText();
		$iFound = substr_count($sContent, $sText);
		if ($iExpected != $iFound) {
			throw new \Exception('Found '.$iFound.' occurences of "'.$sText.'" when expecting '.$iExpected);
		}
	}

	/**
	 * @Then /^I should see text matching "([^"]*)" after a while$/
	 */
	public function iShouldSeeTextAfterAWhile(string $text): bool
	{
		try {
			$startTime = time();
			do {
				$content = $this->getSession()->getPage()->getText();
				if (substr_count($content, $text) > 0) {
					return true;
				}
			} while (time() - $startTime < selfMAX_DURATION_SECONDS);
			throw new ResponseTextException(
				sprintf('Could not find text %s after %s seconds', $text, self::MAX_DURATION_SECONDS),
				$this->getSession()
			);
		} catch (StaleElementReference $e) {
			return true;
		}
	}

  /**
   * @Then /^I should see HTML content matching "([^"]*)"$/
   */
  public function iShouldSeeHTMLContentMatching(string $content)
  {
    $html = $this->getSession()->getPage()->getHtml();
    if (substr_count($html, $content) > 0) {
      return true;
    }

    throw new ResponseTextException(
      sprintf('HTML does not contain content "%s"', $content),
      $this->getSession());
  }

  /**
   * @Then /^I should not see HTML content matching "([^"]*)"$/
   */
  public function iShouldNotSeeHTMLContent($html)
  {
    $content = $this->getSession()->getPage()->getText();
    if (substr_count($content, $html) === 0) {
      return true;
    }
  }

  /**
   * @Then /^I should see HTML content matching "([^"]*)" after a while$/
   */
  public function iShouldSeeHTMLContentMatchingAfterWhile($text)
  {
    try {
      $startTime = time();
      do {
        $content = $this->getSession()->getPage()->getHtml();
        if (substr_count($content, $text) > 0) {
          return true;
        }
      } while (time() - $startTime < self::MAX_DURATION_SECONDS);
      throw new ResponseTextException(
        sprintf('Could not find text %s after %s seconds', $text, self::MAX_DURATION_SECONDS),
        $this->getSession()
      );
    } catch (StaleElementReference $e) {
      return true;
    }
  }

	/**
	 * @Then /^I should not see text matching "([^"]*)" after a while$/
	 */
	public function iShouldNotSeeTextAfterAWhile($text)
	{
		$startTime = time();
		do {
			$content = $this->getSession()->getPage()->getText();
			if (substr_count($content, $text) === 0) {
				return true;
			}
		} while (time() - $startTime < self::MAX_SHORT_DURATION_SECONDS);
		throw new ResponseTextException(
			sprintf('Could find text %s after %s seconds', $text, self::MAX_SHORT_DURATION_SECONDS),
			$this->getSession()
		);
	}

  /**
   * @Then /^wait (\d+) seconds$/
   */
  public function waitSeconds(int $secondsNumber) {
    $this->getSession()->wait($secondsNumber * 1000);
  }

  /**
   * @Then /^I execute shell command "([^"]*)"$/
   */
  public function executeShellCommand(string $command) {
    shell_exec($command);
  }

  /**
   * @Then /^I execute shell command "([^"]*)" and expect output contains "([^"]*)"$/
   */
  public function executeShellCommandExpectContains(string $command, string $expectedOutput) {
    $shellExecOutput = shell_exec($command);
    if (!strstr($shellExecOutput, $expectedOutput)) {
      throw new \Exception("Shell output '$shellExecOutput' does not match expected output '$expectedOutput'.");
    }
  }

  /**
   * @Then /^I execute shell command "([^"]*)" and expect output contains not "([^"]*)"$/
   */
  public function executeShellCommandExpectContainsNot(string $command, string $expectedOutput) {
    $shellExecOutput = shell_exec($command);
    if (strstr($shellExecOutput, $expectedOutput)) {
      throw new \Exception("Shell output '$shellExecOutput' does match not expected output '$expectedOutput'.");
    }
  }

}
