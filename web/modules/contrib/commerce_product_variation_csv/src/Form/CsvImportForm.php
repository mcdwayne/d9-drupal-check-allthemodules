<?php

namespace Drupal\commerce_product_variation_csv\Form;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CsvImportForm extends FormBase {

  /**
   * The number of price list items to process in each batch.
   *
   * @var int
   */
  const BATCH_SIZE = 5;

  /**
   * Constructs a new CsvImportForm object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RouteMatchInterface $route_match, RequestStack $request_stack) {
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_variation_csv_csv_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->routeMatch->getParameter('commerce_product');
    $form['csv'] = [
      '#type' => 'file',
      '#title' => $this->t('Choose a file'),
      '#description' => $this->t('Unsure about the format? Download the <a href=":url">source file</a>.', [
        ':url' => Url::fromRoute('commerce_product_variation_csv.csv_export_form', ['commerce_product' => $product->id()])->toString(),
      ]),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
        'file_validate_size' => [file_upload_max_size()],
      ],
      '#upload_location' => 'temporary://',
    ];

    $form['disable_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable all existing variations for this product prior to import.'),
      '#description' => $this->t('Checking this box will disable all existing variations and allow the CSV data to enable them if they still exist.'),
      '#access' => !empty($product->getVariationIds()),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import variations from CSV'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = Link::createFromRoute($this->t('Back to variations'), 'entity.commerce_product_variation.collection', ['commerce_product' => $product->id()])->toRenderable();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile[] $all_files */
    $all_files = $this->getRequest()->files->get('files', []);
    if (empty($all_files['csv'])) {
      $form_state->setErrorByName('csv', $this->t('No CSV file was provided.'));
    }
    elseif (!$all_files['csv']->isValid()) {
      $form_state->setErrorByName('csv', $this->t('The provided CSV file is invalid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->routeMatch->getParameter('commerce_product');

    $file = file_save_upload('csv', $form['csv']['#upload_validators'], 'temporary://', 0, FILE_EXISTS_RENAME);

    $batch = [
      'title' => $this->t('Importing variations'),
      'progress_message' => '',
      'operations' => [],
      'finished' => [$this, 'finishBatch'],
    ];
    if ($form_state->getValue('disable_existing')) {
      $batch['operations'][] = [
        [get_class($this), 'batchDisableExisting'],
        [$product->id()],
      ];
    }
    $batch['operations'][] = [
      [get_class($this), 'batchProcess'],
      [
        $file->getFileUri(),
        $product->id(),
      ],
    ];
    $batch['operations'][] = [
      [get_class($this), 'batchDeleteUploadedFile'],
      [$file->getFileUri()],
    ];

    batch_set($batch);

    $form_state->setRedirect('entity.commerce_product_variation.collection', ['commerce_product' => $product->id()]);
  }

  /**
   * Disable existing product variations during batch.
   *
   * @param string $product_id
   *   The product ID.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function batchDisableExisting(string $product_id, array &$context) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $variation_storage = $entity_type_manager->getStorage('commerce_product_variation');
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $entity_type_manager->getStorage('commerce_product')->load($product_id);
    $variation_ids = $product->getVariationIds();

    if (empty($context['sandbox'])) {
      $context['sandbox']['disabled_total'] = count($variation_ids);
      $context['sandbox']['disabled_count'] = 0;
      $context['results']['disabled_count'] = 0;
    }

    $total_items = $context['sandbox']['disabled_total'];
    $disabled = &$context['sandbox']['disabled_count'];
    $remaining = $total_items - $disabled;
    $limit = (int) ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;

    if ($total_items === 0 || empty($variation_ids)) {
      $context['finished'] = 1;
    }
    else {
      $variation_ids_to_disable = array_slice($variation_ids, $disabled, $limit);
      $variations_to_disable = $variation_storage->loadMultiple($variation_ids_to_disable);
      array_walk($variations_to_disable, function (ProductVariationInterface $variation) {
        $variation->setUnpublished();
        $variation->save();
      });

      $disabled += $limit;

      $context['message'] = t('Disabling variations @disabled of @total_items', [
        '@disabled' => $disabled,
        '@total_items' => $total_items,
      ]);
      $context['finished'] = $disabled / $total_items;
    }
    // Update the results for finishBatch().
    $context['results']['disabled_count'] = $disabled;
  }

  /**
   * Processes the CSV during batch.
   *
   * @param string $file_uri
   *   The CSV file URI.
   * @param string $product_id
   *   The product ID.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchProcess(string $file_uri, string $product_id, array &$context) {
    $csv_handler = \Drupal::getContainer()->get('commerce_product_variation_csv.csv_handler');
    $csv_importer = \Drupal::getContainer()->get('commerce_product_variation_csv.csv_importer');

    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $entity_type_manager->getStorage('commerce_product')->load($product_id);

    $csv = $csv_importer->prepareCsv($file_uri);

    if (empty($context['sandbox'])) {
      $context['sandbox']['import_total'] = (int) $csv->count();
      $context['sandbox']['import_count'] = 0;
      $context['results']['import_skipped'] = 0;
      $context['results']['import_processed'] = 0;
    }
    // The file is invalid, stop here.
    if (!$csv->valid()) {
      $context['results']['error'] = t('The provided CSV file is invalid.');
      $context['finished'] = 1;
      return;
    }

    $import_total = $context['sandbox']['import_total'];
    $created = &$context['sandbox']['import_count'];
    $remaining = $import_total - $created;
    $limit = ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;

    $variation_type_id = $csv_handler->getProductTypeVariationTypeId($product);
    $field_definitions = $csv_handler->getVariationFieldDefinitions($variation_type_id);
    $columns = $csv_handler->getColumnNames($field_definitions);

    $csv->seek($created + 1);
    for ($i = 0; $i < $limit; $i++) {
      $current = $csv->current();
      try {
        if (count(array_keys($current)) !== count($columns)) {
          throw new \InvalidArgumentException('Mismatched columns');
        }

        $variation = $csv_importer->processCsvRow($variation_type_id, $current, $field_definitions);
        if (!$product->hasVariation($variation)) {
          $product->addVariation($variation);
        }
        $context['results']['import_processed']++;
      }
      catch (\Throwable $e) {
        $context['results']['import_skipped']++;
      }
      finally {
        $created++;

      }
      $csv->next();
    }

    $product->save();

    $context['message'] = t('Importing @created of @import_total variations', [
      '@created' => $created,
      '@import_total' => $import_total,
    ]);
    $context['finished'] = $created / $import_total;
  }

  /**
   * Batch process to delete the uploaded CSV.
   *
   * @param string $file_uri
   *   The CSV file URI.
   * @param array $context
   *   The batch context.
   */
  public static function batchDeleteUploadedFile($file_uri, array &$context) {
    file_unmanaged_delete($file_uri);
    $context['message'] = t('Removing uploaded CSV.');
    $context['finished'] = 1;
  }

  /**
   * Batch finished callback: display batch statistics.
   *
   * @param bool $success
   *   Indicates whether the batch has completed successfully.
   * @param mixed[] $results
   *   The array of results gathered by the batch processing.
   * @param string[] $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, array $results, array $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments: @args', [
        '@operation' => $error_operation[0],
        '@args' => (string) print_r($error_operation[0], TRUE),
      ]));
      return;
    }

    if (!empty($results['error'])) {
      \Drupal::messenger()->addError($results['error_message']);
    }
    else {
      if (!empty($results['import_processed'])) {
        \Drupal::messenger()->addMessage(\Drupal::translation()->formatPlural(
          $results['import_processed'],
          'Imported 1 variation.',
          'Imported @count variations.'
        ));
      }
      if (!empty($results['import_skipped'])) {
        \Drupal::messenger()->addWarning(\Drupal::translation()->formatPlural(
          $results['import_skipped'],
          'Skipped 1 variation during import.',
          'Skipped @count variations during import.'
        ));
      }
    }
  }

}
