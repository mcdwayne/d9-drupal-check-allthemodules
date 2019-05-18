<?php

namespace Drupal\Tests\restrict_ip\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides some helper functions for functional tests.
 */
class RestrictIpBrowserTestBase extends BrowserTestBase
{
	public function assertStatusCodeEquals($statusCode)
	{
		$this->assertSession()->statusCodeEquals($statusCode);
	}

	public function assertElementExists($selector)
	{
		$this->assertSession()->elementExists('css', $selector);
	}

	public function assertElementAttributeExists($selector, $attribute)
	{
		$this->assertSession()->elementAttributeExists('css', $selector, $attribute);
	}

	public function assertElementAttributeContains($selector, $attribute, $value)
	{
		$this->assertSession()->elementAttributeContains('css', $selector, $attribute, $value);
	}

	public function selectRadio($htmlID)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$radio = $this->getSession()->getPage()->findField($htmlID);
		$name = $radio->getAttribute('name');
		$option = $radio->getAttribute('value');
		$this->getSession()->getPage()->selectFieldOption($name, $option);
	}

	public function assertRadioSelected($htmlID)
	{
		if(!preg_match('/^#/', $htmlID))
		{
			$htmlID = '#' . $htmlID;
		}

		$selected_radio = $this->getSession()->getPage()->find('css', 'input[type="radio"]:checked' . $htmlID);

		if(!$selected_radio)
		{
			throw new \Exception('Radio button with ID ' . $htmlID . ' is not selected');
		}
	}

	public function checkCheckbox($htmlID)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->getSession()->getPage()->checkField($htmlID);
	}

	public function assertCheckboxChecked($htmlID)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->assertSession()->checkboxChecked($htmlID);
	}

	public function fillTextValue($htmlID, $value)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->getSession()->getPage()->fillField($htmlID, $value);
	}

	public function assertTextValue($htmlID, $value)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->assertSession()->fieldValueEquals($htmlID, $value);
	}
}
