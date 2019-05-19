<?php

namespace Drupal\wordfilter\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Component\Utility\Html;
use Drupal\wordfilter\WordfilterItem;

/**
 * Class WordfilterConfigurationForm.
 *
 * @package Drupal\wordfilter\Form
 */
class WordfilterConfigurationForm extends EntityForm {

  /**
   * @return \Drupal\wordfilter\Entity\WordfilterConfigurationInterface
   */
  public function getWordfilterConfiguration() {
    return $this->entity;
  }

  /**
   * Helper function to rebuild the form when necessary.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array &$old_form
   *   The old form build.
   * @return array
   *   The newly built form.
   */
  protected function rebuild(FormStateInterface $form_state, &$old_form) {
    $form_state->setRebuild();
    $form_builder = \Drupal::getContainer()->get('form_builder');
    $form = $form_builder->rebuildForm($this->getFormId(), $form_state, $old_form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form_state->setCached(FALSE);

    $wordfilter_configuration = $this->getWordfilterConfiguration();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wordfilter_configuration->label(),
      '#description' => $this->t("Label for the Wordfilter configuration."),
      '#required' => TRUE,
      '#weight' => 10,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wordfilter_configuration->id(),
      '#machine_name' => [
        'exists' => '\Drupal\wordfilter\Entity\WordfilterConfiguration::load',
      ],
      '#disabled' => !$wordfilter_configuration->isNew(),
      '#weight' => 20,
    ];

    $form['process'] = [
      '#type' => 'fieldset',
      '#title' => t('Filtering process'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => 30,
    ];
    /**
     * @var \Drupal\wordfilter\Plugin\WordfilterProcessManager
     */
    $plugin_manager = \Drupal::service('plugin.manager.wordfilter_process');
    $definitions = $plugin_manager->getDefinitions();
    $available_plugins = [];
    foreach ($definitions as $id => $definition) {
      $available_plugins[$id] = $definition['label'];
    }
    $form['process']['process_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Implementation'),
      '#description' => $this->t('The selected implementation will be used to filter the specified words.'),
      '#options' => $available_plugins,
      '#default_value' => $wordfilter_configuration->get('process_id'),
      '#required' => FALSE,
      '#weight' => 10,
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'wordfilterProcessSettingsAjax'],
        'wrapper' => 'wordfilter-process-settings',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Loading settings...'),
        ],
      ],
    ];
    $this->wordfilterProcessSettings($form, $form_state);

    $items = $wordfilter_configuration->getItems();
    $form['items'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => t('Filtering items'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => 40,
    ];

    foreach ($items as $item) {
      $this->wordfilterItemSettings($form, $form_state, $item);
    }

    $num_items = count($items);
    $form['items']['add_destination'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'wordfilter-item-add-destination'],
      '#weight' => $num_items * 100 - 10,
    ];
    $form['items']['add'] = [
      '#tree' => FALSE,
      '#type' => 'submit',
      '#name' => 'item_add',
      '#value' => t('Add another item'),
      '#weight' => $num_items * 100,
      '#ajax' => [
        'callback' => [$this, 'wordfilterNewItemSettingsAjax'],
        'wrapper' => 'wordfilter-item-add-destination',
        'effect' => 'fade',
        'method' => 'before',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Loading settings...'),
        ],
      ],
      '#submit' => ['::wordfilterNewItemSettingsAjax'],
    ];

    return $form;
  }

  /**
   * Form builder for Wordfilter process settings.
   *
   * @see ::buildForm().
   */
  public function wordfilterProcessSettings(array &$form, FormStateInterface $form_state) {
    $wordfilter_config = $this->getWordfilterConfiguration();
    $process_id = $form_state->getValue('process_id');
    if (!isset($process_id)) {
      $process_id = $wordfilter_config->get('process_id');
    }

    /**
     * @var \Drupal\wordfilter\Plugin\WordfilterProcessManager
     */
    $plugin_manager = \Drupal::service('plugin.manager.wordfilter_process');
    /**
     * @var \Drupal\wordfilter\Plugin\WordfilterProcessInterface
     */
    $plugin = $plugin_manager->createInstance($process_id);

    $settings = $plugin->settingsForm($form, $form_state, $wordfilter_config);
    $settings += [
      '#prefix' => '<div id="wordfilter-process-settings">',
      '#suffix' => '</div>',
    ];

    $form['process']['settings'] = $settings;
    $form['process']['settings']['#weight'] = 20;

    return $form['process']['settings'];
  }

  /**
   * Ajax Callback for Wordfilter process settings.
   *
   * @see ::wordfilterProcessSettings().
   */
  public function wordfilterProcessSettingsAjax(array &$form, FormStateInterface $form_state) {
    $this->wordfilterProcessSettings($form, $form_state);
    $form = $this->rebuild($form_state, $form);
    return $form['process']['settings'];
  }

  /**
   * Form builder for settings of a given Filtering item.
   *
   * @see ::buildForm().
   */
  public function wordfilterItemSettings(array &$form, FormStateInterface $form_state, WordfilterItem $item) {
    $count = count($form_state->getValue('items', []));
    if ($count === 0) {
      $count = $form_state->getValue('wordfilter_item_count', 0);
      $count++;
    }
    $form_state->setValue('wordfilter_item_count', $count);

    $delta = $item->getDelta();
    $id = Html::getUniqueId('edit-items-' . $delta);
    $settings = [
      '#type' => 'fieldset',
      '#title' => t('Item #@num', ['@num' => $count]),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => $delta,
      '#attributes' => ['id' => $id],
    ];
    $settings['delta'] = [
      '#type' => 'value',
      '#value' => $delta,
      '#weight' => 10,
    ];
    $settings['substitute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Substitution text'),
      '#default_value' => $item->getSubstitute(),
      '#description' => $this->t('Any filtered word will be replaced by this substitution text (optional).'),
      '#required' => FALSE,
      '#weight' => 20,
    ];
    $settings['filter_words'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Words to filter'),
      '#default_value' => implode(', ', $item->getFilterWords()),
      '#description' => \Drupal::theme()->render('item_list', [
        'items' => [
          $this->t('Enter a <strong>comma-separated</strong> list of filter words.'),
          $this->t('Example: <em>Somebadword, Another, [placeholder]</em>.'),
        ]
      ]),
      '#required' => FALSE,
      '#weight' => 30,
    ];
    $settings['remove'] = [
      '#type' => 'submit',
      '#name' => 'remove_item_' . $delta,
      '#value' => t('Remove this item'),
      '#ajax' => [
        'callback' => [$this, 'removeWordfilterItemAjax'],
        'wrapper' => $id,
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Removing...'),
        ],
      ],
      '#submit' => ['::removeWordfilterItemAjax'],
      '#weight' => 40,
    ];

    $form['items'][$delta] = $settings;
    return $form['items'][$delta];
  }

  /**
   * Form builder for settings of a new Filtering item.
   *
   * @see ::buildForm().
   */
  public function wordfilterNewItemSettings(array &$form, FormStateInterface $form_state) {
    $new = $this->getWordfilterConfiguration()->newItem();
    $this->wordfilterItemSettings($form, $form_state, $new);

    $form = $this->rebuild($form_state, $form);

    $delta = $new->getDelta();
    return $form['items'][$delta];
  }

  /**
   * Ajax callback for the settings of a new Filtering item.
   *
   * @see ::wordfilterNewItemSettings().
   */
  public function wordfilterNewItemSettingsAjax(array &$form, FormStateInterface $form_state) {
    $settings = $this->wordfilterNewItemSettings($form, $form_state);
    $delta = $settings['delta']['#value'];
    $form = $this->rebuild($form_state, $form);
    return $form['items'][$delta];
  }

  /**
   * Ajax remove callback.
   *
   * @see ::buildForm().
   */
  public function removeWordfilterItemAjax(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $delta = $trigger['#parents'][1];

    $wordfilter_config = $this->getWordfilterConfiguration();
    $items = $wordfilter_config->getItems();
    if (!empty($items[$delta])) {
      $wordfilter_config->removeItem($items[$delta]);
    }
    $form = $this->rebuild($form_state, $form);

    drupal_set_message(t('Item will be removed permanently when configuration is saved.'));
    return StatusMessages::renderMessages(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wordfilter_configuration = $this->getWordfilterConfiguration();

    $items = $wordfilter_configuration->getItems();

    // Put submitted data onto the items. It has automatically been populated on
    // the configuration entity's data, but not the configuration items objects.
    foreach ($wordfilter_configuration->get('items') as $delta => $item) {
      $items[$delta]->setSubstitute($item['substitute']);
      $items[$delta]->setFilterWords($item['filter_words']);
    }

    $num_items = count($items);
    if ($num_items > 1) {
      // Remove multiple empty items.
      foreach ($items as $item) {
        if (empty($item->getSubstitute()) && empty($item->getFilterWords())) {
          $wordfilter_configuration->removeItem($item);
        }
      }
    }

    $status = $wordfilter_configuration->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Wordfilter configuration.', [
          '%label' => $wordfilter_configuration->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Wordfilter configuration.', [
          '%label' => $wordfilter_configuration->label(),
        ]));
    }
    $form_state->setRedirectUrl($wordfilter_configuration->toUrl('collection'));
  }

}
