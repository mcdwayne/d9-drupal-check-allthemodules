<?php

namespace Drupal\icons\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;
use Drupal\icons\Entity\IconSetInterface;
use Drupal\icons\IconLibraryPluginInterface;
use Drupal\icons\IconLibraryPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IconSetForm.
 *
 * @package Drupal\icons\Form
 */
class IconSetForm extends EntityForm {

  /**
   * The icon set entity.
   *
   * @var \Drupal\icons\Entity\IconSetInterface
   */
  protected $entity;

  /**
   * The icon provider storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The widget or formatter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerBase
   */
  protected $pluginManager;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * IconProviderForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service to handle entities.
   * @param \Drupal\icons\IconLibraryPluginManager $icon_library_plugin_manager
   *   Icon library plugin manager to handle the icon library plugins.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   Plugin form factory manager to handle the form generation of the plugins.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, IconLibraryPluginManager $icon_library_plugin_manager, PluginFormFactoryInterface $plugin_form_manager) {
    $this->storage = $entity_type_manager->getStorage('icon_set');
    $this->pluginFormFactory = $plugin_form_manager;
    $this->pluginManager = $icon_library_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.icon_library'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var IconSetInterface $icon_set */
    $icon_set = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $icon_set->label(),
      '#description' => $this->t("Label for the Icon Set."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $icon_set->id(),
      '#machine_name' => [
        'exists' => '\Drupal\icons\Entity\IconSet::load',
      ],
      '#disabled' => !$icon_set->isNew(),
    ];

    if ($icon_set->isNew()) {
      $form['plugin'] = [
        '#type' => 'select',
        '#title' => $this->t('Plugin'),
        '#options' => $this->pluginManager->getOptions(),
        '#default_value' => $icon_set->getPlugin() ? $icon_set->getPlugin()
          ->getPluginId() : NULL,
        '#attributes' => ['class' => ['field-plugin-type']],
        '#required' => TRUE,
        /**'#ajax' => [
         * 'callback' => [$this, 'changePluginAjax'],
         * 'wrapper' => 'settings-wrapper',
         * ],**/
      ];
    }
    else {
      $plugin = $icon_set->getPlugin() ? $icon_set->getPlugin() : NULL;

      $form['#tree'] = TRUE;
      $form['settings'] = [
        '#type' => 'fieldset',
        '#prefix' => '<div id="settings-wrapper">',
        '#suffix' => '</div>',
      ];

      $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);

      if ($plugin) {
        $form['settings'] = $this->getPluginForm($icon_set->getPlugin())
          ->buildConfigurationForm($form['settings'], $subform_state);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $icon_set = $this->entity;
    $status = $icon_set->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Icon Set.', [
          '%label' => $icon_set->label(),
        ]));

        $url = Url::fromRoute('entity.icon_set.edit_form', array('icon_set' => $icon_set->id()));
        $form_state->setRedirectUrl($url);
        break;

      default:
        drupal_set_message($this->t('Saved the %label Icon Set.', [
          '%label' => $icon_set->label(),
        ]));
        $form_state->setRedirectUrl($icon_set->urlInfo('collection'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // The icon set Entity form puts all icon library plugin form elements in
    // the settings form element, so just pass that to the icon set for
    // validation.
    if (!$this->entity->isNew()) {
      $this->getPluginForm($this->entity->getPlugin())
        ->validateConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity = $this->entity;
    $entity_is_new = $entity->isNew();
    // The icon set Entity form puts all icon library plugin form elements in
    // the settings form element, so just pass that to the icon set for
    // submission.
    if (!$entity_is_new) {
      $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);

      // Call the plugin submit handler.
      $icon_set_plugin = $entity->getPlugin();
      $this->getPluginForm($icon_set_plugin)
        ->submitConfigurationForm($form, $sub_form_state);

      $entity->set('settings', $icon_set_plugin->getConfiguration());
    }

    drupal_set_message($this->t('The icon set configuration has been saved.'));
  }

  /**
   * Retrieves the plugin form for a given icon set and operation.
   *
   * @param \Drupal\icons\IconLibraryPluginInterface $icon_library
   *   The icon library plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the icon library.
   */
  protected function getPluginForm(IconLibraryPluginInterface $icon_library) {
    if ($icon_library instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($icon_library, 'configure');
    }
    return $icon_library;
  }

  /**
   * Generates a unique machine name for a icon set.
   *
   * @param \Drupal\icons\Entity\IconSetInterface $iconSet
   *   The icon set entity.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName(IconSetInterface $iconSet) {
    $suggestion = $iconSet->getPlugin()->getMachineNameSuggestion();

    // Get all the icon sets which starts with the suggested machine name.
    $query = $this->storage->getQuery();
    $query->condition('id', $suggestion, 'CONTAINS');
    $icon_set_ids = $query->execute();

    $icon_set_ids = array_map(function ($icon_set_id) {
      $parts = explode('.', $icon_set_id);
      return end($parts);
    }, $icon_set_ids);

    // Iterate through potential IDs until we get a new one. E.g.
    // 'plugin', 'plugin_2', 'plugin_3', etc.
    $count = 1;
    $machine_default = $suggestion;
    while (in_array($machine_default, $icon_set_ids)) {
      $machine_default = $suggestion . '_' . ++$count;
    }
    return $machine_default;
  }

}
