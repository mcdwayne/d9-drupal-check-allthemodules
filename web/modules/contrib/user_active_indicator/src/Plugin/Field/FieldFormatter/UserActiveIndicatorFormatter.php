<?php

/**
 * @file
 * Contains \Drupal\user_active_indicator\Plugin\Field\FieldFormatter\UserActiveIndicatorFormatter.
 */

namespace Drupal\user_active_indicator\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'uai_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "uai_formatter",
 *   label = @Translation("User Active Indicator"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class UserActiveIndicatorFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_user_picture' => 'yes',
      'image_style' => 'thumbnail',
      'link_to_user' => 'yes',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['show_user_picture'] = [
      '#title' => $this->t('Show User Picture'),
      '#type' => 'select',
      '#options' => [
        'yes' => $this->t('Yes'),
        'no' => $this->t('No'),
      ],
      '#default_value' => $this->getSetting('show_user_picture') ?: 'yes',
    ];

    $element['image_style'] = [
      '#title' => $this->t('Image Style'),
      '#type' => 'select',
      '#options' => uai_imageStyleOptions(),
      '#default_value' => $this->getSetting('image_style') ?: 'thumbnail',
    ];

    $element['link_to_user'] = [
      '#title' => $this->t('Link Username to User'),
      '#type' => 'select',
      '#options' => [
        'yes' => $this->t('Yes'),
        'no' => $this->t('No'),
      ],
      '#default_value' => $this->getSetting('link_to_user') ?: 'yes',
    ];

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];
    $settings = $this->getSettings();
    $summary[] = $this->t('Show user picture: ') . $settings['show_user_picture'];
    $summary[] = $this->t('Image Style: ') . $settings['image_style'];
    $summary[] = $this->t('Link to user: ') . $settings['link_to_user'];

    return $summary;

  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $valueReturn = [];

    foreach ($items as $delta => $item) {

      $userPictureURI = $item->entity->user_picture->entity->uri->value;
      $userPictureURL = ImageStyle::load($this->getSetting('image_style'))->buildUrl($userPictureURI);

      $name = $item->entity->name->value;
      $uid = $item->entity->uid->value;
      $showUserPicture = $this->getSetting('show_user_picture');
      $linkToUser = $this->getSetting('link_to_user');

      $uidTimestamp = uai_getUserData()->get('page_access_user_data_timestamp', $uid, 'access_timestamp');

      if (isset($uidTimestamp)) {

        if (uai_moduleValues()['dateFormatOption'] == 'custom') {
          $timestamp = date(uai_moduleValues()['customDateFormat'], $uidTimestamp);
        }
        else {
          $timestamp = \Drupal::service('date.formatter')->formatTimeDiffSince($uidTimestamp) . ' ago';
        }

        if ($uidTimestamp > (time() - uai_moduleValues()['duration'])) {
          $activeClass = 'active';
        }
        else {
          $activeClass = 'inactive';
        }

        if (uai_moduleValues()['showMark'] == 'yes' && uai_moduleValues()['showTimestamp'] == 'yes') {

          $newUsername = new TranslatableMarkup(
            '<span class="uai uai-name @activeClass">@name</span><mark class="uai uai-mark @activeClass"></mark><span class="uai uai-message @activeClass@noText">@activeMessage</span><span class="uai uai-timestamp @activeClass">@timestamp</span>', [
              '@name' => t($name),
              '@activeClass' => t($activeClass),
              '@noText' => t(uai_noText()),
              '@activeMessage' => t(uai_moduleValues()['activeMessage']),
              '@timestamp' => $timestamp,
            ]
          );

        }

        if (uai_moduleValues()['showMark'] == 'yes' && uai_moduleValues()['showTimestamp'] == 'no') {

          $newUsername = new TranslatableMarkup(
            '<span class="uai uai-name @activeClass">@name</span><mark class="uai uai-mark @activeClass"></mark>', [
              '@name' => t($name),
              '@activeClass' => t($activeClass),
            ]
          );

        }

        if (uai_moduleValues()['showMark'] == 'no' && uai_moduleValues()['showTimestamp'] == 'yes') {

          $newUsername = new TranslatableMarkup(
            '<span class="uai uai-name @activeClass">@name</span><span class="uai uai-message @activeClass@noText">@activeMessage</span><span class="uai uai-timestamp @activeClass">@timestamp</span>', [
              '@name' => t($name),
              '@activeClass' => t($activeClass),
              '@noText' => t(uai_noText()),
              '@activeMessage' => t(uai_moduleValues()['activeMessage']),
              '@timestamp' => $timestamp,
            ]
          );

        }

        $valueReturn[$delta] = [
          '#theme' => 'uai_formatter',
          '#show_user_picture' => $showUserPicture,
          '#link_to_user' => $linkToUser,
          '#user_picture_url' => $userPictureURL,
          '#uid' => $uid,
          '#markup' => $newUsername,
        ];

      }
      elseif (!isset($uidTimestamp)) {

        $newUsername = new TranslatableMarkup(
          '<span class="uai uai-name inactive">@name</span><span class="uai uai-message inactive@noText">@noDataMessage</span>', [
            '@name' => t($name),
            '@noText' => t(uai_noText()),
            '@noDataMessage' => t(uai_moduleValues()['noDataMessage']),
          ]
        );

        $valueReturn[$delta] = [
          '#theme' => 'uai_formatter',
          '#show_user_picture' => $showUserPicture,
          '#link_to_user' => $linkToUser,
          '#user_picture_url' => $userPictureURL,
          '#uid' => $uid,
          '#markup' => $newUsername,
        ];

      }

      $valueReturn[$delta]['#cache']['max-age'] = 120;

    }

    return $valueReturn;

  }

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'user');
  }

}
