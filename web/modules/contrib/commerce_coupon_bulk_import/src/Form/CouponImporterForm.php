<?php

namespace Drupal\commerce_coupon_bulk_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for the Coupon Importer Batch tool.
 */
class CouponImporterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_coupon_bulk_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['coupon_file'] = [
      '#title' => $this->t('Upload the CSV file'),
      '#type' => 'file',
      '#description' => $this->t('CSV format should be "*coupon code*, *number of uses or leave blank*"'),
    ];

    $form['promotion_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Promotion to add coupons'),
      '#options' => $this->getListOfPromotions(),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $validators = ['file_validate_extensions' => ['csv']];
    $file = file_save_upload('coupon_file', $validators, FALSE, 0);
    if (!$file) {
      return;
    }

    $fileHandler = fopen($file->getFileUri(), "r");
    if (!$fileHandler) {
      drupal_set_message($this->t("There was an issue opening your file."), 'error');
      return;
    }

    $promotionId = $form_state->getValue('promotion_id');
    $operations = [];

    while (($csv_row = fgetcsv($fileHandler)) !== FALSE) {

      $couponCode = $csv_row[0];
      $numberOfUses = (empty($csv_row[1]) ? NULL : $csv_row[1]);

      $coupon_operation_function = '\Drupal\commerce_coupon_bulk_import\ProcessCouponBatch::processCoupon';
      $operations[] = [$coupon_operation_function, [$couponCode, $promotionId, $numberOfUses]];
    }
    fclose($fileHandler);

    $batch = [
      'title' => t('Creating coupons...'),
      'operations' => $operations,
      'finished' => '\Drupal\commerce_coupon_bulk_import\ProcessCouponBatch::processCouponsFinishedCallback',
    ];
    batch_set($batch);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $all_files = $this->getRequest()->files->get('files', []);
    if (!empty($all_files['coupon_file'])) {
      $file_upload = $all_files['coupon_file'];
      if ($file_upload->isValid()) {
        $form_state->setValue('coupon_file', $file_upload->getRealPath());
        return;
      }
    }

    $form_state->setErrorByName('coupon_file', $this->t('The file could not be uploaded.'));
  }

  /**
   * Get list of promotion ids.
   *
   * @return array
   *   Returns a list of promotions.
   */
  public function getListOfPromotions() {
    $entityQuery = \Drupal::entityQuery('commerce_promotion');
    $promotion_ids = $entityQuery->execute();
    $promotions = \Drupal::entityTypeManager()->getStorage('commerce_promotion')->loadMultiple($promotion_ids);

    $promotions_list = [];
    foreach ($promotions as $promotion_id => $promotion) {
      $promotions_list[$promotion_id] = $promotion->getName();
    }
    return $promotions_list;
  }

}
