<?php

namespace Drupal\Tests\url_to_video_filter\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * @group fancy_login
 */
class UrlToVideoFilterFunctionalJavascriptTestBase extends JavascriptTestBase
{
	public function assertStatusCodeEquals($statusCode)
	{
		$this->assertSession()->statusCodeEquals($statusCode);
	}

	public function assertElementExists($selector)
	{
		$this->assertSession()->elementExists('css', $selector);
	}

	public function assertElementExistsXpath($selector)
	{
		$this->assertSession()->elementExists('xpath', $selector);
	}

	public function assertElementNotExistsXpath($selector)
	{
		$this->assertSession()->elementNotExists('xpath', $selector);
	}

	public function getHtml()
	{
		$this->assertEquals('', $this->getSession()->getPage()->getHTML());
	}

	public function clickByXpath($path)
	{
		$this->getSession()->getPage()->find('xpath', $path)->click();
	}

	public function fillTextValue($htmlID, $value)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->getSession()->getPage()->fillField($htmlID, $value);
	}

	public function checkCheckbox($htmlID)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->getSession()->getPage()->checkField($htmlID);
	}

	public function uncheckCheckbox($htmlID)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->getSession()->getPage()->uncheckField($htmlID);
	}

	public function assertCheckboxChecked($htmlID)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->assertSession()->checkboxChecked($htmlID);
	}

	public function assertCheckboxNotChecked($htmlID)
	{
		if(preg_match('/^#/', $htmlID))
		{
			$htmlID = substr($htmlID, 1);
		}

		$this->assertSession()->checkboxNotChecked($htmlID);
	}

	public function checkboxIsChecked($htmlID)
	{
		$script = '(function($){return $("' . $htmlID . ':checked").length;}(jQuery));';

		return (bool) $this->getSession()->evaluateScript($script);
	}

	protected function createArticle($body = '', $format = '')
	{
		$settings = ['type' => 'article', 'title' => 'Article'];

		if($body)
		{
			$settings['body']['value'] = $body;
		}

		if($format)
		{
			$settings['body']['format'] = $format;
		}

		return $this->createNode($settings);
	}
}
