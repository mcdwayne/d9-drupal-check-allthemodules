<?php

namespace Drupal\commerce_currencies_price\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_currencies_price_default' widget.
 *
 * @FieldWidget(
 *   id = "commerce_currencies_price_default",
 *   label = @Translation("Commerce currencies price"),
 *   field_types = {
 *     "commerce_currencies_price"
 *   },
 * )
 */
class CurrenciesPriceDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'required_prices' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements = [];
    $elements['required_prices'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required prices'),
      '#description' => $this->t('Select if you want that is required to enter price for all currencies'),
      '#default_value' => $this->getSetting('required_prices'),
      '#required' => FALSE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Required all currency prices : @required_prices', ['@required_prices' => $this->getSetting('required_prices') ? t('Yes') : t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_currencies_price\Plugin\Field\FieldType\CurrenciesPrice $item */
    $item = $items[$delta];

    $default = $item->getEntity()->isNew() ? [] : $item->toArray();

    $element['prices'] = [
      '#type' => 'commerce_currencies_price',
      '#default_value' => $default,
      '#required_prices' => $this->getSetting('required_prices'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $delta => $value) {
      $new_values[$delta]['prices'] = $value['prices'];
    }
    return $new_values;
  }

}
