<?php

namespace Drupal\number_to_word\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Numbers_Words;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericFormatterBase;

/**
 * Plugin implementation of the 'number_to_word' formatter.
 *
 * @FieldFormatter(
 *   id = "number_to_word",
 *   label = @Translation("Number to word"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class NumberToWord extends NumericFormatterBase
{

    /**
   * {@inheritdoc}
   */
    public static function defaultSettings() 
    {
        return [
        'currency_sign' => '$',
        'currency_words' => 'dollars',
        'currency_fractions' => 'cents',
        'locale' => 'en_US',
        ] + parent::defaultSettings();
    }


    /**
   * {@inheritdoc}
   */
    public function settingsForm(array $form, FormStateInterface $form_state) 
    {

        $elements['locale'] = [
        '#type' => 'select',
        '#title' => t('Locale'),
        '#options' => [
        'en_US' => t('US'),
        'en_GB' => t('GB'),
        'en_IN' => t('IN'),
        ],
        '#default_value' => $this->getSetting('locale'),
        '#weight' => 5,
        ];

        $elements['currency_sign'] = [
        '#title' => t('Currency Sign'),
        '#type' => 'textfield',
        '#default_value' => $this->getSetting('currency_sign'),
        ];
        $elements['currency_words'] = [
        '#title' => t('Currency in Words'),
        '#type' => 'textfield',
        '#default_value' => $this->getSetting('currency_words'),
        ];
        $elements['currency_fractions'] = [
        '#title' => t('Currency in fractions'),
        '#type' => 'textfield',
        '#default_value' => $this->getSetting('currency_fractions'),
        ];

        return $elements;
    }

    /**
   * {@inheritdoc}
   */
    protected function numberFormat($number) 
    {
        $test = new Numbers_Words();

        $fnum = explode('.', $number);

        $number_part = $this->getSetting('currency_sign')
          . number_format($number, 2) . "<br>";

        $string_part = strtoupper(
            $test->toWords(
                $fnum[0], $this->getSetting('locale')
            )
        );

        //Code to add commas in the string.
        $tmp_string = explode(" ", trim($string_part));
        $count = count($tmp_string);
        $new_string = $tmp_string[$count - 3] . " " . $tmp_string[$count - 2]
          . " " . $tmp_string[$count - 1];

        if ($count > 3) {
            // Only now commas will need to be added to the text.
            for ($i = $count - 4; $i >= 0; $i = $i - 4) {
                if (isset($new_string)) {
                    $new_string = $tmp_string[$i - 3] . " "
                      . $tmp_string[$i - 2] . " " . $tmp_string[$i - 1] . " "
                      . $tmp_string[$i] . ", " . $new_string;
                }
            }
        }
        $string_part = $new_string . " "
          . strtoupper($this->getSetting('currency_words'));

        if (!empty($fnum[1])) {
            $string_part .= ' AND ';
            $string_part .= strtoupper(
                $test->toWords(
                    $fnum[1], $this->getSetting('locale')
                )
            ) . " "
              . strtoupper($this->getSetting('currency_fractions'));
        }
        return $number_part . $string_part;
    }
}
