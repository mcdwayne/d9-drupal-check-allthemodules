<?php

/**
 * @file
 * Contains \Drupal\tweetbutton\Plugin\Field\FieldType\TweetbuttonItem.
 */

namespace Drupal\tweetbutton\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'tweetbutton' field type.
 *
 * @FieldType(
 *   id = "tweetbutton",
 *   label = @Translation("Tweetbutton"),
 *   description = @Translation("Creates a tweetbutton field."),
 *   default_widget = "tweetbutton",
 *   default_formatter = "tweetbutton_formatter_horizontal"
 * )
 */
class TweetbuttonItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'text' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => FALSE,
        ),
        'account' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'text' => array('text'),
        'account' => array('account'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    $element = array();
    $config = \Drupal::config('tweetbutton.settings');
    $settings = $this->getFieldDefinition()->getField()->getSettings();

    $element['tweet_text'] = array(
      '#type' => 'textfield',
      '#title' =>  t('Tweet text'),
      '#default_value' => isset($settings['tweet_text']) ? $settings['tweet_text'] : $config->get('tweetbutton_tweet_text'),
    );
    $element['author_twitter'] = array(
      '#type' => 'textfield',
      '#title' => t('Author twitter account'),
      '#default_value' => isset($settings['author_twitter']) ? $settings['author_twitter'] : $config->get('tweetbutton_account'),
      '#description' => t('This user will be @mentioned in the suggested'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    return $constraints;
  }

}
