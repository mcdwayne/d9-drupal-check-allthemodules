<?php

namespace Drupal\block_in_form\Form;

use Drupal\block_in_form\BlockInFormCommon;
use Drupal\block_in_form\BlockInFormUi;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_group\FieldgroupUi;

/**
 * Provides a form for adding a fieldgroup to a bundle.
 */
class BlockInFormAddForm extends FormBase {

  use BlockInFormCommon;

  /**
   * The prefix for groups.
   *
   * @var string
   */
  const BLOCK_PREFIX = 'block_';

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The context for the group.
   *
   * @var string
   */
  protected $context;

  /**
   * The mode for the group.
   *
   * @var string
   */
  protected $mode;

  /**
   * Current step of the form.
   *
   * @var string
   */
  protected $currentStep;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_in_form_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $this->mode = \Drupal::request()->get('form_mode_name');
    }
    else {
      $this->mode = \Drupal::request()->get('view_mode_name');
    }

    if (empty($this->mode)) {
      $this->mode = 'default';
    }

    if (!$form_state->get('context')) {
      $form_state->set('context', $context);
    }
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }
    if (!$form_state->get('step')) {
      $form_state->set('step', 'formatter');
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->context = $form_state->get('context');
    $this->currentStep = $form_state->get('step');

    if ($this->currentStep == 'formatter') {
      $this->buildFormatterSelectionForm($form, $form_state);
    }
    else {
      $this->buildConfigurationForm($form, $form_state);
    }

    return $form;

  }

  /**
   * Build the formatter selection step.
   */
  protected function buildFormatterSelectionForm(array &$form, FormStateInterface $form_state) {

    // Gather group formatters.
    //$form_state->get('context')
    $form['add'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('form--inline', 'clearfix')),
    );

//    $form['block_title'] = [
//      '#theme_wrappers' => [
//        'container' => [
//          '#attributes' => ['class' => 'region-title__action'],
//        ]
//      ],
//      '#type' => 'link',
//      '#title' => $this->t('Place block <span class="visually-hidden">in the %form</span>', ['%region' => $form_state->get('entity_type_id')]),
//      '#url' => Url::fromRoute('block_in_form.block_admin_library', ['entity_type_id' => $form_state->get('entity_type_id')]),
//      '#wrapper_attributes' => [
//        'colspan' => 5,
//      ],
//      '#attributes' => [
//        'class' => ['use-ajax', 'button', 'button--small'],
//        'data-dialog-type' => 'modal',
//        'data-dialog-options' => Json::encode([
//          'width' => 700,
//        ]),
//      ],
//    ];

    $block_options = $this->getBlockDefinitions();

    $form['add']['block_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a new block'),
      '#options' => $block_options,
      '#empty_option' => $this->t('- Select a block -'),
      '#required' => TRUE,
    ];

    // Field label and field_name.
    $form['new_block_wrapper'] = array(
      '#type' => 'container',
      '#states' => array(
        '!visible' => array(
          ':input[name="block_id"]' => array('value' => ''),
        ),
      ),
    );
    $form['new_block_wrapper']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
      '#required' => TRUE,
    );

    $form['new_block_wrapper']['block_name'] = array(
      '#type' => 'machine_name',
      '#size' => 15,
      // This field should stay LTR even for RTL languages.
      '#field_prefix' => '<span dir="ltr">' . self::BLOCK_PREFIX,
      '#field_suffix' => '</span>&lrm;',
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen(self::BLOCK_PREFIX),
      '#machine_name' => array(
        'source' => array('new_block_wrapper', 'label'),
        'exists' => array($this, 'blockNameExists'),
      ),
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#validate' => array(
        array($this, 'validateFormatterSelection')
      ),
    );

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
  }

  /**
   * Build the formatter configuration form.
   */
  protected function buildConfigurationForm(array &$form, FormStateInterface $form_state) {

    // Create a block entity.
    $plugin_id = $form_state->get('block_id');
    $entity_type_id = $form_state->get('entity_type_id');
    $bundle = $form_state->get('bundle');
    $entity = \Drupal::entityManager()->getStorage('block')
      ->create(
        [
          'plugin' => $plugin_id,
          'entity_type_id' => $entity_type_id,
          'bundle' => $bundle
        ]
      );

    $settings_form = [];
    $subform_state = SubformState::createForSubform($settings_form, $form, $form_state);
    $settings_form = $this->getPluginForm($entity->getPlugin())->buildConfigurationForm($settings_form, $subform_state);

    $form['block_settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['block_settings'] += $settings_form;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add block'),
      '#button_type' => 'primary',
    );
  }

  /**
   * Retrieves the plugin form for a given block and operation.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the block.
   */
  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      $plugin_form_factory = \Drupal::service('plugin_form.factory');
      return $plugin_form_factory->createInstance($block, 'configure');
    }
    return $block;
  }

  /**
   * Validate the formatter selection step.
   */
  public function validateFormatterSelection(array &$form, FormStateInterface $form_state) {

    $block_name = self::BLOCK_PREFIX . $form_state->getValue('block_name');

    // Add the prefix.
    $form_state->setValueForElement($form['new_block_wrapper']['block_name'], $block_name);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->get('step') == 'formatter') {
      $form_state->set('step', 'configuration');
      $form_state->set('block_label', $form_state->getValue('label'));
      $form_state->set('block_name', $form_state->getValue('block_name'));
      $form_state->set('block_id', $form_state->getValue('block_id'));
      $form_state->setRebuild();
    }
    else {
      $new_block_in_form = (object) [
        'block_name' => $form_state->get('block_name'),
        'label' => $form_state->get('block_label'),
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'mode' => $this->mode,
        'context' => $this->context,
        'children' =>[],
        'parent_name' => '',
        'weight' => 20,
        'plugin_id' => $form_state->get('block_id'),
        'block_settings' => $form_state->getValue('block_settings')
      ];

      $this->blockInFormSave($new_block_in_form);

      // Store new block information for any additional submit handlers.
      drupal_set_message(t('New block %label successfully added.', array('%label' => $form_state->get('block_label'))));

      $form_state->setRedirectUrl(BlockInFormUi::getFieldUiRoute($new_block_in_form));
      \Drupal::cache()->invalidate('block_in_form');
    }
  }

  /**
   * @param $block_id
   * @param $context
   * @return array
   */
  protected function blocSettings($block_id, $context) {
    //$manager = Drupal::service('plugin.manager.field_group.formatters');
    //return $manager->getDefaultSettings($format_type, $context);

    return [];
  }

  /**
   * Checks if a group machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'group_name' element.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the group machine name is taken.
   */
  public function blockNameExists($value, $element, FormStateInterface $form_state) {

    // Add the prefix.
    $block_name = self::BLOCK_PREFIX . $value;
    $entity_type = $form_state->get('entity_type_id');
    $bundle = $form_state->get('bundle');
    $context = $form_state->get('context');
    $mode = $form_state->get('mode');

    return field_group_exists($block_name, $entity_type, $bundle, $context, $mode);
  }

  /**
   * @return mixed
   */
  private function getBlockDefinitions($context = NULL) {
    $blockManager = \Drupal::service('plugin.manager.block');
    if (!$context) {
      $context = \Drupal::service('context.repository')->getAvailableContexts();
    }

    $definitions = $blockManager->getDefinitionsForContexts($context);
    $definitions = $blockManager->getSortedDefinitions($definitions);

    // Get blocks definition
    $blocks = [];
    foreach ($definitions as $key => $block) {
      $blocks[$key] = $block['admin_label'];
    }

    return $blocks;
  }
}
