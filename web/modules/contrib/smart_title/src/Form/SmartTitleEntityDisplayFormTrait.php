<?php

namespace Drupal\smart_title\Form;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit form for the EntityViewDisplay entity type.
 */
trait SmartTitleEntityDisplayFormTrait {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $smart_title_config = $this->configFactory()->get('smart_title.settings')->get('smart_title');
    $target_entity_type_id = $this->entity->getTargetEntityTypeId();
    $target_entity_bundle = $this->entity->getTargetBundle();

    if ($smart_title_config && in_array("$target_entity_type_id:$target_entity_bundle", $smart_title_config)) {
      $form['smart_title'] = [
        '#type' => 'details',
        '#title' => t('Smart Title'),
        '#group' => 'additional_settings',
      ];

      $form['smart_title']['smart_title__enabled'] = [
        '#type' => 'checkbox',
        '#title' => t('Make entity title configurable'),
        '#description' => t('Check this box if you would like a configurable entity label for this view mode.'),
        '#default_value' => $this->entity->getThirdPartySetting('smart_title', 'enabled', FALSE),
      ];

      $form['#entity_builders']['smart_title'] = '::smartTitleBuilder';
    }

    if (!$this->entity->getThirdPartySetting('smart_title', 'enabled', FALSE)) {
      // Hide the extra field.
      unset($form['#extra'][array_search('smart_title', $form['#extra'])]);
      unset($form['fields']['smart_title']);

      // Opt-out.
      return $form;
    }

    $provide_form = !empty($form_state->getStorage()['plugin_settings_edit']) && $form_state->getStorage()['plugin_settings_edit'] === 'smart_title';
    $smart_title = &$form['fields']['smart_title'];
    $smart_title['plugin']['settings_edit_form'] = [];

    if ($smart_title['region']['#default_value'] !== 'hidden') {
      // Extra field is set to be visible.
      // Getting our settings: the active config, or if we have temporary
      // then those.
      $smart_title_settings = $form_state->get('smart_title_tempvalues') ?:
        $this->entity->getThirdPartySetting('smart_title', 'settings', _smart_title_defaults($this->entity->getTargetEntityTypeId(), TRUE));

      if ($provide_form) {
        unset($smart_title['settings_summary']);
        unset($smart_title['settings_edit']);
        $smart_title['#attributes']['class'][] = 'field-plugin-settings-editing';
        $smart_title['plugin']['#cell_attributes'] = ['colspan' => 3];
        $smart_title['plugin']['settings_edit_form'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
          '#parents' => [
            'fields',
            'smart_title',
            'settings_edit_form',
          ],
          'label' => [
            '#markup' => $this->t('<strong>Smart Title</strong> settings'),
          ],
          'settings' => [
            'smart_title__tag' => [
              '#type' => 'select',
              '#title' => _smart_title_defaults('', NULL, 'smart_title__tag')['label'],
              '#options' => _smart_title_tag_options(),
              '#default_value' => $smart_title_settings['smart_title__tag'],
              '#empty_value' => '',
            ],
            'smart_title__classes' => [
              '#type' => 'textfield',
              '#title' => _smart_title_defaults('', NULL, 'smart_title__classes')['label'],
              '#default_value' => implode(' ', $smart_title_settings['smart_title__classes']),
              '#states' => [
                'invisible' => [
                  ':input[name="fields[smart_title][settings_edit_form][settings][smart_title__tag]"]' => [
                    'value' => '',
                  ],
                ],
              ],
            ],
            'smart_title__link' => [
              '#type' => 'checkbox',
              '#title' => _smart_title_defaults('', NULL, 'smart_title__link')['label'],
              '#default_value' => $smart_title_settings['smart_title__link'],
            ],
          ],
          'third_party_settings' => [],
          'actions' => [
            '#type' => 'actions',
            'save_settings' => [
              '#submit' => [
                '::multistepSubmit',
              ],
              '#ajax' => [
                'callback' => '::multistepAjax',
                'wrapper' => 'field-display-overview-wrapper',
                'effect' => 'fade',
              ],
              '#field_name' => 'smart_title',
              '#type' => 'submit',
              '#button_type' => 'primary',
              '#name' => 'smart_title_plugin_settings_update',
              '#value' => $this->t('Update'),
              '#op' => 'update',
            ],
            'cancel_settings' => [
              '#submit' => ['::multistepSubmit'],
              '#ajax' => [
                'callback' => '::multistepAjax',
                'wrapper' => 'field-display-overview-wrapper',
                'effect' => 'fade',
              ],
              '#field_name' => 'smart_title',
              '#type' => 'submit',
              '#name' => 'smart_title_plugin_settings_cancel',
              '#value' => $this->t('Cancel'),
              '#op' => 'cancel',
              '#limit_validation_errors' => [
                [
                  'fields',
                  'smart_title',
                  'type',
                ],
              ],
            ],
          ],
        ];
      }

      if (!$provide_form) {
        $summary = [];
        foreach ($smart_title_settings as $key => $value) {
          if ($key === 'smart_title__link') {
            if ((bool) $value) {
              $summary[] = _smart_title_defaults('', NULL, 'smart_title__link')['label'];
            }
            continue;
          }

          if ($key === 'smart_title__classes') {
            $value = empty($smart_title_settings['smart_title__tag']) ? FALSE : implode(', ', $value);
          }

          if ((bool) $value) {
            $summary[] = _smart_title_defaults('', NULL, $key)['label'] . ': ' . $value;
          }
        }

        $smart_title['settings_summary'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
          '#context' => [
            'summary' => $summary,
          ],
          '#cell_attributes' => ['class' => ['field-plugin-summary-cell']],
        ];

        $smart_title['settings_edit'] = [
          '#submit' => ['::multistepSubmit'],
          '#ajax' => [
            'callback' => '::multistepAjax',
            'wrapper' => 'field-display-overview-wrapper',
            'effect' => 'fade',
          ],
          '#field_name' => 'smart_title',
          '#type' => 'image_button',
          '#name' => 'smart_title_settings_edit',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => [
            'class' => ['field-plugin-settings-edit'],
            'alt' => $this->t('Edit'),
          ],
          '#op' => 'edit',
          '#limit_validation_errors' => [['fields', 'smart_title', 'type']],
          '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
          '#suffix' => '</div>',
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Check that Smart Title is/should be enabled.
    if ((bool) $form_state->getValue('smart_title__enabled')) {
      $settings_to_save = (bool) $form_state->get('smart_title_tempvalues') ?
        $form_state->get('smart_title_tempvalues') :
        $this->entity->getThirdPartySetting('smart_title', 'settings', []);
      $field_values = $form_state->getValue('fields', ['smart_title' => []]);

      // If format settings form was opened when the view display form was asked
      // to save its config, we want to save values from that format settings
      // subform.
      if (!empty($field_values['smart_title']['settings_edit_form'])) {
        $settings_to_save = (bool) $field_values['smart_title']['settings_edit_form']['settings'] ?
          $field_values['smart_title']['settings_edit_form']['settings'] : [];
        $settings_to_save['smart_title__classes'] = array_values(array_filter(explode(' ', $settings_to_save['smart_title__classes'])));
      }

      // If field is hidden, remove our settings.
      if (!empty($field_values['smart_title']['region']) && $field_values['smart_title']['region'] === 'hidden') {
        $this->entity->unSetThirdPartySetting('smart_title', 'settings');
      }
      else {
        $settings_to_save += _smart_title_defaults($this->entity->getTargetEntityTypeId(), TRUE);
        // Save the (possibly new) config.
        $this->entity->setThirdPartySetting('smart_title', 'settings', $settings_to_save);
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function multistepSubmit($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $field_values = $form_state->getValue('fields', [
      'smart_title' => ['settings_edit_form' => []],
    ]);

    if (
      $trigger['#op'] === 'update' &&
      !empty($field_values['smart_title']['settings_edit_form'])
    ) {
      $settings_to_save = !empty($field_values['smart_title']['settings_edit_form']['settings']) ?
        $field_values['smart_title']['settings_edit_form']['settings'] : [];

      if (isset($settings_to_save['smart_title__classes'])) {
        $settings_to_save['smart_title__classes'] = array_values(array_filter(explode(' ', $settings_to_save['smart_title__classes'])));
      }
      $form_state->set('smart_title_tempvalues', $settings_to_save);
    }

    parent::multistepSubmit($form, $form_state);
  }

  /**
   * Entity view display's entity builder of Smart Title.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function smartTitleBuilder($entity_type_id, EntityViewDisplayInterface $entity_view_display, array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('smart_title__enabled')) {
      $entity_view_display->setThirdPartySetting('smart_title', 'enabled', TRUE);
    }
    else {
      $entity_view_display
        ->setThirdPartySetting('smart_title', 'enabled', FALSE)
        ->unsetThirdPartySetting('smart_title', 'settings')
        ->removeComponent('smart_title');
    }
  }

}
