<?php

namespace Drupal\image_field_repair\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures file_mdm settings for this site.
 */
class RepairDimensionsForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RepairDimensionsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_field_repair_dimensions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'image_field_repair');

    $form['introduction'] = [
      '#type' => 'markup',
      '#markup' => '<p>This form allows you to repair the dimensions of all image field values.</p>
         <p>To be able to render the <code>width</code> and <code>height</code> attributes of image tags in a performant way, image fields store the image dimensions in the database.
          For whatever reason, specifically including issue <a href="https://www.drupal.org/node/2644468">[#2644468]: Multiple image upload breaks image dimensions</a>,
          these image dimensions may be corrupted, leading to incorrect img tag attributes and consequently page layout problems.</p>
          <p>By starting this action, the dimensions of all stored image field values will be checked against the actual dimensions and where necessary corrected.</p>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do repair.
    $batch = [
      'operations' => [[[$this, 'processBatch'], []]],
      'title' => t('Repairing image fields'),
      'progress_message' => t('Processing (and where necessary repairing) image field values.'),
      'error_message' => t('Error repairing image fields.'),
      'finished' => [$this, 'finishedBatch'],
      'file' => drupal_get_path('module', 'image_field_repair') . '/image_field_repair.inc',
    ];
    batch_set($batch);
  }

  /**
   * Callback that processes the batch to repair image fields.
   *
   * The actual work is done in image_field_repair_process_batch(). That
   * function uses the semantics of a hook_update_N() hook, i.e. accept only the
   * sandbox entry as argument, return the value for $context['finished'] in
   * $sandbox['#finished'] and return a message that you want to be added to
   * $context['results'].
   *
   * If that function is run via this batch, we allow it to set a progress
   * message in $context['message'] via $sandbox['message'].
   *
   * @param array|\ArrayAccess $context
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @see callback_batch_operation()
   */
  public function processBatch(&$context) {
    $result =  image_field_repair_process_batch($context['sandbox']);
    $context['finished'] = $context['sandbox']['#finished'];
    $context['message'] = !empty($context['sandbox']['message']) ? $context['sandbox']['message'] : '';
    if (!empty($result)) {
      if (is_array($result)) {
        $context['results'] = array_merge($context['results'], $result);
      }
      else {
        $context['results'][] = $result;
      }
    }
  }

  /**
   * Callback that displays the results of the repair image fields batch.
   *
   * @param bool $success
   * @param array $results
   * param array $operations
   *
   * @see callback_batch_finished()
   */
  public function finishedBatch($success, $results/*, $operations*/) {
    if ($success) {
      array_walk($results, function ($result) {
        drupal_set_message($result, 'status');
      });
    }
    else {
      // An error occurred.
      $message = t('An error occurred while repairing image fields');
      drupal_set_message($message, 'error');
    }
  }
}
