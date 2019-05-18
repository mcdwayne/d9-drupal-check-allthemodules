<?php

/**
 * @file
 * Contains Drupal\filter\Plugin\Filter\FilterPhonetic
 */

namespace Drupal\phonetic\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to display to mask curse words.
 *
 * @Filter(
 *   id = "filter_phonetic",
 *   title = @Translation("Phonetic Word Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = -10
 * )
 */
class FilterPhonetic extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $blacklist = $this->settings['phonetic_blacklist'];

    if (!is_array($blacklist)) {
      $blacklist = explode("\n", $blacklist);
    }

    foreach($blacklist as $blword){
      $processed_blacklist[$blword] = metaphone($blword);
    }

    $whitelist = $this->settings['phonetic_whitelist'];

    if (!is_array($whitelist)) {
      $whitelist = explode("\n", $whitelist);
    }

    $char = $this->settings['phonetic_replacement_char'];
    $words = str_word_count(strip_tags($text), 2);

    foreach ($words as $word) {
    //create word sound
      $metaphone = metaphone($word);

        foreach ($processed_blacklist as $original => $phonetic) {
          // Phonetic filtering.
          if ($phonetic == $metaphone) {
            // Whitelist filtering.
            if (!in_array($word, $whitelist)) {
              // Replacement.
              $replace = str_pad('', strlen($word), $char);
              $text = str_replace($word, $replace, $text);
            }
            // Word replaced, no need to go further in the blacklist.
          }else if(similar_text(strtolower($word), $original) >= 80){
            // Replacement.
            $replace = str_pad('', strlen($word), $char);
            $text = str_replace($word, $replace, $text);
          }
        }
      }
    return new FilterProcessResult($text);
  }


  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Masks curse words with replacement characters based on the sound of a string.');
  }

  /**
   * {@inheritdoc}
   */

  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['phonetic_mask_node_title'] = array(
      '#title' => t('Filter node titles'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings['phonetic_mask_node_title'],
      '#description' => t('Check this if you would like to filter node titles.'),
    );
    $form['phonetic_replacement_char'] = array(
      '#title' => t('Replacement character'),
      '#type' => 'textfield',
      '#size'=> 2,
      '#default_value' => $this->settings['phonetic_replacement_char'],
      '#description' => t('Specify the character that will be used to replace filtered words.'),
    );

    $form['phonetic_blacklist'] = array(
      '#title' => t('blacklist'),
      '#type' => 'textarea',
      '#default_value' => $this->settings['phonetic_blacklist'],
      '#description' => t('Enter all unwanted words. Type one word per line. See WORDS.txt in the module directory.'),
    );
    $form['phonetic_whitelist'] = array(
      '#title' => t('Whitelist'),
      '#type' => 'textarea',
      '#default_value' => $this->settings['phonetic_whitelist'],
      '#description' => t('Enter all words which got accidentely filtered by this module. Type one word per line.'),
    );

    return $form;
  }

}
