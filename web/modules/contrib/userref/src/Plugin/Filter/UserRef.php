<?php

namespace Drupal\userref\Plugin\Filter;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for markdown.
 *
 * @Filter(
 *   id = "userref",
 *   module = "userref",
 *   title = @Translation("User Reference Filter"),
 *   description = @Translation("Substitutes @smokris with link to user page."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class UserRef extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(
      preg_replace_callback(
        '/\B\@([a-zA-Z0-9._]+)\b/',
        function ($matches) {
          $u = user_load_by_name($matches[1]);
          return $u ? Link::fromTextAndUrl("@" . $u->getDisplayName(), Url::fromRoute('entity.user.canonical', ['user' => $u->id()]))->toString() : $matches[0];
        },
        $text
      )
    );
  }

  public static function makeUserLink($name, $uid) {
    $u = user_load_by_name($matches[1]);
    return $u ? Link::fromTextAndUrl("@" . $name, Url::fromUri("user/" . $uid))->toString() : $matches[0];
  }

  /**
   * Get the tips for the filter.
   *
   * @param bool $long
   *   If get the long or short tip.
   *
   * @return string
   *   The tip to show for the user.
   */
  public function tips($long = FALSE) {
    return $this->t("Substitutes @smokris with link to user page.");
  }
}
