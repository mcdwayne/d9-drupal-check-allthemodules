<?php

namespace Drupal\email_contact\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\email_contact\Form\ContactForm;

/**
 * Plugin implementation of the 'email_contact_inline' formatter.
 *
 * @FieldFormatter(
 *   id = "email_contact_inline",
 *   label = @Translation("Email contact inline"),
 *   field_types = {
 *     "email",
 *   },
 *   settings = {
 *     "redirection_to" = "front",
 *     "custom_path" = "",
 *     "default_message" = "[current-user:name] sent a message using the
 *     contact form at [current-page:url]."
 *   }
 * )
 */
class EmailContactInlineFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $element['redirection_to'] = array(
      '#title' => t('Redirection after form submit'),
      '#type' => 'radios',
      '#options' => array(
        'front' => t('To the frontpage'),
        'current' => t('To the current page'),
        'custom' => t('To a custom path'),
      ),
      '#default_value' => $this->getSetting('redirection_to'),
      '#required' => TRUE,
    );

    $element['custom_path'] = array(
      '#title' => t('Redirection path'),
      '#type' => 'textfield',
      '#states' => array(
        'visible' => array(
          'input[name="redirection_to"]' => array('value' => 'custom'),
        ),
      ),
      '#default_value' => $this->getSetting('custom_path'),
      '#element_validate' => [[$this, 'validateCustomPath']],
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
      '#type' => 'hidden',
      '#value' => '',
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
      'link_text' => '',
      'redirection_to' => 'front',
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
    $summary[] = t('Displays a contact form for this email.');
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
      try {
        $form = new ContactForm($entity->getEntityTypeId(), $entity->id(), $items->getName(), $this->getSettings());
        $elements[$delta]['form'] = \Drupal::formBuilder()->getForm($form);
      }
      catch (NotFoundHttpException $e) {
        \Drupal::logger('email_contact')->notice('Invalid inline contact form on @entity_type id @id.', ['@entity_type' => $entity->getEntityTypeId(), '@id' => $entity->id()]);
      }
      break;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateCustomPath($element, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    if ('custom' == $settings['redirection_to']) {
      if (empty($element['#value'])) {
        $form_state->setError($element, $this->t('The custom path is required!'));
      }
      if (!UrlHelper::isValid($element['#value'])) {
        $form_state->setError($element, $this->t('The given url is not valid!'));
      }
    }
  }
}
