<?php

/**
 * @file
 * Contains \Drupal\dsbox\Plugin\Field\FieldWidget\DrupalSwipeboxLinkWidget.
 */

namespace Drupal\dsbox\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'dsbox_link' widget.
 *
 * @FieldWidget(
 *   id = "dsbox_link",
 *   label = @Translation("Swipebox video link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class DrupalSwipeboxLinkWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'placeholder_url' => '',
      'placeholder_title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Video URL'),
      '#description' => $this->t('Examples: http://www.youtube.com/watch?v=XSGBVzeBUbk or short links like http://youtu.be/XSGBVzeBUbk or http://vimeo.com/54178821'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => isset($items[$delta]->url) ? $items[$delta]->url : NULL,
      '#element_validate' => array(array($this, 'validateUrl')),
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );
    $element['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('Leave empty to use the URL.'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
    );
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    if (!$form_state->get('default_value_widget') && $this->getFieldSetting('title') == DRUPAL_REQUIRED) {
      $element['#element_validate'][] = array($this, 'validateTitle');
    }

    // Exposing the attributes array in the widget is left for alternate and more
    // advanced field widgets.
    $element['attributes'] = array(
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => !empty($items[$delta]->attributes) ? $items[$delta]->attributes : array(),
      '#attributes' => array('class' => array('link-field-widget-attributes')),
    );

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['placeholder_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for video URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => $this->t('URL that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.<br />Examples: http://www.youtube.com/watch?v=XSGBVzeBUbk or http://youtu.be/XSGBVzeBUbk'),
    );
    $elements['placeholder_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for link text'),
      '#default_value' => $this->getSetting('placeholder_title'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#states' => array(
        'invisible' => array(
          ':input[name="instance[settings][title]"]' => array('value' => DRUPAL_DISABLED),
        ),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder_title = $this->getSetting('placeholder_title');
    $placeholder_url = $this->getSetting('placeholder_url');
    if (empty($placeholder_title) && empty($placeholder_url)) {
      $summary[] = $this->t('No placeholders');
    }
    else {
      if (!empty($placeholder_title)) {
        $summary[] = $this->t('Title placeholder: @placeholder_title', array('@placeholder_title' => $placeholder_title));
      }
      if (!empty($placeholder_url)) {
        $summary[] = $this->t('URL placeholder: @placeholder_url', array('@placeholder_url' => $placeholder_url));
      }
    }

    return $summary;
  }

  /**
   * Form element validation handler.
   *
   * Validates a proper video URL.
   */
  function validateURL(&$element, &$form_state, $form) {
    if ($element['#value'] !== '') {
      $rx = '~
        ^(?:https?://)?                        # Optional protocol
        (?:www\.)?                             # Optional subdomain
        (?:youtu\.be|youtube\.com|vimeo\.com)  # Mandatory domain name
        /(watch\?v=([^&]+)|[^&]+)              # URI with video id as capture group 1
        ~x';
      $has_match = preg_match($rx, $element['#value'], $matches);

      $error = array(
        'match' => FALSE,
        'message' => ''
      );
      if ($has_match) {
        if (preg_match('/youtu/', $matches[0])) {
          $error = $this->validateMatchYoutube($matches, $error);
        }
      }
      else {
        $error['match'] = TRUE;
        $error['message'] = $this->t('Please enter a supported URL. Supported are YouTube and Vimeo, should look like this: http://www.youtube.com/watch?v=VideoID, http://www.youtube.com/watch?v=VideoID&hd=1, http://youtu.be/VideoID> or http://vimeo.com/54178821. A YouTube VideoID must consist of 11 characters.');
      }

      if ($error['match']) {
        $form_state->setError($element, $this->t('Field %name: @message', array('%name' => $element['#title'], '@message' => $error['message'])));
      }
    }
  }

  /**
   * Helper function for form element validation handler.
   *
   * Check the YouTube video ID length.
   *
   * @param array $matches
   *   The pattern matches.
   *
   * @param array $error
   *   The default form element error values.
   *
   * @return array
   *   The array values, needed to diplay the form element error.
   */
  private function validateMatchYoutube(&$matches, $error) {
    $video_id = array_pop($matches);
    if (strlen($video_id) != 11) {
      $error['match'] = TRUE;
      $error['message'] = $this->t('Please enter a valid YouTube video ID. The video ID must consist of 11 characters.');
    }

    return $error;
  }

  /**
   * Form element validation handler.
   *
   * Conditionally requires the link title if a URL value was filled in.
   */
  public function validateTitle(&$element, FormStateInterface  $form_state) {
    if ($element['url']['#value'] !== '' && $element['title']['#value'] === '') {
      $element['title']['#required'] = TRUE;
      $form_state->setError($element['title'], $this->t('The %name field is required.', array('%name' => $element['title']['#title'])));
    }
  }

}
