<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\Plugin\Field\FieldWidget\SjiSocialConnectWidget.
 */

namespace Drupal\sjisocialconnect\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget as ImageWidget;

/**
 * Plugin implementation of the 'sjisocialconnect' widget.
 *
 * @FieldWidget(
 *   id = "sjisocialconnect",
 *   label = @Translation("Sji social connect"),
 *   field_types = {
 *     "sjisocialconnect"
 *   }
 * )
 */
class SjiSocialConnectWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'provider' => '',
      'message_label' => '',
      'message' => '',
      'rows' => 3,
      'provider' => '',
      'max_length' => 115,
      'placeholder' => t('Message to be post on social network.'),
    ) + ImageWidget::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element += ImageWidget::settingsForm($form, $form_state);
    // Message.
    $element['message_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label for message'),
      '#default_value' => $this->getSetting('message_label'),
    );
    $element['max_length'] = array(
      '#type' => 'number',
      '#title' => t('Max length of textarea'),
      '#default_value' => $this->getSetting('max_length'),
      '#disabled' => FALSE,
      '#min' => 1,
    );
    // TextareaWidget.
    $element['rows'] = array(
      '#type' => 'number',
      '#title' => t('Rows'),
      '#default_value' => $this->getSetting('rows'),
      '#disabled' => TRUE,
      '#min' => 1,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    if (!empty($settings)) {
      foreach ($settings as $key => $value) {
        if (trim($value) === '') {
          continue;
        }
        $summary[] = t(ucfirst($key). ': @' . $key, array('@' . $key => $value));
      }
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * Overrides \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    $elements += ImageWidget::formMultipleElements($items, $form, $form_state);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#access'] = \Drupal::currentUser()->hasPermission('use sji social connect');
    // Providers.
    $current_path = Url::fromRoute('<current>');
    $options = array('empty' => t('- None -'));
    foreach (\Drupal::moduleHandler()->getImplementations('sjisocialconnect_info') as $module) {
      $sjisocialconnect_info = \Drupal::moduleHandler()->invoke($module, 'sjisocialconnect_info');
      $options += isset($sjisocialconnect_info['providers']) ? $sjisocialconnect_info['providers'] : array();
    }
    $element['provider'] = array(
      '#type' => 'select',
      '#options' => $options,
      // '#multiple' => TRUE,
      '#access' => !\Drupal::service('path.matcher')->matchPath($current_path, '/admin/structure/types/manage/*/fields/*'),
      '#default_value' => NULL, // isset($items[$delta]->provider) ? array($items[$delta]->provider) : NULL,
      '#description' => t("This option is explicitly disabled by default regardless of content's state."),
      '#weight' => -51,
    );
    // Message.
    $element['message'] = array(
      '#title' => $this->getSetting('message_label'),
      '#type' => 'textarea',
      '#default_value' => !empty($items[$delta]->message) ? $items[$delta]->message : '[node:title]',
      '#rows' => $this->getSetting('rows'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#attributes' => array('class' => array('text-full')),
      '#weight' => -50,
      '#maxlength' => $this->getSetting('max_length'),
    );
    // Display token browser.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $token_types = array('node');
      $element['token_tree'] = array(
        '#type' => 'container',
        '#dialog' => TRUE,
        '#theme' => 'token_tree', 
        '#token_types' => $token_types,
      );
    }
    else {
      $element['token_tree'] = array(
        '#markup' => '<p>' . t('Enable the <a href="@drupal-token">Token module</a> to view the available token browser.', array('@drupal-token' => 'http://drupal.org/project/token')) . '</p>',
      );
    }

    $element += parent::formElement($items, $delta, $element, $form, $form_state);
    $element += ImageWidget::formElement($items, $delta, $element, $form, $form_state);

    return $element;
  }

  /**
   * Form API callback: Processes a image_image field element.
   *
   * Expands the image_image type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    return ImageWidget::process($element, $form_state, $form);
  }

  /**
   * Validate callback for alt and title field, if the user wants them required.
   *
   * This is separated in a validate function instead of a #required flag to
   * avoid being validated on the process callback.
   */
  public static function validateRequiredFields($element, FormStateInterface $form_state) {
    ImageWidget::validateRequiredFields($element, $form_state);
  }

}
