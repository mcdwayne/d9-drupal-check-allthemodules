<?php
/**
 * @file
 * Definition of Drupal\link_socicon\Plugin\Field\FieldFormatter\LinkSociconFormatter.
 */

namespace Drupal\link_socicon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'link_socicon' formatter.
 *
 * @FieldFormatter(
 *   id = "link_socicon",
 *   module = "link_socicon",
 *   label = @Translation("Link Socicon"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkSociconFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {

    $elements = array(
      '#type' => 'container',
      '#attached' => array(
        'library' => array(
          'link_socicon/socicon',
        ),
      ),
    );

    $this->getIcon('http://fb.me/olragon');

    foreach ($items as $delta => $item) {
      $icon = $this->getIcon($item->url);
      $url = Url::fromUri($item->url);
      if ($icon) {
        $url->setOptions(array('attributes' => array('class' => 'socicon', 'title' => $item->title)));
        $elements[$delta] = array(
          '#markup' => \Drupal::l($icon['character'], $url)
        );
      } else {
        $elements[$delta] = array(
          '#markup' => \Drupal::l($item->title, $url)
        );
      }
    }
    return $elements;
  }

  /**
   * Get icon from URL
   *
   * @param string $url
   *   URL to get icon
   * @return bool|array
   *   Return icon definition or FALSE
   */
  private function getIcon($url) {
    $components = parse_url($url);
    $host = $components['host'];

    $defs = $this->sociconDefinitions();
    if (isset($defs[$host])) {
      return $defs[$host];
    }

    $defs_aliases = array_filter($defs, function ($def) {
      return !empty($def['aliases']);
    });

    foreach ($defs_aliases as $def_aliases) {
      if (in_array($host, $def_aliases['aliases'])) {
        return $def_aliases;
      }
    }

    return FALSE;
  }

  /**
   * Icon definition
   *
   * @return array
   *   Icon info with name, socicon's character
   */
  private function sociconDefinitions() {
    return array(
      'twitter.com' => array(
        'name' => 'Twitter',
        'character' => 'a',
      ),
      'facebook.com' => array(
        'name' => 'Facebook',
        'character' => 'b',
        'aliases' => array(
          'fb.me'
        )
      ),
      'plus.google.com' => array(
        'name' => 'Google+',
        'character' => 'c',
      ),
      'pinterest.com' => array(
        'name' => 'Pinterest',
        'character' => 'd',
      ),
      'foursquare.com' => array(
        'name' => 'foursquare',
        'character' => 'e',
      ),
      'yahoo.com' => array(
        'name' => 'Yahoo!',
        'character' => 'f',
      ),
      'skype.com' => array(
        'name' => 'skype',
        'character' => 'g',
      ),
      'yelp.com' => array(
        'name' => 'yelp',
        'character' => 'h',
      ),
      'feedburner.com' => array(
        'name' => 'FeedBurner',
        'character' => 'i',
        'aliases' => array(
          'feedburner.google.com'
        )
      ),
      'linkedin.com' => array(
        'name' => 'Linkedin',
        'character' => 'j',
      ),
      'viadeo.com' => array(
        'name' => 'Viadeo',
        'character' => 'k',
      ),
      'xing.com' => array(
        'name' => 'Xing',
        'character' => 'l',
      ),
      'myspace.com' => array(
        'name' => 'Myspace',
        'character' => 'm',
      ),
      'soundcloud.com' => array(
        'name' => 'soundcloud',
        'character' => 'n',
      ),
      'spotify.com' => array(
        'name' => 'Spotify',
        'character' => 'o',
      ),
      'grooveshark.com' => array(
        'name' => 'grooveshark',
        'character' => 'p',
      ),
      'last.fm' => array(
        'name' => 'last.fm',
        'character' => 'q',
      ),
      'youtube.com' => array(
        'name' => 'YouTube',
        'character' => 'r',
      ),
      'vimeo.com' => array(
        'name' => 'vimeo',
        'character' => 's',
      ),
      'dailymotion.com' => array(
        'name' => 'Dailymotion',
        'character' => 't',
      ),
      'vine.co' => array(
        'name' => 'Vine',
        'character' => 'u',
      ),
      'flickr.com' => array(
        'name' => 'flickr',
        'character' => 'v',
      ),
      '500px.com' => array(
        'name' => '500px',
        'character' => 'w',
      ),
      'instagram.com' => array(
        'name' => 'Instagram',
        'character' => 'x',
      ),
      'wordpress.com' => array(
        'name' => 'WordPress',
        'character' => 'y',
      ),
      'tumblr.com' => array(
        'name' => 'tumblr',
        'character' => 'z',
      ),
      'blogspot.com' => array(
        'name' => 'Blogger',
        'character' => 'A',
      ),
      'technorati.com' => array(
        'name' => 'Technorati',
        'character' => 'B',
      ),
      'reddit.com' => array(
        'name' => 'reddit',
        'character' => 'C',
      ),
      'dribbble.com' => array(
        'name' => 'dribbble',
        'character' => 'D',
      ),
      'stumbleupon.com' => array(
        'name' => 'StumbleUpon',
        'character' => 'E',
      ),
      'digg.com' => array(
        'name' => 'Digg',
        'character' => 'F',
      ),
      'evanto.com' => array(
        'name' => 'Envato',
        'character' => 'G',
        'aliases' => array(
          'themeforest.net', 'graphicriver.net', 'codecanyon.net', 'videohive.net', 'photodune.net', '3docean.net', 'audiojungle.net', 'activeden.net'
        )
      ),
      'behance.com' => array(
        'name' => 'Behance',
        'character' => 'H',
      ),
      'delicious.com' => array(
        'name' => 'Delicious',
        'character' => 'I',
      ),
      'deviantart.com' => array(
        'name' => 'deviantART',
        'character' => 'J',
      ),
      'forrst.com' => array(
        'name' => 'Forrst',
        'character' => 'K',
      ),
      'play.google.com' => array(
        'name' => 'Play Store',
        'character' => 'L',
      ),
      'zerply.com' => array(
        'name' => 'Zerply',
        'character' => 'M',
        'aliases' => array(
          'plus.google.com'
        )
      ),
      'wikipedia.com' => array(
        'name' => 'Wikipedia',
        'character' => 'N',
      ),
      'apple.com' => array(
        'name' => 'Apple',
        'character' => 'O',
      ),
      'flattr.com' => array(
        'name' => 'Flattr',
        'character' => 'P',
      ),
      'github.com' => array(
        'name' => 'GitHub',
        'character' => 'Q',
      ),
      'chime.in' => array(
        'name' => 'Chime.in',
        'character' => 'R',
      ),
      'friendfeed.com' => array(
        'name' => 'FriendFeed',
        'character' => 'S',
      ),
      'newsvine.com' => array(
        'name' => 'NewsVine',
        'character' => 'T',
        'aliases' => array(
          'facebook.com', 'fb.me'
        )
      ),
      'identi.ca' => array(
        'name' => 'Identica',
        'character' => 'U',
      ),
      'bebo.com' => array(
        'name' => 'bebo',
        'character' => 'V',
      ),
      'zynga.com' => array(
        'name' => 'zynga',
        'character' => 'W',
      ),
      'steampowered.com' => array(
        'name' => 'steam',
        'character' => 'X',
      ),
      'xbox.com' => array(
        'name' => 'XBOX',
        'character' => 'Y',
      ),
      'windows.com' => array(
        'name' => 'Windows',
        'character' => 'Z',
      ),
      'outlook.com' => array(
        'name' => 'Outlook',
        'character' => '1',
      ),
      'coderwall.com' => array(
        'name' => 'coderwall',
        'character' => '2',
      ),
      'tripadvisor.com' => array(
        'name' => 'tripadvisor',
        'character' => '3',
      ),
      'netcod.es' => array(
        'name' => 'netcodes',
        'character' => '4',
      ),
      'easid.cc' => array(
        'name' => 'easID',
        'character' => '5',
      ),
      '_easid.cc' => array(
        'name' => 'easID (?)',
        'character' => '6',
      ),
      'lanyrd.com' => array(
        'name' => 'Lanyrd',
        'character' => '7',
      ),
      'slideshare.com' => array(
        'name' => 'SlideShare',
        'character' => '8',
      ),
      'bufferapp.com' => array(
        'name' => 'Buffer',
        'character' => '9',
      ),
      'rss' => array(
        'name' => 'RSS',
        'character' => ',',
      ),
      'vk.com' => array(
        'name' => 'VKontakte',
        'character' => ';',
      ),
      'disqus.com' => array(
        'name' => 'DISQUS',
        'character' => ':',
      ),
    );
  }
}
