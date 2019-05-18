<?php


namespace Drupal\flag_rating\Plugin\ActionLink;

use Drupal\Core\Url;
use Drupal\flag\FlagInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\ActionLink\ActionLinkTypeBase;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Provides the AJAX Rating link type.
 *
 * This class is a kind of duplicate of the AJAXactionLink type, but modified to
 * provide rating functionality.
 *
 * @ActionLinkType(
 *   id = "ajax_rating",
 *   label = @Translation("AJAX Rating"),
 *   description = "An AJAX JavaScript request which send a number alongside the flagging."
 * )
 */
class AJAXactionLinkRating extends ActionLinkTypeBase {

  /**
   * Undocumented function
   *
   * @param FlagInterface $flag
   * @return array
   *    The icon as renderable array.
   */
  protected function getIcon(FlagInterface $flag) {
    $icon = [];

    if ($fid = $flag->getThirdPartySetting('flag_rating', 'action_icon', NULL)) {
      if ($file = file_load((int) $fid)) {
        if (
          empty(file_validate_extensions($file, 'svg')) &&
          $raw = file_get_contents(file_create_url($file->getFileUri()))
        ) {
          $icon = [
            '#type' => 'inline_template',
            '#template' => '{{ svg|raw }}',
            '#context' => [
              'svg' => strip_tags($raw, '<svg></svg><path></path><g></g><polygon></polygon>')
            ]            
          ];
        }
      }
      else if ($file = flag_rating_create_default_icon()) {
        $icon = [
          '#theme' => 'image',
          '#uri' => $file->getFileUri(),
        ];
      }
    }
    return $icon;
  }

  /**
   * {@inheritDoc}
   */
  protected function getUrl($action, FlagInterface $flag, EntityInterface $entity) {
    switch($action) {
      case 'flag':
        return Url::fromRoute('flag.action_link_flag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);

      default:
        return Url::fromRoute('flag.action_link_unflag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity) {
    $render = [];
    $action = $this->getAction($flag, $entity);
    $access = $flag->actionAccess($action, $this->currentUser, $entity);

    if(!$access->isAllowed()) {
      return $render;
    }

    // Get default values.
    $score = (int) 4; // @todo GET REAL SCORE for this flag.
    $min = (int) $flag->getThirdPartySetting('flag_rating', 'score_min', 1);
    $max = (int) $flag->getThirdPartySetting('flag_rating', 'score_max', 5);
    $build['#min'] = $min;
    $build['#max'] = $max;
    $build['#score'] = $score;
    
    // Generate links.
    $items = [];
    for ($i = $min; $i <= $max; $i++) {
      $url = $this->getUrl($action, $flag, $entity, $i);
      $url->setRouteParameter('rating', $i);
      $url->setRouteParameter('destination', $this->getDestination());
      $items_classes = [
        ($i <= $score) ? 'is-active' : '',
        'use-ajax',
      ];
      $render['#items'][$i] = [
        '#type' => 'inline_template',
        '#template' => '<a href="{{ href }}" class="{{ classes }}" title="{{ title }}">{{ icon|render }}</a>',
        '#context' => [
          'icon' => $this->getIcon($flag),
          'href' => $url->toString(),
          'title' => $flag->getLongText($action) . ': ' . $i . '/' . $score,
          'classes' => implode(' ', $items_classes),
        ],
        '#access' => $access->isAllowed(),
      ];
      
      unset($url);
    }

    
    // Use our custom theme and library.
    $render['#flag'] = $flag;
    $render['#flaggable'] = $entity;
    $render['#theme'] = 'flag_rating';
    $render['#attached']['library'][] = 'flag_rating/flag.rating';

    CacheableMetadata::createFromRenderArray($items)
      ->addCacheableDependency($access)
      ->applyTo($render);
      
    return $render;
  }

}
