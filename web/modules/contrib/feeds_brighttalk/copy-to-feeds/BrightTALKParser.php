<?php

namespace Drupal\feeds\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\SyndicationItem;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Zend\Feed\Reader\Exception\ExceptionInterface;
use Zend\Feed\Reader\Reader;

/**
 * Defines an BrightTALK feed parser.
 *
 * @FeedsParser(
 *   id = "brighttalk",
 *   title = @Translation("BrightTALK"),
 *   description = @Translation("Default parser for BrightTALK feeds.")
 * )
 */
class BrightTALKParser extends PluginBase implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $result = new ParserResult();
    Reader::setExtensionManager(\Drupal::service('feed.bridge.reader'));
    Reader::registerExtension('GeoRSS');

    $channel_id = 0;
    $webcast_id = 0;

    $raw = $fetcher_result->getRaw();
    if (!strlen(trim($raw))) {
      throw new EmptyFeedException();
    }

    try {
      $channel = Reader::importString($raw);
    }
    catch (ExceptionInterface $e) {
      $args = ['%site' => $feed->label(), '%error' => trim($e->getMessage())];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    foreach ($channel as $delta => $entry) {
      $item = new SyndicationItem();

      $item
        ->set('title', $entry->getTitle())
        ->set('guid', $entry->getId())
        ->set('url', $entry->getLink())
        ->set('description', $entry->getDescription())
        ->set('tags', $entry->getCategories()->getValues())
        ->set('feed_title', $channel->getTitle())
        ->set('feed_description', $channel->getDescription())
        ->set('feed_url', $channel->getLink());

      if ($image = $channel->getImage()) {
        $item->set('feed_image_uri', $image['uri']);
      }

      if ($enclosure = $entry->getEnclosure()) {
        $item->set('enclosures', [rawurldecode($enclosure->url)]);
      }

      if ($author = $entry->getAuthor()) {
        $author += ['name' => '', 'email' => ''];
        $item->set('author_name', $author['name'])
          ->set('author_email', $author['email']);
      }

      if ($date = $entry->getDateModified()) {
        $item->set('timestamp', $date->getTimestamp());
      }

      if ($point = $entry->getGeoPoint()) {
        $item->set('georss_lat', $point['lat'])
          ->set('georss_lon', $point['lon']);
      }

      $url_parts = explode('brighttalk.com/webcast/', $entry->getLink());

      if (is_array($url_parts) && isset($url_parts[1])) {
        $path_data = explode('/', $url_parts[1]);

        if (is_array($path_data) && isset($path_data[1])) {

          $channel_id = $path_data[0];
          $webcast_id = $path_data[1];
        }
      }

      $js = 'https://www.brighttalk.com/clients/js/player-embed/player-embed.js';
      $player = '<script src="' . $js . '" class="jsBrightTALKEmbed">';
      $player .= '{ "channelId": ' . $channel_id . ', "commid" : ' . $webcast_id . ', "height" : "2000", "width" : "100%", "displayMode" : "channelList", "environment" : "prod", "language" : "en-us", "theme" : "default", "categories" : "Webinars", "noFlash" : false, "track" : "", "inlineContent" : false }';
      $player .= '</script>';

      $item->set('channel', $channel_id);
      $item->set('webcast', $webcast_id);
      $item->set('player', $player);

      $result->addItem($item);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [
      'url' => [
        'label' => $this->t('Webcast url'),
        'description' => $this->t('The url of the individual Brighttalk.com webcast.'),
        'suggestions' => [
          'targets' => ['url'],
        ],
      ],
      'guid' => [
        'label' => $this->t('Unique ID'),
        'description' => $this->t('Unique ID.'),
      ],
      'title' => [
        'label' => $this->t('Webcast title'),
        'description' => $this->t('Webcast title.'),
        'suggestions' => [
          'targets' => ['title'],
        ],
      ],
      'description' => [
        'label' => $this->t('Description'),
        'description' => $this->t('Webcast summary.'),
      ],
      'author' => [
        'label' => $this->t('Author'),
        'description' => $this->t('A string containing information about the author(s) of a webcast'),
      ],
      'channel' => [
        'label' => $this->t('Channel ID'),
        'description' => $this->t('The unique identifier for the channel on brighttalk.com'),
      ],
      'webcast' => [
        'label' => $this->t('Webcast ID'),
        'description' => $this->t('A unique identifier for this webcast.'),
      ],
      'date' => [
        'label' => $this->t('Start date and time'),
        'description' => $this->t('The webcast start date and time (timestamp).'),
      ],
      'duration' => [
        'label' => $this->t('Duration'),
        'description' => $this->t('The webcast duration, in seconds.'),
      ],
      'image' => [
        'label' => $this->t('Webcast image'),
        'description' => $this->t('The URL of the webcast image.'),
      ],
      'status' => [
        'label' => $this->t('Webcast status'),
        'description' => $this->t('The current webcast status (recorded, live or upcoming).'),
      ],
      'tags' => [
        'label' => $this->t('Tags'),
        'description' => $this->t('Freetagged taxonomy.'),
      ],
      'player' => [
        'label' => $this->t('Player'),
        'description' => $this->t('Embedded webcast.'),
      ],
    ];
  }

}
