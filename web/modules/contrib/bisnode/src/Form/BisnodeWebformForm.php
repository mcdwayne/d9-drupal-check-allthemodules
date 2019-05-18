<?php

namespace Drupal\bisnode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bisnode\BisnodeServiceInterface;

/**
 * Class BisnodeWebformForm.
 */
class BisnodeWebformForm extends FormBase {

  /**
   * Drupal\bisnode\BisnodeServiceInterface definition.
   *
   * @var \Drupal\bisnode\BisnodeServiceInterface
   */
  protected $bisnodeWebapi;
  /**
   * Constructs a new BisnodeTestConnectionForm object.
   */
  public function __construct(BisnodeServiceInterface $bisnode_webapi) {
    $this->bisnodeWebapi = $bisnode_webapi;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bisnode.webapi')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bisnode_webform_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $webform = NULL) {
    $this->webform = $webform;

    $stored_mapping = $webform->getThirdPartySetting('bisnode', 'mapping_fields', []);
    $num_groups = $form_state->get('num_groups');
    if ($num_groups === NULL) {
      if (!empty($stored_mapping)) {
        $num_groups = count($stored_mapping);
      }
      else {
        $num_groups = 1;
      }
      $form_state->set('num_groups', $num_groups);
    }

    $elements = $webform->getElementsInitializedFlattenedAndHasValue('view');
    $fields = [];
    foreach ($elements as $key => $element) {
      $fields[$key] = $this->t("@title (%type)", [
        '@title' => ($element['#admin_title'] ?: $element['#title'] ?: $key),
        '%type' => (isset($element['#type']) ? $element['#type'] : ''),
      ]);
    }

    $form['#tree'] = TRUE;

    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => 'Active Bisnode',
      '#default_value' => $webform->getThirdPartySetting('bisnode', 'active', FALSE),
    ];

    $form['loading_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Loading text'),
      '#default_value' => $webform->getThirdPartySetting('bisnode', 'loading_text', ''),
    ];

    $form['settings_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
      '#prefix' => '<div id="bisnode-settings-container-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_groups; $i++) {

      $form['settings_container'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Settings group @num', ['@num' => $i + 1]),
      ];

      $form['settings_container'][$i]['search_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Search field'),
        '#options' => array_merge(['none' => $this->t('No field')], $fields),
        '#default_value' => isset($stored_mapping[$i]) && isset($stored_mapping[$i]['search_field']) ? $stored_mapping[$i]['search_field'] : 'none',
      ];

      $form['settings_container'][$i]['mapping_fields'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Mapping Fields'),
        '#tree' => TRUE,
      ];

      $mapping = [
          'none' => $this->t('No mapped'),
        ] + $this->bisnodeWebapi::fieldsMapping();

      foreach ($fields as $key => $field) {
        $form['settings_container'][$i]['mapping_fields'][$key] = [
          '#type' => 'select',
          '#title' => $field,
          '#options' => $mapping,
          '#default_value' => isset($stored_mapping[$i]) && isset($stored_mapping[$i]['mapping_fields'][$key]) ? $stored_mapping[$i]['mapping_fields'][$key] : NULL,
        ];
      }
    }

    $form['settings_container']['actions'] = [
      '#type' => 'actions',
    ];
    $form['settings_container']['actions']['add_group'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add group'),
      '#submit' => ['::addGroup'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'bisnode-settings-container-wrapper',
      ],
    ];
    // If there is more than one group, add the remove button.
    if ($num_groups > 1) {
      $form['settings_container']['actions']['remove_group'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove group'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'bisnode-settings-container-wrapper',
        ],
      ];
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $settings_container = $values["settings_container"];
    unset($settings_container["actions"]);
    $webform = $this->webform;

    $webform->setThirdPartySetting('bisnode', 'active', $form_state->getValue('active'));
    $webform->setThirdPartySetting('bisnode', 'loading_text', $form_state->getValue('loading_text'));
    $webform->setThirdPartySetting('bisnode', 'mapping_fields', $settings_container);

    $webform->save();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings_container'];
  }

  /**
   * Submit handler for the "add group" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addGroup(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->get('num_groups');
    $add_button = $groups + 1;
    $form_state->set('num_groups', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove group" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->get('num_groups');
    if ($groups > 1) {
      $remove_button = $groups - 1;
      $form_state->set('num_groups', $remove_button);
    }
    $form_state->setRebuild();
  }

}
