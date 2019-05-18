<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 20.12.2018
 * Time: 22:28
 */

namespace Drupal\hashtags\Plugin\Filter;
use Drupal\Core\Link;
use Drupal\Core\Url;

/*
 * Helper class to pass parameters to callback function within preg_replace_callback
 */
class LinksConverter {
    private $hashtags_tids;

    function __construct($hashtags_tids) {
        $this->hashtags_tids = $hashtags_tids;
    }

    function replace($matches) {
        if (isset($this->hashtags_tids)) {
            $hashtags_tids = $this->hashtags_tids;
        }
        $first_delimeter = isset($matches[1]) ? $matches[1] : '';
        $hashtag_name = isset($matches[3]) ? $matches[3] : '';
        $hashtag_tid = isset($this->hashtags_tids[strtolower($hashtag_name)]) ? $this->hashtags_tids[strtolower($hashtag_name)] : '';
        $hashtag_name = '#'.$hashtag_name;
        // hashtag is not exists - show without link
        if (empty($hashtag_tid)) {
            return $first_delimeter . $hashtag_name;
        }
        // Fatal error: [] operator not supported for strings in /includes/common.inc on line 2442
        // Issue comes up when we try to bind attribute to link which has path parameter of the current page............
        /*if ($_GET['q'] == 'taxonomy/term/'.$hashtag_tid) {
            $hashtag_link = l($hashtag_name, 'taxonomy/term/'.$hashtag_tid);
        } else {
            $hashtag_link = l($hashtag_name, 'taxonomy/term/'.$hashtag_tid, array('attributes' => array('class' => 'hashtag')));
        }*/
        $hashtag_link = Link::fromTextAndUrl($hashtag_name,
            Url::fromRoute('entity.taxonomy_term.canonical', array('taxonomy_term' => $hashtag_tid),
                array('attributes' => array('class' => 'hashtag'))))->toString();

        return $first_delimeter . $hashtag_link;
    }
}