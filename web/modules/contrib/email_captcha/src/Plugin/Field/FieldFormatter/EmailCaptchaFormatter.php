<?php
/**
 * @file
 * Contains Drupal\email_captcha\Plugin\Field\FieldFormatter\EmailCaptchaFormatter.
 */


namespace Drupal\email_captcha \Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'email_captcha_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "email_captcha",
 *   module = "email_captcha",
 *   label = @Translation("Email Captcha"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class EmailCaptchaFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity_id = $items->getEntity()->id();
    $entity_type = $items->getEntity()->getEntityTypeId();
    $field_name = $items->getName();

    foreach ($items as $delta => $item) {
      $html_id = "$entity_type-$field_name-$entity_id-$delta";
      $elements[$delta] = [
        'link' => [
          '#type' => 'link',
          '#title' => $this->t('View email'),
          '#url' => Url::fromUri('internal:/email_captcha/captcha'),
          '#attributes' => [
            'class' => 'use-ajax',
            'data-dialog-type' => 'modal',
          ],
          '#prefix' => '<span class="email-captcha-link">',
          '#suffix' => '</span>',
          '#attached' => [
            'library' => [
              'core/drupal.ajax',
            ],
          ],
          '#options' => [
            'query' => [
              'entity_type' => $entity_type,
              'entity_id' => $entity_id,
              'field_name' => $field_name,
              'delta' => $delta,
            ],
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * Encode email to format ***@****.***.
   */
  private function EncodeEmail($email) {
    $parts = explode('@', $email);
    $name = $this->ReplaceCharsToX($parts[0]);
    $domain = [];
    $parts = explode('.', $parts[1]);

    foreach ($parts as $part) {
      $domain[] = $this->ReplaceCharsToX($part);
    }

    $domain_text = implode('.', $domain);
    return "$name@$domain_text";
  }

  /**
   * Replace every character in the text to *.
   */
  private function ReplaceCharsToX($text) {
    $result = '';

    for ($i = 0, $len = strlen($text); $i < $len; $i++) {
      $result .= '*';
    }

    return $result;
  }
}
