<?php

namespace Drupal\multiplechoice\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\multiplechoice\Form\MultipleChoiceForm;
use Drupal\multiplechoice\Plugin\MultiplechoiceSettingsTrait;
use Drupal\Core\Form\FormState;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Plugin implementation of the 'multiplechoice' formatter.
 *
 * @FieldFormatter(
 *   id = "multiplechoice",
 *   label = @Translation("Multiple Choice"),
 *   field_types = {
 *     "multiplechoice"
 *   }
 * )
 */
class MultiplechoiceFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use MultiplechoiceSettingsTrait;

  /**
   * The field settings.
   *
   */
  protected $fieldSettings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * Constructs a new LinkFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
//    ksm($plugin_definition);
//    dpm($field_definition->getItemDefinition());
    $this->fieldSettings = $field_definition->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '80',
      'url_only' => '',
      'url_plain' => '',
      'rel' => '',
      'target' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['trim_length'] = array(
      '#type' => 'number',
      '#title' => t('Trim link text length'),
      '#field_suffix' => t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#description' => t('Leave blank to allow unlimited link text lengths.'),
    );
    $elements['url_only'] = array(
      '#type' => 'checkbox',
      '#title' => t('URL only'),
      '#default_value' => $this->getSetting('url_only'),
      '#access' => $this->getPluginId() == 'link',
    );
    $elements['url_plain'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show URL as plain text'),
      '#default_value' => $this->getSetting('url_plain'),
      '#access' => $this->getPluginId() == 'link',
      '#states' => array(
        'visible' => array(
          ':input[name*="url_only"]' => array('checked' => TRUE),
        ),
      ),
    );
    $elements['rel'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => $this->getSetting('rel'),
    );
    $elements['target'] = array(
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    if (!empty($settings['trim_length'])) {
      $summary[] = t('Link text trimmed to @limit characters', array('@limit' => $settings['trim_length']));
    }
    else {
      $summary[] = t('Link text not trimmed');
    }
    if ($this->getPluginId() == 'link' && !empty($settings['url_only'])) {
      if (!empty($settings['url_plain'])) {
        $summary[] = t('Show URL only as plain-text');
      }
      else {
        $summary[] = t('Show URL only');
      }
    }
    if (!empty($settings['rel'])) {
      $summary[] = t('Add rel="@rel"', array('@rel' => $settings['rel']));
    }
    if (!empty($settings['target'])) {
      $summary[] = t('Open link in new window');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $entity = $items->getEntity();
    $account = \Drupal::currentUser();
    $admin = FALSE;
    if (node_node_access($entity, 'update', $account)) {
      $admin = TRUE;
    }

    $settings = $this->_getMultiplechoiceSettings($entity);

    if (!$settings) {
      $settings = $this->fieldSettings;
    }

    if (!$admin && $settings['quiz_open'] > time()) {
      // Quiz is not open yet
      $element['quiz_not_open'] = array(
        '#markup' => t('The quiz is not open yet')
      );
    }
    if (!$admin && $settings['quiz_close'] < time()) {
      // Quiz has closed
      $element['quiz_closed'] = array(
        '#markup' => t('The quiz has now closed')
      );
    }

    $db = \Drupal::database();
    $account = \Drupal::currentUser();
    $form_state = new FormState;
    $total_questions = count($items);
    $element = \Drupal::formBuilder()->getForm('Drupal\multiplechoice\Form\MultipleChoiceForm');

    $element['question_id'] = array(
      '#type' => 'hidden',
      '#value' => $entity->id()
    );

    $element['question_revision_id'] = array(
      '#type' => 'hidden',
      '#value' => $entity->getRevisionId()
    );

    $element['field_name'] = array(
      '#type' => 'hidden',
      '#value' => $items->getName()
    );

    $element['entity_type'] = array(
      '#type' => 'hidden',
      '#value' => $entity->getEntityTypeId()
    );

    $delta = 0;
    $intro = FALSE;
    if (isset($_GET['question']) && is_numeric($_GET['question'])) {
      $delta = $_GET['question'] - 1;
      $element['counter'] = array(
        '#markup' => '<p>' . $this->t('Question @question of @total', array('@question' => $delta + 1,
            '@total' => $total_questions)) . '</p>'
      );
      // Occasionally this is a problem
      if (!is_object($items[$delta])) return $element;

      $value = $items[$delta]->getValue();
      $answers = unserialize($value['answers']);
      $options = array();
      foreach ($answers as $index => $answer) {
        $options[$index] = $answer['answer'];
      }
      $element['multiplechoice_question_' . $delta] = array(
        '#id' => 'multiplechoice-' . $delta,
        '#type' => 'radios',
        '#title' => strip_tags($value['question']),
        '#title_display' => 'above',
        '#options' => $options,
      );
    }
    // If we have not started the quiz yet, then we display the intro page
    else {
      $intro = TRUE;
      $element['intro'] = $this->_introDisplay($settings, $total_questions);

     // We calculate if this user can still take the test
      $attempts = $this->_getAttempts($db, $account, $entity);
      if ($attempts >= $settings['takes']) {
        drupal_set_message('You have taken this course the maximum allowed number of times.');
        if (!$admin) {
          return $element['intro'];
        }

      }
      // If this is admin then we show settings
      $element['settings'] = array(
        '#type' => 'details',
        '#title' => t('Settings')
      );
      $element['settings']['form'] = $this->_settingsForm($settings, $entity);
//      ksm($element['settings']['form']);
    }

    // Process the newly added fields so they acquire all the pre_render functions etc
    $element = \Drupal::formBuilder()->doBuildForm('Drupal\multiplechoice\Form\MultipleChoiceForm', $element,
      $form_state);

    // Change value of button if this is the intro page or last question
    if ($intro) {
      $start = $this->t('Start')->render();
      $element['save']['#value']->__construct($start);
    }
    elseif ($delta + 1 == $total_questions) {
      $finish = $this->t('Finish')->render();
      $element['save']['#value']->__construct($finish);
    }
    return $element;
  }

  /*
   * Intro display for first page
   */
  protected function _introDisplay($settings, $total_questions) {
    $rows = array();

    $rows[] = array(
      'Questions',
      $total_questions
    );
    $rows[] = array(
      'Attempts Allowed',
      $settings['takes']
    );

    $rows[] = array(
      'Open Date',
      $settings['quiz_open']
    );
    $rows[] = array(
      'Close Date',
      $settings['quiz_close']
    );
    $rows[] = array(
      'Pass Rate',
      $settings['pass_rate']
    );
    $rows[] = array(
      'Backwards Navigation',
      $settings['backwards_navigation'] ? 'Yes' : 'No'
    );

    return array(
      '#type' => 'table',
      '#rows' => $rows,
    );
  }

  /*
   *
   */
  protected function _getAttempts($db, $account, $entity) {
    $query = $db->select('multiplechoice_quiz_node_results', 'qnr');
    $query->condition('qnr.uid', $account->id());
    $query->condition('qnr.nid', $entity->id());
    return $query->countQuery()->execute()->fetchField();
  }

  /*
   *
   */
  protected function _settingsForm($settings, $entity) {
    $form = $this->multipleChoiceSettingsForm($settings);

    $form['entity_id'] = array(
      '#type' => 'hidden',
      '#value' => $entity->id()
    );

    $form['revision_id'] = array(
      '#type' => 'hidden',
      '#value' => $entity->getRevisionId()
    );

    $form['submit'] = array(
      '#type' => 'button',
      '#value' => 'save',
      '#attributes' => array(
        'id' => 'multiplechoice-settings-submit'
      ),
      '#attached' => array(
        'library' => array('multiplechoice/multiplechoice.multiplechoice_settings')
      ),
      '#ajax' => array(
         'callback' => array('Drupal\multiplechoice\Controller\MultiplechoiceAjax::saveSettings'),
//        'callback' => array('Drupal\multiplechoice\Plugin\Field\FieldFormatter::settingsFormSubmit'),
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Saving settings...'),
        ),
      ),
    );

    return $form;
  }

  protected function _getMultiplechoiceSettings($entity) {
    return \Drupal::database()->select('multiplechoice_quiz_node_properties', 'qnp')
      ->fields('qnp', array(
        'pass_rate',
        'backwards_navigation',
        'quiz_open',
        'quiz_close',
        'takes'
      ))
      ->condition('qnp.nid', $entity->id())
      ->execute()->fetchAssoc();

  }


}
