<?php

namespace Drupal\ji_commerce_taxes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\ji_quickbooks\JIQuickBooksService;
use Drupal\ji_quickbooks\JIQuickBooksSupport;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Utility\Html;

/**
 * Defines a route controller.
 */
class JICommerceTaxesController extends ControllerBase {

  /**
   * Handler for the QuickBooks Agency name field.
   */
  public function agencyNameAutocomplete(Request $request) {
    $results = [];
    $options = JIQuickBooksSupport::taxAgenciesCache();

    $string = $request->query->get('q');

    // When user selects with mouse, tell code it's okay to
    // return everything available.
    if ($string !== '' && $string !== ' ') {
      foreach ($options as $key => $agency_name) {
        $agency = Html::escape($agency_name);
        if (strpos(strtolower($agency_name), strtolower($string)) !== FALSE) {
          $results[] = [
            'value' => $agency . '|' . $key,
            'label' => $agency_name,
          ];
        }
      }
    }
    else {
      foreach ($options as $key => $agency_name) {
        $agency = Html::escape($agency_name);
        $results[] = [
          'value' => $agency . '|' . $key,
          'label' => $agency,
        ];
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Route callback.
   *
   * From ji_commerce_taxes.links.action.yml which routes
   * to 'ji_commerce_taxes.sync_taxes'.
   *
   * @param Request $request
   *   Our request variable, which we don't use.
   *
   * @return RedirectResponse
   *   Return us to the Commerce tax page.
   */
  public function sync(Request $request) {
    $quickbooks_service = new JIQuickBooksService();
    if ($quickbooks_service) {
      try {
        $response = $quickbooks_service->getAllTaxes();
        if (is_null($response)) {
          \Drupal::messenger()
            ->addWarning($this->t("No QuickBooks taxes found, make sure taxes have been setup correctly."), FALSE);
        }
        else {
          $error = $quickbooks_service->checkErrors();
          if (empty($error['code'])) {

            // Clean up a weird tax code which is returned by QBO.
            foreach ($response as $key => $row) {
              if ($row->Name === 'Out of scope') {
                unset($response[$key]);
                // Skip this and continue to the next row, if any.
                continue;
              }
            }

            $this->createUpdateTaxes($response);
            \Drupal::messenger()
              ->addStatus($this->t('Sync completed successfully'), FALSE);
          }
        }

        // Redirect to Commerce tax type collection page.
        return new RedirectResponse(Url::fromRoute('entity.commerce_tax_type.collection')
          ->toString());
      } catch (\Exception $e) {
        \Drupal::messenger()
          ->addError($this->t('@error', ['@error' => $e->getMessage()]), FALSE);
      }
    }
  }

  /**
   * Helper function.
   *
   * Updates or creates a tax item in Commerce.
   *
   * @param array $tax_codes
   *   All of the tax codes we found within QuickBooks.
   */
  private function createUpdateTaxes($tax_codes) {
    try {
      foreach ($tax_codes as $tax_code) {
        $machine_name = JIQuickBooksSupport::getMachineName('quickbooks_tax_id_' . $tax_code->Id);

        // Does tax type exist?
        $commerce_tax_item = TaxType::load($machine_name);

        // Doesn't exist, create a new one.
        if (is_null($commerce_tax_item)) {
          $commerce_tax_item = TaxType::create([
            'id' => $machine_name,
            'label' => $tax_code->Name,
            'plugin' => 'quickbookstax',
          ]);
        }
        // Tax rate existed.
        else {
          // QuickBooks tax rate name may have changed.
          // Get existing configuration so we don't
          // replace previous settings.
          $configuration = $commerce_tax_item->getPluginConfiguration();
        }

        $commerce_tax_item->set('label', $tax_code->Name);
        $commerce_tax_item->set('status', ($tax_code->Active == 'true') ? 1 : 0);

        $tax_type = NULL;
        $rate = [];
        $rates = [];

        // Size above 1 is a combined tax type.
        if (count($tax_code->TaxRates) > 1) {
          $tax_type = 'combined';
          foreach ($tax_code->TaxRates as $tax_rate) {
            $rates[] = $this->buildRate($tax_code, $tax_rate, $tax_type);
          }
        }
        else {
          $tax_type = 'single';
          $rate[] = $this->buildRate($tax_code, $tax_code->TaxRates[0], $tax_type);
        }

        if (isset($configuration)) {
          $configuration['rate'] = $rate;
          $configuration['rates'] = $rates;

          $commerce_tax_item->setPluginConfiguration($configuration);
        }
        else {
          $commerce_tax_item->setPluginConfiguration([
            'display_label' => 'tax',
            'tax_type' => $tax_type,
            'rate' => $rate,
            'rates' => $rates,
            'territories' => [
              0 => [
                'country_code' => 'US',
              ],
            ],
            'round' => FALSE,
            'display_inclusive' => FALSE,
          ]);
        }

        $commerce_tax_item->save();
      }
    } catch (EntityStorageException $e) {
      \Drupal::messenger()
        ->addError($this->t('@error', ['@error' => $e->getMessage()]), FALSE);
    }
  }

  /**
   * Helper function.
   *
   * Returns nicely arranged array of tax values.
   */
  private function buildRate($tax_code, $tax_rate, $type) {
    return [
      'id' => $tax_rate->TaxRateRef,
      'tax_type' => $type,
      'tax_name' => $tax_code->Name,
      'component_name' => $tax_rate->Name,
      'agency_name' => $tax_rate->AgencyName,
      'percentage' => (string) ($tax_rate->RateValue / 100),
    ];
  }

}
