<?php

namespace Drupal\file_downloader\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\file_downloader\DownloadOptionPluginInterface;
use Drupal\file_downloader\DownloadOptionPluginManager;
use Drupal\file_downloader\Entity\DownloadOptionConfig;
use Drupal\file_downloader\Entity\DownloadOptionConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DownloadOptionConfigForm.
 */
class DownloadOptionConfigForm extends EntityForm {
  /**
   * The download option provider storage.
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
   * DownloadOptionConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service to handle entities.
   * @param \Drupal\file_downloader\DownloadOptionPluginManager $downloadOptionPluginManager
   *   Download option plugin manager to handle the download option plugins.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   Plugin form factory manager to handle the form generation of the plugins.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, DownloadOptionPluginManager $downloadOptionPluginManager, PluginFormFactoryInterface $pluginFormFactory) {
    $this->storage = $entityTypeManager->getStorage('download_option_config');
    $this->pluginFormFactory = $pluginFormFactory;
    $this->pluginManager = $downloadOptionPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.download_option'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface $downloadOptionConfig */
    $downloadOptionConfig = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $downloadOptionConfig->label(),
      '#description' => $this->t("Label for the Download option config."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $downloadOptionConfig->id(),
      '#machine_name' => [
        'exists' => '\Drupal\file_downloader\Entity\DownloadOptionConfig::load',
      ],
      '#disabled' => !$downloadOptionConfig->isNew(),
    ];

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Separate extensions with a space or comma and do not include the leading dot, if empty all extensions are allowed.'),
      '#default_value' => $downloadOptionConfig->getExtensions(),
      '#element_validate' => [[static::class, 'validateExtensions']],
    ];

    if ($downloadOptionConfig->isNew()) {
      $form['plugin_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Plugin'),
        '#options' => $this->pluginManager->getOptions(),
        '#default_value' => $downloadOptionConfig->getPlugin() ? $downloadOptionConfig->getPlugin()
          ->getPluginId() : NULL,
        '#attributes' => ['class' => ['field-plugin-type']],
        '#required' => TRUE,
        '#disabled' => !$downloadOptionConfig->isNew(),
      ];
    }
    else{
      $form['settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Plugin specific settings'),
      ];

      $form['#tree'] = TRUE;
      $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);

      if ($downloadOptionConfig->getPlugin()) {
        $form['settings'] = $this->getPluginForm($downloadOptionConfig->getPlugin())
          ->buildConfigurationForm($form['settings'], $subform_state);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state){
    $downloadOptionConfig = $this->entity;
    $status = $downloadOptionConfig->save();

    drupal_set_message($this->getSaveMessage($downloadOptionConfig, $status));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface $downloadOptionConfig */
    $downloadOptionConfig = $this->entity;

    if (!$downloadOptionConfig->isNew()) {
      $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);

      $downloadOptionPlugin = $downloadOptionConfig->getPlugin();
      $this->getPluginForm($downloadOptionPlugin)
        ->submitConfigurationForm($form, $sub_form_state);

      $downloadOptionConfig->set('settings', $downloadOptionPlugin->getConfiguration());
    }
  }

  /**
   * @param \Drupal\file_downloader\Entity\DownloadOptionConfigInterface $downloadOptionConfig
   * @param $status
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private function getSaveMessage($downloadOptionConfig, $status) {
    if ($status == SAVED_NEW) {
      return $this->t('Created the %label Download option config.', [
        '%label' => $downloadOptionConfig->label(),
      ]);
    }

    return $this->t('Saved the %label Download option config.', [
      '%label' => $downloadOptionConfig->label(),
    ]);
  }

  /**
   * Validates a list of file extensions.
   *
   * @See \Drupal\file\Plugin\Field\FieldType\FileItem::validateExtensions
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (empty($element['#value'])) {
      return;
    }

    $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
    $extensions = array_filter(explode(' ', $extensions));
    $extensions = implode(' ', array_unique($extensions));
    if (!preg_match('/^([a-z0-9]+([.][a-z0-9])* ?)+$/', $extensions)) {
      $form_state->setError($element, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
    }

    $form_state->setValueForElement($element, $extensions);

  }

  /**
   * Retrieves the plugin form for a given block and operation.
   *
   * @param \Drupal\file_downloader\DownloadOptionPluginInterface $downloadOptionPlugin
   *   The download option plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the download option.
   */
  protected function getPluginForm(DownloadOptionPluginInterface $downloadOptionPlugin) {
    if ($downloadOptionPlugin instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($downloadOptionPlugin, 'configure');
    }
    return $downloadOptionPlugin;
  }

}
