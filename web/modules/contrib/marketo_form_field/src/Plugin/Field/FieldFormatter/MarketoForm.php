<?php

namespace Drupal\marketo_form_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\encryption\EncryptionTrait;
use Drupal\marketo_ma\Service\MarketoMaServiceInterface;

/**
 * Plugin implementation of the 'marketo_form' formatter.
 *
 * @FieldFormatter(
 *   id = "marketo_form",
 *   label = @Translation("Marketo Form"),
 *   field_types = {
 *     "marketo_form_field",
 *   },
 * )
 */
class MarketoForm extends FormatterBase {

  use EncryptionTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $config = \Drupal::config(MarketoMaServiceInterface::MARKETO_MA_CONFIG_NAME);
    $munchkinId = $this->decrypt($config->get('munchkin.account_id'));
    $instanceHost = $config->get('instance_host');

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '<script src="//{{ instance_host }}/js/forms2/js/forms2.min.js"></script><form id="mktoForm_{{ marketo_form_id }}"></form>',
        '#context' => [
          'marketo_form_id' => $item->value,
          'instance_host' => $instanceHost,
        ],
        '#attached' => [
          'library' => ['marketo_form_field/marketo-form'],
          'drupalSettings' => [
            'marketo_form_field' => [
              'marketoForms' => [
                [
                  'formId' => $item->value,
                  'successMessage' => $item->success_message ?: 'Thank you for submitting.',
                ],
              ],
              'munchkinId' => $munchkinId,
              'instanceHost' => "//$instanceHost",
            ],
          ],
        ],
      ];
    }
    return $elements;
  }

}
