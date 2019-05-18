<?php

namespace Drupal\paragraphs_wrapper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'Paragraphs wrapper' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraphs_wrapper",
 *   label = @Translation("Paragraphs wrapper"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsWrapperFormatter extends EntityReferenceRevisionsEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $groupCount = 0;
    $itemCount = 0;
    $view = parent::view($items, $langcode);
    $paragraphWraps = [];
    $lastParagraph = FALSE;
    foreach (Element::children($view) as $childKey) {
      $el = $view[$childKey];
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $el['#paragraph'];
      // If we've been through the loop onces and this paragraph is of the same
      // bundle as the last one then add it to that group.
      if (
        $itemCount > 0
        && $lastParagraph
        && ($paragraph->bundle() == $lastParagraph->bundle())
      ) {
        $lastEl = &$paragraphWraps[($groupCount - 1)];
        $lastEl['#items'][] = $el;
      }
      // Otherwise we need to start a new group...
      else {
        /** @var \Drupal\node\Entity\Node $parent */
        $parent = $items->getParent()->getValue();
        $paragraphWraps[$groupCount] = [
          '#theme' => 'paragraphs_wrapper_wrap',
          '#bundle' => $paragraph->bundle(),
          '#parent' => $parent,
          '#items' => [$el],
        ];
        $groupCount++;
      }
      $itemCount++;
      $lastParagraph = $paragraph;
    }
    unset($view['#items']);
    $view['#theme'] = 'paragraphs_wrapper_container';
    $view['#wraps'] = $paragraphWraps;
    return $view;
  }

}
