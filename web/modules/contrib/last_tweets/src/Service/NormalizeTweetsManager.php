<?php

namespace Drupal\last_tweets\Service;

/**
 * Class NormalizeTweetsManager.
 *
 * @package Drupal\last_tweets\Service
 */
class NormalizeTweetsManager {

  /**
   * Normalize.
   *
   * @param array $tweets
   *   Tweets.
   *
   * @return array
   *   Renderable array.
   */
  public function normalize(array $tweets) {

    foreach ($tweets as $t) {
      if (is_object($t)) {
        // Build tweet links.
        $textArr = explode(" ", $this->buildLinks($t));

        // Build hastags links.
        $textArr = $this->buildHashtags($t, $textArr);

        // Build user mentions links.
        $textArr = $this->buildUserMentions($t, $textArr);

        // Get tweet media.
        $t = property_exists($t,
          'retweeted_status') ? $t->retweeted_status : $t;
        $mediaArr = isset($t->extended_entities->media) ? $t->extended_entities->media : isset($t->entities->media) ? $t->entities->media : [];
        $this->buildMedia($t, $mediaArr);
        $t->full_text = implode(" ", $textArr);
      }
    }
    return [
      '#theme' => 'tweets_list',
      '#tweets' => $tweets,
      '#user_screen_name' => $tweets[0]->user->name,
    ];
  }

  /**
   * Build tweets links.
   *
   * @param \stdClass $t
   *   Tweet.
   *
   * @return mixed
   *   Tweet text.
   */
  protected function buildLinks(\stdClass &$t) {
    if (!empty($t->entities->urls)) {
      $tweetUrls = $t->entities->urls;
      foreach ($tweetUrls as $tweetUrl) {
        $tUrl = $tweetUrl->url;
        $t->full_text = str_replace($tUrl,
          '<a class="tweet-url" title="link tweet" href="' . $tUrl . '" >' . $tUrl . '</a>',
          $t->full_text);
      }
    }
    return $t->full_text;
  }

  /**
   * Build hashtags.
   *
   * @param \stdClass $t
   *   Tweet.
   * @param array $textArr
   *   Text array.
   *
   * @return mixed
   *   Text with hastag links.
   */
  protected function buildHashtags(\stdClass &$t, array $textArr) {
    if (!empty($t->entities->hashtags)) {
      $hashtags = $t->entities->hashtags;
      foreach ($hashtags as $hashtag) {
        $tag = "#" . $hashtag->text;
        $tagLink = substr_replace($tag, '', 0, 1);
        foreach ($textArr as $key => $text) {
          $textArr[$key] = str_replace($tag,
            '<a class="hashtag" title="hashtag" href="https://twitter.com/hashtag/' . $tagLink . '?src=hash" >' . $tag . '</a>',
            $textArr[$key]);
        }
      }
    }
    return $textArr;
  }

  /**
   * Build user mentions.
   *
   * @param \stdClass $t
   *   Tweet.
   * @param array $textArr
   *   Text array.
   *
   * @return mixed
   *   Text array.
   */
  protected function buildUserMentions(\stdClass &$t, array $textArr) {
    if (!empty($t->entities->user_mentions)) {
      $userMentions = $t->entities->user_mentions;
      foreach ($userMentions as $userMention) {
        $screenName = "@" . $userMention->screen_name;
        foreach ($textArr as $index => $text) {
          $screenNameLink = substr_replace($screenName, '', 0, 1);
          $title = t('mentionned user');
          $textArr[$index] = str_replace($screenName,
            '<a class="user-mentions" title=' . $title . ' href="https://twitter.com/' . $screenNameLink . '" >' . $screenName . '</a>',
            $textArr[$index]);
        }
      }
    }
    return $textArr;
  }

  /**
   * Build media.
   *
   * @param \stdClass $t
   *   Tweet.
   * @param array $mediaArr
   *   Media array.
   */
  protected function buildMedia(\stdClass &$t, array $mediaArr) {
    if (!empty($mediaArr)) {
      $t->tweet_media = '';
      foreach ($mediaArr as $media) {
        // Photo.
        if ($media->type == "photo") {
          $t->tweet_media .= '<a href="' . $media->url . '"><img alt="photo tweet" src = "' . $media->media_url_https . '"><a/>';
        }
        // Video.
        if ($media->type == "video") {
          $video_url = $this->getVideoUrl($media->video_info->variants);
          if ($video_url) {
            $t->tweet_media .= '<video controls="" autoplay="" width="500px" height="auto" poster="' . $media->media_url_https . '"><source src="' . $video_url . '" type="video/mp4"></video>';
          }
        }
      }
    }
  }

  /**
   * Video Url.
   *
   * @param array $variants
   *   Array of video variants from api.
   *
   * @return null|string
   *   Video url.
   */
  protected function getVideoUrl(array $variants) {
    if ($variants) {
      foreach ($variants as $variant) {
        if ($variant->content_type == 'video/mp4') {
          return $variant->url;
        }
      }
    }
    return NULL;
  }

}
