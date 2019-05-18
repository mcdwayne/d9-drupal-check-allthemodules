<?php

namespace Drupal\google_appliance\Service;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\google_appliance\SearchResults\KeyMatch;
use Drupal\google_appliance\SearchResults\OneBoxResult;
use Drupal\google_appliance\SearchResults\OneBoxResultSet;
use Drupal\google_appliance\SearchResults\ResultSet;
use Drupal\google_appliance\SearchResults\Result;
use Drupal\google_appliance\SearchResults\Synonym;

/**
 * Defines a class for parsing GSA responses.
 */
class Parser implements ParserInterface {

  /**
   * Cached parse result.
   *
   * @var null|\Drupal\google_appliance\SearchResults\ResultSet
   */
  protected $response;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new Parser object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function parseResponse($xml, $useCached = TRUE) {
    if (!$this->response || FALSE === $useCached) {
      $response = new ResultSet();

      // Look for xml parse errors.
      libxml_use_internal_errors(TRUE);
      /** @var \SimpleXMLElement $payload */
      $payload = simplexml_load_string($xml);

      if ($payload === FALSE) {
        // XML parse error(s)
        $errors = [];
        foreach (libxml_get_errors() as $error) {
          $response->addError($error->message);
        }
      }
      else {
        // Store metrics for stat reporting.
        $response->setLastResult((int) $payload->RES['EN']);

        $this->parseResultCount($payload, $response);

        // Search returned zero results.
        if (!$response->getTotal()) {
          $response->addError('No results', ResultSet::ERROR_NO_RESULTS);
          $this->response = $response;
          return $this->response;
        }

        // Spelling suggestions.
        if (isset($payload->Spelling->Suggestion)) {
          $spelling_suggestion = (string) $payload->Spelling->Suggestion;
          $response->addSpellingSuggestion(Xss::filter($spelling_suggestion, [
            'b',
            'i',
          ]));
        }

        $this->parseOneboxResults($payload, $response);

        foreach ($payload->xpath('//GM') as $km) {
          $keymatch = new KeyMatch((string) $km->GD, (string) $km->GL);
          $response->addKeyMatch($keymatch);
        }

        // If there are any synonyms returned by the appliance,
        // put them in the results as a new array.
        // @see http://code.google.com/apis/searchappliance/documentation/50/xml_reference.html#tag_onesynonym
        foreach ($payload->xpath('//OneSynonym') as $syn_element) {
          $synonym = new Synonym((string) $syn_element, (string) $syn_element['q']);
          $response->addSynonym($synonym);
        }

        $this->parseResultEntries($payload, $response);

        $this->moduleHandler->alter('google_appliance_results', $results, $payload);
        $this->response = $response;
      }
    }

    return $this->response;
  }

  /**
   * Parse the count of total results.
   *
   * @param \SimpleXMLElement $payload
   *   Payload.
   * @param \Drupal\google_appliance\SearchResults\ResultSet $response
   *   Response.
   */
  protected function parseResultCount(\SimpleXMLElement $payload, ResultSet $response) {
    $response->setTotal((int) $payload->RES->M);

    // C check if there is an result at all ($payload->RES),
    // secure search doesn't provide a value for $payload->RES->M.
    if (isset($payload->RES) && !$response->getTotal()) {
      $response->setTotal((int) $payload->RES['EN']);

      $param_start = $payload->xpath('/GSP/PARAM[@name="start"]');
      $param_num = $payload->xpath('/GSP/PARAM[@name="num"]');
      $request_max_total = (int) $param_start[0]['value'] + (int) $param_num[0]['value'];

      if ($response->getTotal() === $request_max_total) {
        $response->setTotal($response->getTotal() + 1);
      }
    }
  }

  /**
   * Parse results from the payload.
   *
   * @param \SimpleXMLElement $payload
   *   Payload.
   * @param \Drupal\google_appliance\SearchResults\ResultSet $response
   *   Response.
   */
  protected function parseResultEntries(\SimpleXMLElement $payload, ResultSet $response) {
    foreach ($payload->xpath('//R') as $res) {
      $result = new Result((string) $res->U, (string) $res->UE, (string) $res->T, (string) $res->S, (string) $res->FS['VALUE'], (string) $res['MIME'], isset($res['L']) ? (int) $res['L'] : 1);

      // Result meta data.
      // Here we just collect the data from the device
      // and leave implementing display of meta data
      // to the themer (use-case specific).
      // @see google-appliance-result.tpl.php
      foreach ($res->xpath('./MT') as $mt) {
        $result->addMeta((string) $mt['N'], (string) $mt['V']);
      }
      $response->addResult($result);
    }
  }

  /**
   * Parse onebox results.
   *
   * @param \SimpleXMLElement $payload
   *   Payload.
   * @param \Drupal\google_appliance\SearchResults\ResultSet $response
   *   Response.
   */
  protected function parseOneboxResults(\SimpleXMLElement $payload, ResultSet $response) {
    // Onebox results.
    // @see https://developers.google.com/search-appliance/documentation/614/oneboxguide#providerresultsschema
    // @see https://developers.google.com/search-appliance/documentation/614/oneboxguide#mergingobs
    foreach ($payload->xpath('//ENTOBRESULTS/OBRES') as $mod) {
      $result_code = empty($mod->resultCode) ? '' : (string) $mod->resultCode;
      if (empty($result_code) || $result_code === 'success') {
        $module_name = (string) $mod['module_name'];
        $onebox = new OneBoxResultSet($module_name, (string) $mod->provider, (string) $mod->title->urlText, (string) $mod->title->urlLink, (string) $mod->IMAGE_SOURCE, $mod->description);
        foreach ($mod->xpath('./MODULE_RESULT') as $res) {
          $result = new OneBoxResult((string) $res->U, (string) $res->Title);
          foreach ($res->xpath('./Field') as $field) {
            $field_name = (string) $field['name'];
            $result->addFieldValue($field_name, (string) $field);
          }
          $onebox->addResult($result);
        }
        $response->addOneBoxResultSet($module_name, $onebox);
      }
    }
  }

}
