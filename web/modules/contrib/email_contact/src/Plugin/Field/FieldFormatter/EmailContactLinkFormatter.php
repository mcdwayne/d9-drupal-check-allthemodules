<?php

namespace Drupal\email_contact\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'email_contact_link' formatter.
 *
 * @FieldFormatter(
 *   id = "email_contact_link",
 *   label = @Translation("Email contact link"),
 *   field_types = {
 *     "email",
 *   },
 *   settings = {
 *     "link_text" = "Contact person by email"
 *   }
 * )
 */
class EmailContactLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $element['redirection_to'] = array(
      '#type' => 'hidden',
      '#value' => 'custom',
    );

    $element['custom_path'] = array(
      '#type' => 'hidden',
      '#value' => '',
    );

    $element['include_values'] = array(
      '#title' => t('Display all field values in email body'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('include_values'),
    );

    $element['default_message'] = array(
      '#title' => t('Additional message in email body'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('default_message'),
    );

    $element['link_text'] = array(
      '#title' => t('Link text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings()['link_text'],
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['token_help'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array('node'),
      );
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'link_text' => t('Contact person by email'),
      'redirection_to' => 'custom',
      'custom_path' => '',
      'include_values' => 1,
      'default_message' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Displays a link to a contact form.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $item->getEntity();
      $elements[$delta]['#markup'] = \Drupal::l(
        $this->getSetting('link_text'),
        new Url('email_contact.form', [
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
          'field_name' => $items->getName(),
          'view_mode' => $this->viewMode
        ])
      );
      break;
    }

    return $elements;
  }

}
