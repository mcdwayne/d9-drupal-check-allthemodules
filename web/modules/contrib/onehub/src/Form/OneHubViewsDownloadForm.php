<?php

namespace Drupal\onehub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\onehub\OneHubFormIdService;
use Drupal\onehub\OneHubApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Downloads form for OneHub Views field.
 */
class OneHubViewsDownloadForm extends FormBase {

  /**
   * The form id.
   *
   * @var string
   */
  protected $formid;

  /**
   * The OneHub file for downloading.
   *
   * @var array
   */
  protected $file = [];

  /**
   * Constructs a new DevelGenerateForm object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(OneHubFormIdService $onehub) {
    $this->formid = $onehub;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('onehub.formid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->formid->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $value = NULL) {

    // Grab the files from the DB.
    $db = \Drupal::database();
    $results = $db->select('onehub', 'o')
      ->fields('o')
      ->condition('oid', $value)
      ->execute()
      ->fetchAll();

    // Render each file.
    foreach ($results as $delta => $result) {

      $form['download-' . $result->oid] = [
        '#type' => 'submit',
        '#name' => $result->oid,
        '#value' => t($result->filename),
        '#attributes' => ['class' => ['onehub-button']],
      ];

      $this->file[$result->oid] = [
        'fid' => $result->oid,
        'filename' => $result->filename,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();

    // Parse out the delta form the triggering element.
    if (strpos($element['#id'], 'edit-download') !== FALSE) {
      $delta = str_replace('edit-download-', '', $element['#id']);
      $filename = $this->file[$delta]['filename'];
      $fid = $this->file[$delta]['fid'];
      $file = (new OneHubApi())->getFile($filename, $fid);
    }
  }
}
