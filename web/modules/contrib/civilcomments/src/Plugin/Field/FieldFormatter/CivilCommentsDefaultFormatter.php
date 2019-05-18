<?php

namespace Drupal\civilcomments\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'civilcomments_default' formatter.
 *
 * @FieldFormatter(
 *   id = "civilcomments_default",
 *   label = @Translation("Civil Comments default"),
 *   field_types = {
 *     "civil_comments"
 *   }
 * )
 */
class CivilCommentsDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   *
   * Currently returning #status, but not actually using it. Reserved for use
   * in closing comments to new submissions, while still displaying them on the
   * entity.
   *
   * @todo
   *   - Find out how civilcomments expects to do this, and use #status to do
   *     it.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $user = \Drupal::currentUser();
    if (!$user->hasPermission('view civilcomments')) {
      return $elements;
    }

    $entity_uuid = $items->getEntity()->uuid();
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'civilcomments_formatter',
        '#status' => $item->status,
        '#attached' => [
          'library' => [
            'civilcomments/civilcomments.default',
          ],
          'drupalSettings' => [
            'civilcomments' => [
              'site_id' => \Drupal::config('civilcomments.settings')->get('civilcomments_site_id'),
              'content_id' => $entity_uuid,
              'lang' => \Drupal::config('civilcomments.settings')->get('civilcomments_lang'),
            ],
          ],
        ],
      ];
    }

    return $elements;
  }

}
