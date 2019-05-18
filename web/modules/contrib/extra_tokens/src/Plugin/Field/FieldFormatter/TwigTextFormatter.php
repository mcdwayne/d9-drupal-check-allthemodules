<?php
namespace Drupal\extra_tokens\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;


/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "twig_text",
 *   label = @Translation("Twig text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class TwigTextFormatter extends TextDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    $twig = \Drupal::service('twig');
    foreach ($items as $delta => $item) {
      $value = $item->value;
      /** @var  $template \Twig_Template */
      $template = $twig->createTemplate($value);
      $value = $template->render([]);
      $elements[$delta] = [
        '#type' => 'processed_text',
        '#text' => $value,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
        '#cache' => ['tags' => ['config:extra_tokens.settings']],
      ];
    }
    return $elements;
  }
}