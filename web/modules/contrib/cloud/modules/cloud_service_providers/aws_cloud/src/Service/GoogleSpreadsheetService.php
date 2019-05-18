<?php

namespace Drupal\aws_cloud\Service;

use Google_Client;
use Google_Service_Exception;
use Google_Service_Drive;
use Google_Service_Drive_Permission;
use Google_Service_Sheets;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_ValueRange;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_Request;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The google spreadsheet service.
 */
class GoogleSpreadsheetService {

  use StringTranslationTrait;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The price data provider.
   *
   * @var \Drupal\aws_cloud\Service\InstanceTypePriceDataProvider
   */
  protected $dataProvider;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory.
   * @param \Drupal\aws_cloud\Service\InstanceTypePriceDataProvider $data_provider
   *   The price data provider.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
    InstanceTypePriceDataProvider $data_provider,
    TranslationInterface $string_translation
  ) {
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->dataProvider = $data_provider;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Create or update a spreadsheet.
   *
   * @param string $spreadsheet_url
   *   The url of the spreadsheet.
   * @param string $region
   *   The name of an AWS Ec2 region.
   * @param string $title
   *   The title of the spreadsheet.
   *
   * @return string
   *   The url of the spreadsheet created.
   */
  public function createOrUpdate($spreadsheet_url, $region, $title) {
    try {
      $spreadsheet_id = NULL;
      if (!empty($spreadsheet_url)) {
        if (preg_match('/spreadsheets\/d\/(.+)\/edit/', $spreadsheet_url, $matches)) {
          $spreadsheet_id = $matches[1];
        }
      }

      // Get the API client and construct the service object.
      $client = $this->getClient();
      $service = new Google_Service_Sheets($client);

      // Create spreadsheet if spreadsheet id is NULL.
      if (empty($spreadsheet_id)) {
        $spreadsheet = $this->createSpreadsheet($client, $service, $title);
        $spreadsheet_id = $spreadsheet->getSpreadsheetId();
      }
      else {
        $spreadsheet = $service->spreadsheets->get($spreadsheet_id);
      }

      // Add or update the sheet for region.
      $sheet = $this->findSheet($service, $spreadsheet, $region);
      if ($sheet == NULL) {
        $sheet = $this->createSheet($service, $spreadsheet, $region);
      }

      $sheet_id = $sheet->getProperties()->getSheetId();
      $sheet_title = $sheet->getProperties()->getTitle();

      // Append data.
      $values = $this->getValues(
        $this->dataProvider->getFields(),
        $this->dataProvider->getDataByRegion($region)
      );
      $request_body = new Google_Service_Sheets_ValueRange(['values' => $values]);
      $service->spreadsheets_values->update(
        $spreadsheet_id,
        $sheet_title . '!A1',
        $request_body,
        ['valueInputOption' => 'USER_ENTERED']
      );

      $requests = [];
      for ($i = 1; $i < count($values[0]); $i++) {
        $my_range = [
          'sheetId' => $sheet_id,
          'startRowIndex' => 1,
          'endRowIndex' => count($values),
          'startColumnIndex' => $i,
          'endColumnIndex' => $i + 1,
        ];
        $requests[] = new Google_Service_Sheets_Request([
          'addConditionalFormatRule' => [
            'rule' => [
              'ranges' => [$my_range],
              'gradientRule' => [
                'minpoint' => [
                  'color' => [
                    'green' => 1,
                    'red' => 0,
                  ],
                  'type' => 'MIN',
                ],
                'midpoint' => [
                  // Color rgb(255, 214, 102).
                  'color' => [
                    'red' => 1,
                    'green' => 0.84,
                    'blue' => 0.4,
                  ],
                  'type' => 'PERCENTILE',
                  'value' => '50',
                ],
                'maxpoint' => [
                  'color' => [
                    'green' => 0,
                    'red' => 1,
                  ],
                  'type' => 'MAX',
                ],
              ],
            ],
            'index' => 0,
          ],
        ]);
      }

      // Make header align to center.
      $requests[] = new Google_Service_Sheets_Request([
        'repeatCell' => [
          'cell' => [
            'userEnteredFormat' => [
              'horizontalAlignment' => 'CENTER',
              'verticalAlignment' => 'MIDDLE',
            ],
          ],
          'range' => [
            'sheetId' => $sheet_id,
            'startRowIndex' => 0,
            'endRowIndex' => 1,
            'startColumnIndex' => 0,
            'endColumnIndex' => count($values[0]),
          ],
          'fields' => 'userEnteredFormat',
        ],
      ]);

      // Update the font family to Lato.
      $requests[] = new Google_Service_Sheets_Request([
        'repeatCell' => [
          'cell' => [
            'userEnteredFormat' => [
              'textFormat' => [
                'fontFamily' => 'Lato',
              ],
            ],
          ],
          'range' => [
            'sheetId' => $sheet_id,
            'startRowIndex' => 0,
            'endRowIndex' => count($values),
            'startColumnIndex' => 0,
            'endColumnIndex' => count($values[0]),
          ],
          'fields' => 'userEnteredFormat.textFormat.fontFamily',
        ],
      ]);

      // Add basic filter.
      $requests[] = new Google_Service_Sheets_Request([
        'setBasicFilter' => [
          'filter' => [
            'range' => [
              'sheetId' => $sheet_id,
              'startRowIndex' => 0,
              'endRowIndex' => 1,
              'startColumnIndex' => 0,
              'endColumnIndex' => count($values[0]),
            ],
          ],
        ],
      ]);

      // Freeze column and row and update sheet name.
      $requests[] = new Google_Service_Sheets_Request([
        'updateSheetProperties' => [
          'properties' => [
            'sheetId' => $sheet_id,
            'gridProperties' => [
              'frozenRowCount' => 1,
              'frozenColumnCount' => 1,
            ],
            'title' => $region,
          ],
          'fields' => 'gridProperties.frozenRowCount,gridProperties.frozenColumnCount,title',
        ],
      ]);

      // Update column instance_type width.
      $requests[] = new Google_Service_Sheets_Request([
        'updateDimensionProperties' => [
          'range' => [
            'sheetId' => $sheet_id,
            'dimension' => 'COLUMNS',
            'startIndex' => 0,
            'endIndex' => 1,
          ],
          'properties' => [
            'pixelSize' => 120,
          ],
          'fields' => 'pixelSize',
        ],
      ]);

      $batch_update_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
        ['requests' => $requests]
      );
      $service->spreadsheets->batchUpdate($spreadsheet_id, $batch_update_request);

      return $spreadsheet->getSpreadsheetUrl() . '#gid=' . $sheet_id;
    }
    catch (Google_Service_Exception $e) {
      foreach ($e->getErrors() as $error) {
        $this->messenger->addError(
          $this->t(
            'Failed to create spreadsheet due to the Google_Service_Exception with error "@message"',
            ['@message' => $error['message']]
          )
        );
      }
    }
    catch (\Exception $e) {
      $this->messenger->addError(
        $this->t(
          'Failed to create spreadsheet due to the Exception with error "@message"',
          ['@message' => $e->getMessage()]
        )
      );
    }

    return '';
  }

  /**
   * Delete a spreadsheet.
   *
   * @param string $spreadsheet_url
   *   The url of a spreadsheet.
   */
  public function delete($spreadsheet_url) {
    try {
      $client = $this->getClient();
      $drive_service = new Google_Service_Drive($client);

      if (preg_match('/spreadsheets\/d\/(.+)\/edit/', $spreadsheet_url, $matches)) {
        $spreadsheet_id = $matches[1];
        $drive_service->files->delete($spreadsheet_id);
      }
    }
    catch (Google_Service_Exception $e) {
      foreach ($e->getErrors() as $error) {
        $this->messenger->addError(
          $this->t(
            'Failed to delete spreadsheet due to the Google_Service_Exception with error "@message"',
            ['@message' => $error['message']]
          )
        );
      }
    }
    catch (\Exception $e) {
      $this->messenger->addError(
        $this->t(
          'Failed to delete spreadsheet due to the Exception with error "@message"',
          ['@message' => $e->getMessage()]
        )
      );
    }
  }

  /**
   * Get client.
   *
   * @return \Google_Client
   *   Google client.
   */
  private function getClient() {
    $client = new Google_Client();
    $client->setApplicationName('Cloud');
    $client->setScopes([
      Google_Service_Sheets::SPREADSHEETS,
      Google_Service_Drive::DRIVE,
    ]);

    $client->setAuthConfig(aws_cloud_google_credential_file_path());
    $client->setAccessType('offline');
    return $client;
  }

  /**
   * Get values for spreadsheet.
   *
   * @param array $fields
   *   Fields.
   * @param array $data
   *   Data.
   *
   * @return array
   *   The values for spreadsheet.
   */
  private function getValues(array $fields, array $data) {
    $headers = array_map(function ($item) {
      return str_replace('<br>', "\n", $item->render());
    }, array_values($fields));

    $rows = array_map(function ($item) {
      return array_values($item);
    }, $data);

    return array_merge([$headers], array_values($rows));
  }

  /**
   * Create a spreadsheet and set the permission.
   *
   * @param \Google_Client $client
   *   The google API client.
   * @param \Google_Service_Sheets $service
   *   The google spreadsheet service.
   * @param string $title
   *   The title of the spreadsheet.
   *
   * @return \Google_Service_Sheets_Spreadsheet
   *   The new spreadsheet created.
   */
  private function createSpreadsheet(
    Google_Client $client,
    Google_Service_Sheets $service,
    $title
  ) {
    $spreadsheet = new Google_Service_Sheets_Spreadsheet([
      'properties' => [
        'title' => $title,
      ],
    ]);
    $spreadsheet = $service->spreadsheets->create($spreadsheet);
    $spreadsheet_id = $spreadsheet->getSpreadsheetId();

    // Share the file.
    $drive_service = new Google_Service_Drive($client);
    $userPermission = new Google_Service_Drive_Permission([
      'type' => 'anyone',
      'role' => 'reader',
    ]);
    $drive_service->permissions->create(
      $spreadsheet_id,
      $userPermission,
      ['fields' => 'id']
    );

    return $spreadsheet;
  }

  /**
   * Find the sheet with the same name as the region's name.
   *
   * @param \Google_Service_Sheets $service
   *   The google spreadsheet service.
   * @param \Google_Service_Sheets_Spreadsheet $spreadsheet
   *   The spreadsheet.
   * @param string $region
   *   The region.
   *
   * @return \Google_Service_Sheets_Sheet
   *   The sheet found. NULL if there is no sheet found.
   */
  private function findSheet(
    Google_Service_Sheets $service,
    Google_Service_Sheets_Spreadsheet $spreadsheet,
    $region
  ) {
    $spreadsheet_id = $spreadsheet->getSpreadsheetId();
    $sheets = $spreadsheet->getSheets();
    foreach ($sheets as $sheet) {
      if ($sheet->getProperties()->getTitle() == $region) {
        return $sheet;
      }
    }

    // If there is only a default sheet, use it and update title.
    if (count($sheets) == 1 && $sheets[0]->getProperties()->getTitle() == 'Sheet1') {
      $request = new Google_Service_Sheets_Request([
        'updateSheetProperties' => [
          'properties' => [
            'sheetId' => 0,
            'title' => $region,
          ],
          'fields' => 'title',
        ],
      ]);

      $batch_update_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
        ['requests' => [$request]]
      );
      $service->spreadsheets->batchUpdate($spreadsheet_id, $batch_update_request);
      $sheets[0]->getProperties()->setTitle($region);
      return $sheets[0];
    }

    return NULL;
  }

  /**
   * Find the place to insert the sheet of the region.
   *
   * @param \Google_Service_Sheets_Spreadsheet $spreadsheet
   *   The spreadsheet.
   * @param string $region
   *   The region.
   *
   * @return int
   *   The place to insert.
   */
  private function findSheetInsertIndex(
    Google_Service_Sheets_Spreadsheet $spreadsheet,
    $region
  ) {
    $sheets = $spreadsheet->getSheets();
    foreach ($sheets as $sheet) {
      if ($sheet->getProperties()->getTitle() > $region) {
        return $sheet->getProperties()->getIndex();
      }
    }

    return $sheets[count($sheets) - 1]->getProperties()->getIndex() + 1;
  }

  /**
   * Create a sheet.
   *
   * @param \Google_Service_Sheets $service
   *   The google spreadsheet service.
   * @param \Google_Service_Sheets_Spreadsheet $spreadsheet
   *   The spreadsheet.
   * @param string $region
   *   The region.
   *
   * @return \Google_Service_Sheets_Sheet
   *   The sheet created.
   */
  private function createSheet(
    Google_Service_Sheets $service,
    Google_Service_Sheets_Spreadsheet $spreadsheet,
    $region
  ) {
    $spreadsheet_id = $spreadsheet->getSpreadsheetId();

    // Create a sheet.
    $request = new Google_Service_Sheets_Request([
      'addSheet' => [
        'properties' => [
          'title' => $region,
          'index' => $this->findSheetInsertIndex($spreadsheet, $region),
        ],
      ],
    ]);

    $batch_update_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
      ['requests' => [$request]]
    );
    $response = $service->spreadsheets->batchUpdate($spreadsheet_id, $batch_update_request);
    return $response->getReplies()[0]->getAddSheet();
  }

}
