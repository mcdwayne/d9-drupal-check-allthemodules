<?php

namespace Drupal\block_backup_restore\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class RestoreConfigForm.
 */
class RestoreConfigForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a Recurly settings form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity type manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'block_backup_restore.setting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_restore_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme = NULL) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    $form['block_restore'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Restore Block Layout Configuration by Using JSON File.'),
    ];

    $validators = [
      'file_validate_extensions' => ['json'],
      'file_validate_size' => [file_upload_max_size()],
    ];

    // Hidden validation field.
    $form['block_restore']['theme_name'] = [
      '#type' => 'hidden',
      '#value' => $theme,
    ];
    // File Field for the JSON File.
    $form['block_restore']['json_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('JSON File'),
      '#upload_location' => 'public://block_restore/',
      '#default_value' => [],
      '#description' => [
        '#theme' => 'file_upload_help',
        '#description' => $this->t('The JSON file must include columns in the following order:'),
        '#upload_validators' => $validators,
      ],
      '#upload_validators' => $validators,
    ];

    $form['actions']['submit']['#value'] = $this->t('Restore layout');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    file_save_upload($form['block_restore']['json_file'], $form['block_restore']['json_file']['#upload_validators'], FALSE, 0);
    // Ensure we have the file uploaded.
    if (!$form_state->getValue('json_file')) {
      $form_state->setErrorByName('csv_file', $this->t('File to import not found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('json_file')) {
      $fileId = $form_state->getValue('json_file')[0];
      $fileObject = $this->entityManager->getStorage('file')->load($fileId);
      $fileData = file_get_contents($fileObject->getFileUri());
      if (!empty($fileData)) {
        $json = json_decode($fileData, TRUE);
        foreach ($json as $key => $value) {
          if (!empty($key)) {
            $blockData = $this->entityManager->getStorage('block')->load($key);
            if (!empty($blockData)) {
              $blockData->setRegion($value['region'])
                ->setWeight($value['weight'])
                ->enable()
                ->save();
            }
          }
        }
      }
    }
    drupal_set_message($this->t('Block Restore in the @themeName theme successfully.', ['@themeName' => ucwords($form_state->getValue('theme_name'))]));
    $url = Url::fromRoute('block.admin_display_theme', ['theme' => $form_state->getValue('theme_name')]);
    $form_state->setRedirectUrl($url);
  }

}
