<?php


namespace Drupal\responsive_share_buttons\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;


/**
 * Provides a 'Responsive Sharing buttons' block.
 *
 * @Block(
 *   id = "responsive_sharing_buttons",
 *   admin_label = @Translation("Responsive Sharing buttons block")
 * )
 */
class ResponsiveShareButtonsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    if (is_array($title) && isset($title['#markup'])) {
      $title = $title['#markup'];
    }
    elseif (!is_string($title)) {
      $title = \Drupal::config('system.site')->get('name');
    }
    $title = urlencode($title);
    $url = urlencode($request->getUri());

    $links = array();

    $networks = $this->getActiveNetworks();
    foreach ($networks as $network) {
      $links[] = $this->_prepare_share_link($network, $url, $title);
    }

    $render_array = array(
      '#theme' => 'item_list',
      '#cache' => array(
        'contexts' => array('url'),
      ),
      '#wrapper_attributes' => array(
        'class' => array('share-inner-wrp'),
      ),
      '#items' => $links,
      '#attached' => array(
        'library' => array('responsive_share_buttons/share'),
      ),
    );
    return $render_array;
  }

  protected function getActiveNetworks() {

    $config = \Drupal::config('responsive_share_buttons.settings');
    $networks = $config->get('networks');

    $network_list = array();
    foreach ($networks as $name => $network) {
      if (!empty($network['active'])) {
        $network_list[] = $name;
      }
    }
    return $network_list;
  }

  /**
   * Prepare a sharing link.
   *
   * @param string $network
   *   The name of the social network to use.
   *
   * @param string $url
   *   The URL to share.
   *
   * @param string $title
   *   The title to use.
   *
   * @return string
   *   The link for sharing.
   */
  protected function _prepare_share_link($network, $url, $title) {
    $link = '';

    $link_options = array(
      'attributes' => array(
        'class' => array(
          'button-wrap',
          $network,
        ),
      ),
    );

    switch ($network) {
      case 'delicious':
        $full_url = Url::fromUri('http://del.icio.us/post?url=' . $url . '&amp;title=' . $title);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('Delicious'), $full_url);
        break;

      case 'digg':
        $full_url = Url::fromUri('http://www.digg.com/submit?phase=2&amp;url=' . $url . '&amp;title=' . $title);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('Digg it'), $full_url);
        break;

      case 'facebook':
        $full_url = Url::fromUri('https://www.facebook.com/sharer/sharer.php?u=' . $url . '&amp;title=' . $title);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('Facebook'), $full_url);
        break;

      case 'google':
        $full_url = Url::fromUri('https://plus.google.com/share?url=' . $url . '&amp;title=' . $title);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('Plus Share'), $full_url);
        break;

      case 'linkedin':
        $full_url = Url::fromUri('http://www.linkedin.com/shareArticle?mini=true&url=' . $url . '&amp;title=' . $title);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('LinkedIn'), $full_url);
        break;

      case 'pinterest':
        $full_url = Url::fromUri('https://www.pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('Pinterest'), $full_url);
        break;

      case 'stumbleupon':
        $full_url = Url::fromUri('http://www.stumbleupon.com/submit?url=' . $url . '&amp;title=' . $title);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('Stumbleupon'), $full_url);
        break;

      case 'twitter':

        $config = \Drupal::config('responsive_share_buttons.settings');
        $twitter_name = $config->get('twitter_name');


        if (!empty($twitter_name)) {
          $title .= t(' via @@twitter_name', array('@twitter_name' => $twitter_name));
        }

        $full_url = Url::fromUri('http://twitter.com/home?status=' . $title . ' ' . $url);
        $full_url->setOptions($link_options);
        $link = Link::fromTextAndUrl(t('Tweet'), $full_url, array(
          'attributes' => array(
            'class' => array(
              'button-wrap',
              $network,
            ),
          ),
        ));
        break;
    }

    return $link;
  }


}