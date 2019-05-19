<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\views\Views;
use Drupal\viewsreference\Plugin\Field\FieldType\ViewsReferenceItem;

/**
 * Adds the file URI to embedded file entities.
 */
class ViewsReferenceFieldItemNormalizer extends ComplexDataNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = ViewsReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = $field_item->getValue();
    $display_id = $values['display_id'];
    $argument = $values['argument'];

    // TODO: fix loading this view disabled the X-Drupal-Dynamic-Cache
    // because of the user context.
    $view = Views::getView($values['target_id']);

    $view->setDisplay($display_id);

    // Someone may have deleted the View.
    if (!is_object($view)) {
      return;
    }
    // No access.
    if (!$view->access($display_id)) {
      return;
    }

    $arguments = [];
    if ($argument) {
      $arguments = [$argument];
      if (preg_match('/\//', $argument)) {
        $arguments = explode('/', $argument);
      }

      $node = \Drupal::routeMatch()->getParameter('node');
      $token_service = \Drupal::token();
      if (is_array($arguments)) {
        foreach ($arguments as $index => $argument) {
          if (!empty($token_service->scan($argument))) {
            $arguments[$index] = $token_service->replace($argument, ['node' => $node]);
          }
        }
      }
    }

    $build = $view->display_handler->buildRenderable($arguments, TRUE);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $output = (string) $renderer->renderRoot($build);
    /* TODO: find smarter way to do this */
    $return = Json::decode($output);

    if (!empty($context['cacheability'])) {
      $context['cacheability']->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
    }

    return $return;
  }

}
