<?php

namespace Drupal\Tests\url_to_video_filter\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\url_to_video_filter\Service\UrlToVideoFilterService;

/**
 * @coversDefaultClass \Drupal\url_to_video_filter\Service\UrlToVideoFilterService
 * @group url_to_video_filter
 */
class UrlToVideoFilterServiceTest extends UnitTestCase
{
	/**
	 * @covers ::convertYouTubeUrls
	 * @dataProvider convertYouTubeUrlsDataProvider
	 */
	public function testConvertYouTubeUrls($text, $expected, $findUrls, $message)
	{
		$service = new UrlToVideoFilterService();

		$converted = $service->convertYouTubeUrls($text);
		$this->assertSame($converted['text'], $expected, $message);
		$this->assertSame($converted['url_found'], $findUrls, $message);
	}

	/**
	 * Data provider for testConvertYouTubeUrls()
	 */
	public function convertYouTubeUrlsDataProvider()
	{
		return [
			['https://www.youtube.com/watch?v=youtubetest some text', '<span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span> some text', TRUE, 'HTTPS Youtube properly embedded at start of string'],
			['http://www.youtube.com/watch?v=youtubetest some text', '<span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span> some text', TRUE, 'HTTP Youtube properly embedded at start of string'],
			['some text https://www.youtube.com/watch?v=youtubetest', 'some text <span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span>', TRUE, 'HTTPS Youtube properly embedded at end of string'],
			['some text http://www.youtube.com/watch?v=youtubetest', 'some text <span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span>', TRUE, 'HTTP Youtube properly embedded at end of string'],
			['https://youtu.be/youtubetest some text', '<span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span> some text', TRUE, 'HTTPS youtu.be properly embedded at start of string'],
			['http://youtu.be/youtubetest some text', '<span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span> some text', TRUE, 'HTTP youtu.be properly embedded at start of string'],
			['some text https://youtu.be/youtubetest', 'some text <span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span>', TRUE, 'HTTPS youtu.be properly embedded at end of string'],
			['some text http://youtu.be/youtubetest', 'some text <span class="url-to-video-container youtube-container no-js"><span class="youtube-player url-to-video-player loader" data-youtube-id="youtubetest"></span></span>', TRUE, 'HTTP youtu.be properly embedded at end of string'],
			['some text', 'some text', FALSE, 'Text not converted when no YouTube link exists'],
		];
	}

	/**
	 * @covers ::convertVimeoUrls
	 * @dataProvider convertVimeoUrlsDataProvider
	 */
	public function testConvertVimeoUrls($text, $expected, $findUrls, $message)
	{
		$service = new UrlToVideoFilterService();

		$converted = $service->convertVimeoUrls($text);
		$this->assertSame($converted['text'], $expected, $message);
		$this->assertSame($converted['url_found'], $findUrls, $message);
	}

	/**
	 * Data provider for testConvertVimeoUrls()
	 */
	public function convertVimeoUrlsDataProvider()
	{
		return [
			['https://vimeo.com/vimeotest some text', '<span class="url-to-video-container vimeo-container no-js"><span class="vimeo-player url-to-video-player loader" data-vimeo-id="vimeotest"></span></span> some text', TRUE, 'HTTPS Vimeo properly embedded at start of string'],
			['http://vimeo.com/vimeotest some text', '<span class="url-to-video-container vimeo-container no-js"><span class="vimeo-player url-to-video-player loader" data-vimeo-id="vimeotest"></span></span> some text', TRUE, 'HTTP Vimeo properly embedded at start of string'],
			['some text https://vimeo.com/vimeotest', 'some text <span class="url-to-video-container vimeo-container no-js"><span class="vimeo-player url-to-video-player loader" data-vimeo-id="vimeotest"></span></span>', TRUE, 'HTTPS Vimeo properly embedded at end of string'],
			['some text https://vimeo.com/vimeotest', 'some text <span class="url-to-video-container vimeo-container no-js"><span class="vimeo-player url-to-video-player loader" data-vimeo-id="vimeotest"></span></span>', TRUE, 'HTTP Vimeo properly embedded at end of string'],
			['some text', 'some text', FALSE, 'Text not converted when no Vimeo link exists'],
		];
	}
}
