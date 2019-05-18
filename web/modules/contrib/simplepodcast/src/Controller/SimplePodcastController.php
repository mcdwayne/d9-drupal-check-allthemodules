<?php
/**
 * @file
 * Contains \Drupal\simplepodcast\Controller\SimplePodcastController.
 */
namespace Drupal\simplepodcast\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Site\Settings;
use Drupal\simplepodcast\SimplePodcastCommonFunctions;

class SimplePodcastController extends ControllerBase {

  public function content() {
    $title = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('title');
    $descr = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('description');
    $link = $GLOBALS['base_url'];
    $pub_date = self::getNewestDate();
    $lang = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('language');
    $owner = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('owner_name');
    $author = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('owner_author');
    $copy = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('copyright');
    $email = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('owner_email');
    $channel_image = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('channel_image');
    $cat1 = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('category_1');
    $cat2 = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('category_2');
    $cat3 = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('category_3');
    $items = self::buildItems();
    $rss_link = $GLOBALS['base_url'] . '/podcast.xml';
    $explicit = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('explicit') ? 'Yes' : 'No';
    $categories = [self::buildCategory($cat1)];
    if ( $cat2 != '' ) {
        array_push($categories,self::buildCategory($cat2));
    }
    if ( $cat3 != '' ) {
        array_push($categories,self::buildCategory($cat3));
    }
    $response = new Response();
    $response->headers->set('Content-Type', 'application/xml');

    $render = [
            '#theme'               => 'simplepodcast_xml',
            '#channel_title'       => $this->t($title),
            '#channel_link'        => $link,
            '#channel_lang'        => $this->t($lang),
            '#channel_copyright'   => $this->t($copy),
            '#channel_description' => $this->t($descr),
            '#channel_explicit'    => $this->t($explicit),
            '#channel_rss_url'     => $rss_link,
            '#channel_owner_name'  => $this->t($owner),
            '#channel_owner_author'=> $this->t($author),
            '#channel_owner_email' => $email,
            '#channel_image_url'   => $channel_image,
            '#channel_categories'  => $categories,
            '#items'               => $items,
        ];
        $xml = \Drupal::service('renderer')->renderRoot($render);
        $response->setContent($xml);
        return $response;
  }

  private function getNewestDate() {
    $episode_content_type = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('episode_content_type');
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('type', $episode_content_type)
      ->sort('created', 'DESC')
      ->range(0,1);
    $nids = $query->execute();
    $node = \Drupal\node\Entity\Node::load($nids[0]);
    $newest_date = $node->created->value;
    $newest_date_formatted = date(DATE_RSS,$newest_date);
    return $newest_date_formatted;
  }

  private function buildCategory($category_string) {
    list($main_cat,$sub_cat) = explode('|',$category_string);
    if ($sub_cat) {
      return ['main'=>$main_cat,'sub'=>$sub_cat];
    }
    else {
      return ['main'=>$main_cat];
    }
  }

  private function buildItems() {
    $episode_ids = self::getIDS();
    foreach ($episode_ids as $episode) {
      $items[] = self::buildItem($episode);
    }
    return $items;
  }


  private function getIDS($episode_content_type) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('type', \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('episode_content_type'))
      ->sort('created', 'DESC');
    $nids = $query->execute();
    return $nids;
  }

  private function buildItem($id) {
    $node = \Drupal\node\Entity\Node::load($id);

    //item title
    //$title = $node->title->value;
    $title_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_title');
    $title = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldValue($node,$title_field);
    $title_safe = self::makeXmlSafe($title);

    $author_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_author');
    $author = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldValue($node,$author_field);

    $subtitle_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_subtitle');
    $subtitle = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldValue($node,$subtitle_field);
    $subtitle_safe = self::makeXmlSafe($subtitle);

    //item link
    $link = \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$id);
    $link = $GLOBALS['base_url'] . $link;

    //item description
    $summary_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_summary');
    $summary = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldValue($node,$summary_field);
    $summary_safe = self::makeCdataSafe($summary);

    //image
    $image_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_image');
    $image_url = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldImage($node,$image_field);

    //enclosure
    $media_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_media');
    $media = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldUri($node,$media_field);

    $media_length_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_media_length');
    $media_length = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldValue($node,$media_length_field);
    $media_length = ($media_length>0) ? $media_length : 0;

    $duration_field = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getConfig('item_media_duration');
    $duration = \Drupal\simplepodcast\SimplePodcastCommonFunctions::getFieldValue($node,$duration_field);
    $duration_formatted = str_pad(floor($duration/3600),2,'0',STR_PAD_LEFT).":"
      .str_pad(floor(($duration/60)%60),2,'0',STR_PAD_LEFT).":"
      .str_pad($duration%60,2,'0',STR_PAD_LEFT);

    //pubdate
    $created_date = $node->created->value;
    $created_date_formatted = date('r',$created_date);

    $item = [
            'item_title'          => $this->t($title_safe),
            'item_author'         => $this->t($author),
            'item_subtitle'       => $this->t($subtitle_safe),
            'item_summary'        => $this->t($summary_safe),
            'item_image_url'      => $image_url,
            'item_media_url'      => $media,
            'item_media_length'   => $media_length,
            'item_guid'           => $link,
            'item_pubdate'        => $created_date_formatted,
            'item_duration'       => $duration_formatted,
        ];

    return $item;
  }

  private function makeXmlSafe($text) {
    if (!$text) {
      return '';
    }
    $invalid_characters = '/[^\x9\xa\x20-\xD7FF\xE000-\xFFFD]/';
    return preg_replace($invalid_characters, '', $text);
  }

  private function makeCdataSafe($text) {
    if (!$text) {
      return '';
    }
    $invalid_characters = '/[^\x9\xa\x20-\xD7FF\xE000-\xFFFD]/';
    return preg_replace($invalid_characters, '', $text);
  }
}