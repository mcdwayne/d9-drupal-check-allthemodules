<?php

namespace Drupal\amp\Element;

use Drupal\filter\Element\ProcessedText;
use Symfony\Component\HttpFoundation\Request;
use Drupal\amp\AMP\DrupalAMP;
use Drupal\amp\Service\AMPService;
use Drupal\Component\Utility\Xss;

/**
 * Provides an amp-processed text render element.
 *
 * @RenderElement("amp_processed_text")
 */
class AmpProcessedText extends ProcessedText {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#text' => '',
      '#format' => NULL,
      '#filter_types_to_skip' => array(),
      '#langcode' => '',
      '#pre_render' => array(
        array($class, 'preRenderText'),
        array($class, 'preRenderAmpText'),
      ),
      '#cache' => [
        'contexts' => ['url.query_args:amp', 'url.query_args:debug'],
        'tags' => ['config:amp.settings']
      ]
    );
  }

  /**
   * Pre-render callback: Processes the amp markup and attaches libraries.
   */
  public static function preRenderAmpText($element) {

    /** @var AMPService $amp_service */
    $amp_service = \Drupal::service('amp.utilities');
    /** @var AMP $amp */
    $amp = $amp_service->createAMPConverter();

    $amp->loadHtml($element['#markup']);
    $element['#markup'] = $amp->convertToAmpHtml();
    $element['#allowed_tags'] = array_merge(Xss::getAdminTagList(), ['amp-img']);

    if (!empty($amp->getComponentJs())) {
      $element['#attached']['library'] = $amp_service->addComponentLibraries($amp->getComponentJs());
      $element['#allowed_tags'] = array_merge($amp_service->getComponentTags($amp->getComponentJs()), $element['#allowed_tags']);
    }
    return $element;
  }
}
