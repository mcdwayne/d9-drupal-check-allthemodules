<?php

namespace Drupal\chinese_address\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\chinese_address\chineseAddressHelper;

/**
 * Plugin implementation of the 'chinese_address_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "chinese_address_formatter_type",
 *   label = @Translation("Chinese Address Formatter"),
 *   field_types = {
 *     "chinese_address_field_type"
 *   }
 * )
 */
class ChineseAddressFormatterType extends FormatterBase
{

    /**
   * {@inheritdoc}
   */
    public static function defaultSettings() 
    {
        return [
        'has_province' => true,
        'has_city' => true,
        'has_county' => true,
        'has_street' => true,
        'has_detail' => true,
        // Implement default settings.
        ] + parent::defaultSettings();
    }

    /**
   * {@inheritdoc}
   */
    public function settingsForm(array $form, FormStateInterface $form_state) 
    {
        $settings = $this->getSettings();

        $element['has_province'] = [
        '#type' => 'checkbox',
        '#title' => t('Has Province'),
        '#default_value' => $settings['has_province'],
        ];
        $element['has_city'] = [
        '#type' => 'checkbox',
        '#title' => t('Has City'),
        '#default_value' => $settings['has_city'],
        ];
        $element['has_county'] = [
        '#type' => 'checkbox',
        '#title' => t('Has County'),
        '#default_value' => $settings['has_county'],
        ];
        $element['has_street'] = [
        '#type' => 'checkbox',
        '#title' => t('Has Street'),
        '#default_value' => $settings['has_street'],
        ];

        $element['has_detail'] = [
        '#type' => 'checkbox',
        '#title' => t('Has Detail'),
        '#default_value' => $settings['has_detail'],
        ];

        return $element;
    }

    /**
   * {@inheritdoc}
   */
    public function settingsSummary() 
    {
        $summary = [];
        // Implement settings summary.
        return $summary;
    }

    /**
   * {@inheritdoc}
   */
    public function viewElements(FieldItemListInterface $items, $langcode) 
    {
        $elements = [];

        foreach ($items as $delta => $item) {
            $elements[$delta] = ['#markup' => $this->viewValue($item)];
        }

        return $elements;
    }

    /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
    protected function viewValue(FieldItemInterface $item) 
    {
        // The text value has no text format assigned to it, so the user input
        // should equal the output, including newlines.
        $settings = $this->getSettings();
        $address = $item->getValue();
        $address_names = chineseAddressHelper::chinese_address_get_region_index($address);

        $address['province'] = $settings['has_province'] && isset($address['province']) ? $address_names[$address['province']] : '';
        $address['city'] = $settings['has_city'] && isset($address['city']) && $address_names[$address['city']] != chineseAddressHelper::CHINESE_ADDRESS_NAME_HIDE ? $address_names[$address['city']] : '';
        $address['county'] = $settings['has_county'] && isset($address['county']) && $address['county'] ? $address_names[$address['county']] : '';
        $address['street'] = $settings['has_street'] && isset($address['street']) && $address['street'] ? $address_names[$address['street']] : '';
        $address['detail'] = $settings['has_detail'] && isset($address['detail']) ? $address['detail'] : '';

        foreach ($address as $i => $a) {
            if (!in_array($i, ['province', 'city', 'county', 'street', 'detail'])) {
                unset($address[$i]);
            }
        }

        return implode($address);
    }

}
