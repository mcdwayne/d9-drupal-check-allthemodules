<?php

namespace Drupal\missyou\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MissyouSettings.
 */
class MissyouSettings extends ConfigFormBase {

  /**
   * Protected variable $entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Protected variable $fileUsage.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, FileUsageInterface $file_usage) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage         = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'missyou_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config                    = $this->config('missyou.settings');
    $form['missyou_tab_title'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Tab title'),
      '#description'   => $this->t("Enter the title for the browser tab<br>If empty, default title ('I Miss you !') will be shown"),
      '#maxlength'     => 64,
      '#size'          => 64,
      '#required'      => TRUE,
      '#default_value' => $config->get('missyou_tab_title'),
    ];
    $form['missyou_favicon']   = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('Favicon'),
      '#description'       => $this->t("Upload the image file for the favicon in the browser tab<br>If empty, current website's favicon will be used"),
      '#upload_location'   => 'public://missyou/',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
      ],
      '#default_value'     => $config->get('missyou_favicon'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('missyou.settings')
      ->set('missyou_tab_title', $form_state->getValue('missyou_tab_title'))
      ->set('missyou_favicon', $form_state->getValue('missyou_favicon'))
      ->save();

    // First we just grab the file ID for the favicon we uploaded, if any.
    $icon_field = $form_state->getValue('missyou_favicon');
    $file_id    = empty($icon_field) ? FALSE : reset($icon_field);

    if (!empty($file_id)) {
      // Make this a permanent file so that cron doesn't delete it later.
      $file         = $this->entityTypeManager->getStorage('file')
        ->load($file_id);
      $file->status = FILE_STATUS_PERMANENT;
      $file->save();
      $this->fileUsage->add($file, 'missyou', 'file', $file_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'missyou.settings',
    ];
  }

}
