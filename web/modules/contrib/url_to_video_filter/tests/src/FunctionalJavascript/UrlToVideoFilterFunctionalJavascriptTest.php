<?php

namespace Drupal\Tests\url_to_video_filter\FunctionalJavascript;

use Drupal\Tests\url_to_video_filter\FunctionalJavascript\UrlToVideoFilterFunctionalJavascriptTestBase;

/**
 * @group url_to_video_filter
 */
class UrlToVideoFilterFunctionalJavascriptTest extends UrlToVideoFilterFunctionalJavascriptTestBase
{
	public static $modules = ['url_to_video_filter', 'filter', 'node'];

	protected $filter_type = 'video_filter';

	protected $youtube_url = 'www.youtube.com/watch?v=';
	protected $youtu_be_url = 'www.youtu.be/';
	protected $vimeo_url = 'vimeo.com/';

	protected $youtube_id = '3qrNRzkwlbU';
	protected $vimeo_id = '195421709';

	public function setUp()
	{
		parent::setUp();

		$admin_role = $this->createAdminRole();
		$this->createContentType(['type' => 'article']);
		$adminUser = $this->createUser(['administer site configuration', 'administer filters', 'administer themes']);
		$adminUser->addRole($admin_role);
		$this->drupalLogin($adminUser);
		$this->drupalGet('/admin/appearance');
		$this->assertStatusCodeEquals(200);
		$this->clickByXpath('//a[@title="Install Bartik as default theme"]');
		$this->assertStatusCodeEquals(200);
		$this->drupalGet('/admin/config/content/formats/add');
		$this->assertStatusCodeEquals(200);
		$this->fillTextValue('#edit-name', 'Video Filter');
		$this->checkCheckbox("#edit-roles-authenticated");
		$this->checkCheckbox('#edit-filters-filter-url-to-video-status');
		$this->getSession()->evaluateScript('jQuery(".form-type-machine-name.visually-hidden").removeClass("visually-hidden");');
		$this->fillTextValue('#edit-format', $this->filter_type);
		$this->click('#edit-actions-submit');
	}

	public function testYouTubeEmbed()
	{
		$this->setFilterSettings(TRUE, TRUE);

		$node = $this->createArticle('https://' . $this->youtube_url . $this->youtube_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');

		$node = $this->createArticle('http://' . $this->youtube_url . $this->youtube_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');

		$node = $this->createArticle('some text https://' . $this->youtube_url . $this->youtube_id, $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');

		$node = $this->createArticle('some text http://' . $this->youtube_url . $this->youtube_id, $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');

		$node = $this->createArticle('https://' . $this->youtu_be_url . $this->youtube_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');

		$node = $this->createArticle('http://' . $this->youtu_be_url . $this->youtube_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');

		$node = $this->createArticle('some text https://' . $this->youtu_be_url . $this->youtube_id, $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');

		$node = $this->createArticle('some text http://' . $this->youtu_be_url . $this->youtube_id, $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]/span[@data-youtube-id="' . $this->youtube_id . '"]');
	}

	public function testYouTubeNotEmbed()
	{
		$this->setFilterSettings(FALSE, TRUE);

		$node = $this->createArticle('https://' . $this->youtube_url . $this->youtube_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementNotExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]');

		$node = $this->createArticle('https://' . $this->youtu_be_url . $this->youtube_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementNotExistsXpath('//span[@class="url-to-video-container youtube-container no-js"]');
	}

	public function testVimeoEmbed()
	{
		$this->setFilterSettings(TRUE, TRUE);
		$node = $this->createArticle('https://' . $this->vimeo_url . $this->vimeo_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container vimeo-container no-js"]/span[@data-vimeo-id="' . $this->vimeo_id . '"]');

		$node = $this->createArticle('http://' . $this->vimeo_url . $this->vimeo_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container vimeo-container no-js"]/span[@data-vimeo-id="' . $this->vimeo_id . '"]');

		$node = $this->createArticle('some text https://' . $this->vimeo_url . $this->vimeo_id, $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container vimeo-container no-js"]/span[@data-vimeo-id="' . $this->vimeo_id . '"]');

		$node = $this->createArticle('some text http://' . $this->vimeo_url . $this->vimeo_id, $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//span[@class="url-to-video-container vimeo-container no-js"]/span[@data-vimeo-id="' . $this->vimeo_id . '"]');
	}

	public function testVimeoNotEmbed()
	{
		$this->setFilterSettings(TRUE, FALSE);

		$node = $this->createArticle('https://' . $this->vimeo_url . $this->vimeo_id . ' some text', $this->filter_type);
		$this->drupalGet('/node/' . $node->id());
		$this->assertStatusCodeEquals(200);
		$this->assertElementNotExistsXpath('//span[@class="url-to-video-container vimeo-container no-js"]');
	}

	private function setFilterSettings($enableYouTube, $enableVimeo)
	{
		$this->drupalGet('/admin/config/content/formats/manage/video_filter');
		$this->assertStatusCodeEquals(200);
		$youtube_checkbox = '#edit-filters-filter-url-to-video-settings-youtube';
		$youtube_enabled = $this->checkboxIsChecked($youtube_checkbox);
		$vimeo_checkbox = '#edit-filters-filter-url-to-video-settings-vimeo';
		$vimeo_enabled = $this->checkboxIsChecked($vimeo_checkbox);
		if($enableYouTube && !$youtube_enabled)
		{
			$this->checkCheckbox($youtube_checkbox);
		}
		elseif(!$enableYouTube && $youtube_enabled)
		{
			$this->uncheckCheckbox($youtube_checkbox);
		}

		if($enableVimeo && !$vimeo_enabled)
		{
			$this->checkCheckbox($vimeo_checkbox);
		}
		elseif(!$enableVimeo && $vimeo_enabled)
		{
			$this->uncheckCheckbox($vimeo_checkbox);
		}

		$this->click('#edit-actions-submit');
		$this->assertStatusCodeEquals(200);
		$this->drupalGet('/admin/config/content/formats/manage/video_filter');
		$this->assertStatusCodeEquals(200);

		if($enableYouTube)
		{
			$this->assertCheckboxChecked($youtube_checkbox);
		}
		else
		{
			$this->assertCheckboxNotChecked($youtube_checkbox);
		}

		if($enableVimeo)
		{
			$this->assertCheckboxChecked($vimeo_checkbox);
		}
		else
		{
			$this->assertCheckboxNotChecked($vimeo_checkbox);
		}
	}
}
