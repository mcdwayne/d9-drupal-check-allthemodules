<?php

namespace Drupal\cumulio\Plugin\Field\FieldWidget;

use Cumulio\Cumulio;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'cumulio_widget' widget.
 *
 * @FieldWidget(
 *   id = "cumulio_widget",
 *   label = @Translation("Cumulio widget"),
 *   field_types = {
 *     "cumulio_field"
 *   }
 * )
 */
class CumulioWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('cumulio.settings');
    $token = $config->get('api_token');
    $key = $config->get('api_key');
    $client = Cumulio::initialize($key, $token);
    $form_id = isset($items[$delta]->value) ? $items[$delta]->value : '';

    $data = $client->get('securable', [
      'type' => 'dashboard',
      'attributes' => ['name', 'contents', 'id'],
      'include' => [
        [
          'model' => 'Thumbnail',
          'attributes' => ['url'],
          'where' => [
            'size' => '512px',
          ],
        ],
      ],
    ]
    );

    $options = [];
    foreach ($data['rows'] as $row) {
      if (isset($row['name']['en'])) {
        $options[$row['id']] = $row['name']['en'];
      }
      else {
        // Reset the array back to the start.
        reset($row['name']);

        // Fetch the key from the current element.
        $key = key($row['name']);
        $options[$row['id']] = $row['name'][$key];

      }
    }

    $element += [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $form_id,
      '#empty_option' => t('- Select a dashboard -'),
    ];

    return [
      'value' => $element,
    ];
  }

}
