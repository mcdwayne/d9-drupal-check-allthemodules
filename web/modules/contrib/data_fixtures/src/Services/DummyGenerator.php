<?php

namespace Drupal\data_fixtures\Services;

/**
 * Class DummyGenerator.
 *
 * @deprecated No longer used. Extend AbstractGenerator which is a wrapper for fzaninotto/faker
 *   and contains some useful methods.
 * @package Drupal\data_fixtures\Services
 */
class DummyGenerator {

  /**
   * Array of dummy titles.
   *
   * @var array
   */
  private $titles = [
    'Lorem ipsum dolor sit amet',
    'Et ille ridens: Video, inquit, quid agas',
    'Hoc ille tuus',
    'Collige omnia, quae soletis',
    'Hoc est non modo cor non habere',
    'Quo studio Aristophanem putamus aetatem in litteris duxisse',
    'Duo Reges: constructio interrete',
  ];

  /**
   * Array of dummy urls.
   *
   * @var array
   */
  private $urls = [
    'https://en.wikipedia.org/',
    'https://www.mozilla.org/en-US/firefox/new/',
    'https://www.openstreetmap.org/',
    'https://opensource.com/',
    'https://github.com/',
  ];

  /**
   * Array of dummy file names.
   *
   * @var array
   */
  private $attachments = [
    'sample.pdf',
  ];

  /**
   * Array of dummy images.
   *
   * @var array
   */
  private $images = [
    'media' => ['dummy-media.jpg'],
    'mediaFull' => ['dummy-media-full.jpg'],
  ];

  /**
   * Array of Vimeo video ids.
   *
   * @var array
   */
  private $vimeoIds = [
  // Nine Inch Nails: The perfect drug.
    '3612941',
  // Nine Inch Nails: The becoming (still)
    '61677369',
  // Nine Inch Nails: Only.
    '61997410',
  // On the job.
    '76227718',
  // Lôzane’s Burning VI - MONKEY3.
    '33172663',
  // Eluveitie - Thousandfold.
    '40368825',
  // Nostromo Acoustique.
    '36197258',
  // Dragon baby.
    '52942657',
  // On the job.
    '76227718',
  // Tiangmenshan Shaolin Kung Fu Academy.
    '88630748',
  // LaRoux - Bulletproof (MNI asked for !)
    '13133379',
  // Geist.
    '174544848',
  ];

  /**
   * Random text.
   *
   * @var string
   */
  private $text;

  /**
   * Create a random text from lorem ipsum generator.
   *
   * @param int $limit
   *   Number of characters in the text.
   *
   * @return string
   *   Randomly generated text.
   */
  public function getText($limit = 100) {
    if (!$this->text) {
      $this->text = simplexml_load_file(
        'http://www.lipsum.com/feed/xml?amount=20&what=paras&start=0'
      )->lipsum;
    }

    $randomIndex = rand($limit, strlen($this->text));

    return substr($this->text, $randomIndex - $limit, $limit);
  }

  /**
   * Create a random title.
   *
   * @param int $maxLength
   *   The max length of the string to return.
   *
   * @return string
   *   Text to use as title.
   */
  public function getTitle($maxLength = 255) {
    return substr($this->titles[$this->getRandomIndex($this->titles)], 0, $maxLength);
  }

  /**
   * Return the path to a dummy image.
   *
   * @param string $type
   *   Get a random image.
   *
   * @return string
   *   Path to the image.
   */
  public function getImage($type = 'media') {
    return $this->getModuleAssetsDirectory() . '/images/' . $this->images[$type][$this->getRandomIndex($this->images[$type])];
  }

  /**
   * Get a random Vimeo's video.
   *
   * @return string
   *   Full Vimeo url.
   */
  public function getVimeoUrl() {
    return 'http://www.vimeo.com/' . $this->vimeoIds[$this->getRandomIndex(
        $this->vimeoIds
      )];
  }

  /**
   * Get a random url.
   *
   * @return string
   *   Random url.
   */
  public function getUrl() {
    return $this->urls[$this->getRandomIndex($this->urls)];
  }

  /**
   * Get a random file.
   *
   * @return string
   *   Path to the file.
   */
  public function getFile() {

    return $this->getModuleAssetsDirectory() . '/attachments/' . $this->attachments[$this->getRandomIndex($this->attachments)];
  }

  /**
   * Return the path to the modules assets directory.
   *
   * @return string
   *   Path to the modules assets directory.
   */
  private function getModuleAssetsDirectory() {
    return drupal_get_path('module', 'data_fixtures') . '/assets';
  }

  /**
   * Get random index on array.
   *
   * @param array $arr
   *   Array from which you'll get a random index.
   *
   * @return int
   *   Random index value.
   */
  public function getRandomIndex(array $arr) {
    return rand(0, count($arr) - 1);
  }

}
