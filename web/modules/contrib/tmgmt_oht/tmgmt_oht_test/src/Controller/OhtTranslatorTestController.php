<?php

namespace Drupal\tmgmt_oht_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mock server for Oht translator.
 */
class OhtTranslatorTestController {

  /**
   * The uuid of the source resource created by the mock server.
   */
  const SOURCE_RESOURCE_UUID = 'rsc-5270f421267822-79952796';

  /**
   * The uuid of the translation resource created by the mock server.
   */
  const TRANSLATION_RESOURCE_UUID = 'rsc-52724b2ac6b853-07164235';

  /**
   * The id of the project created.
   */
  const PROJECT_ID = '1847';

  /**
   * Helper function to format the response in the JSON format expected :
   * {
   *   "status" : {
   *     "code": $code,
   *     "msg": $msg
   *   },
   *   "results": $results,
   *   "errors": []
   * }
   *
   * @param string[] $results
   *   An array containing "results", to add to the JSON response.
   *
   * @return \Drupal\Component\Serialization\Json\JsonResponse
   *   The JSON response.
   */
  public function formatResponse($code, $msg, $results) {
    return new JsonResponse([
      'status' => [
        'code' => $code,
        'msg' => $msg,
      ],
      'results' => $results,
      'errors' => [],
    ]);
  }

  /**
   * Helper function to run authentication.
   *
   * @param string[] $results
   *   (optional) If the secret key should also be authenticated. Some API
   *   calls only require the public key.
   */
  function authenticate(Request $request, $authenticate_secret_key = TRUE) {
    // Only the public and secret keys are transmitted, in plain text.
    if ($request->get('public_key') == 'correct key') {
      if (!$authenticate_secret_key || ($request->get('secret_key') == 'correct key')) {
        return;
      }
    }
    // Authentication failed.
    return $this->formatResponse('102', 'Forbidden - you are not allowed to perform this request or api key has been revoked', []);
  }

  /**
   * Mock service to fetch account information.
   *
   * @param Request $request
   *   The request object.
   */
  public function account(Request $request) {
    if ($error_response = $this->authenticate($request)) {
      return $error_response;
    }

    return $this->formatResponse('0', 'ok', [
      'credits' => '10000',
      'account_id' => '123123',
      'account_username' => 'userTest',
    ]);
  }

  /**
   * Mock service to obtain a quote for a job.
   *
   * @param Request $request
   *   The request object.
   */
  public function quote(Request $request) {
    if ($error_response = $this->authenticate($request)) {
      return $error_response;
    }

    // Return a response similar to that of the Oht's API.
    /* @see https://www.onehourtranslation.com/translation/api-documentation-v2/general-instructions#get-quote */
    $resource = new \stdClass();
    $resource->price = 1.23;
    $resource->resource = $this::SOURCE_RESOURCE_UUID;
    $resource->wordcount = 123;
    $resource->credits = 99;

    $total = new \stdClass();
    $total->net_price = 1.23;
    $total->transaction_fee = 0.23;
    $total->price = 1.46;
    $total->wordcount = 123;
    $total->credits = 99;

    // Save the quote total for easier access by the test class.
    \Drupal::state()->set('tmgmt_oht_test_quote_total', $total);

    return $this->formatResponse('0', 'ok', [
      'currency' => 'EUR',
      'resources' => [
        $resource,
      ],
      'total' => $total,
    ]);
  }

  /**
   * Mock service to obtain the expertises.
   *
   * @param Request $request
   *   The request object.
   */
  public function discoverExpertise(Request $request) {
    if ($error_response = $this->authenticate($request)) {
      return $error_response;
    }

    // Return a response similar to that of the Oht's API.
    /* @see https://www.onehourtranslation.com/translation/api-documentation-v2/general-instructions#supported-expertise */
    $expertise_1 = new \stdClass();
    $expertise_1->name = 'Automotive / Aerospace';
    $expertise_1->code = 'automotive-aerospace';
    $expertise_1->expertise_id = 330;

    $expertise_2 = new \stdClass();
    $expertise_2->name = 'Business / Forex / Finance';
    $expertise_2->code = 'business-finance';
    $expertise_2->expertise_id = 331;

    // Save the expertise for easier access by the test class.
    \Drupal::state()->set('tmgmt_oht_test_expertise', [$expertise_1, $expertise_2]);

    return $this->formatResponse('0', 'ok', [
      $expertise_1,
      $expertise_2,
    ]);
  }

  /**
   * Mock service for supported languages discovery.
   *
   * @param Request $request
   *   The request object.
   */
  public function discoverLanguages(Request $request) {
    if ($error_response = $this->authenticate($request, FALSE)) {
      return $error_response;
    }

    $english = new \stdClass();
    $english->name = "English";
    $english->code = "en-us";

    $german = new \stdClass();
    $german->name = "German";
    $german->code = "de-de";

    return $this->formatResponse('0', 'ok', [
      $english,
      $german,
    ]);
  }

  /**
   * Mock service for supported language pairs discovery.
   *
   * @param Request $request
   *   The request object.
   */
  public function discoverLanguagePairs(Request $request) {
    if ($error_response = $this->authenticate($request, FALSE)) {
      return $error_response;
    }

    $source_english = new \stdClass();
    $source_english->name = 'English';
    $source_english->code = 'en-us';

    $target_german = new \stdClass();
    $target_german->name = 'German';
    $target_german->code = 'de-de';
    $target_german->availability = 'medium';

    $target_spanish = new \stdClass();
    $target_spanish->name = 'Spanish';
    $target_spanish->code = 'es-es';
    $target_spanish->availability = 'medium';

    return $this->formatResponse('0', 'ok', [
      [
        'source' => $source_english,
        'targets' => [
          $target_german,
          $target_spanish,
        ],
      ],
    ]);
  }

  /**
   * Mock service to create a file resource.
   *
   * @param Request $request
   *   The request object.
   */
  public function createFileResource(Request $request) {
    if ($error_response = $this->authenticate($request)) {
      return $error_response;
    }

    // Save the resource uuid for easier access by the test class.
    \Drupal::state()->set('tmgmt_oht_test_source_resource_uuid', $this::SOURCE_RESOURCE_UUID);

    // Retrieve the file path of the xliff file attached to the request and
    // save the file's content, which will be used when sending translations.
    $path_name = $request->files->get('upload')->getPathname();
    \Drupal::state()->set('tmgmt_oht_test_xliff_file_content', file_get_contents($path_name));

    // Return a dummy resource uuid.
    return $this->formatResponse('0', 'ok', [
      $this::SOURCE_RESOURCE_UUID,
    ]);
  }

  /**
   * Mock service for creating a translation project.
   *
   * @param Request $request
   *   The request object.
   */
  public function projectsTranslation(Request $request) {
    if ($error_response = $this->authenticate($request)) {
      return $error_response;
    }

    // The mock server only supports English to German or Spanish translations.
    $supported_target_languages = ['de-de', 'es-es'];
    if (($request->get('source_language') !== 'en-us') || !in_array($request->get('target_language'), $supported_target_languages)) {
      return $this->formatResponse('201', 'language pair is currently unsupported');
    }

    // Verify that the resource uuid is correct (ie. the one returned when
    // creating the file resource).
    if ($request->get('sources') !== $this::SOURCE_RESOURCE_UUID) {
      // Return a general request error.
      return $this->formatResponse('104', 'Item is missing or you are not authorized', []);
    }

    // Save the job item id and its hash.
    \Drupal::state()->set('tmgmt_oht_test_tjiid', $request->get('custom0'));
    \Drupal::state()->set('tmgmt_oht_test_tjiid_hash', $request->get('custom1'));

    // Save the project id for easier access by the test class.
    \Drupal::state()->set('tmgmt_oht_test_project_id', $this::PROJECT_ID);

    // Return a response with a dummy project id, word count and remaining
    // credits.
    return $this->formatResponse('0', 'ok', [
      'project_id' => $this::PROJECT_ID,
      'wordcount' => 14,
      'credits' => 98,
    ]);
  }

  /**
   * Mock service for downloading resources.
   *
   * @param Request $request
   *   The request object.
   */
  public function resourcesDownload(Request $request) {
    // Add the translations to the saved xliff file and send it back.
    $xliff_content = \Drupal::state()->get('tmgmt_oht_test_xliff_file_content');
    $xliff_content = str_replace('<target xml:lang="de-de"/>', '<target xml:lang="de-de"><![CDATA[Hallo Wort]]></target>', $xliff_content);
    return new Response($xliff_content);
  }

  /**
   * Mock service for retrieving project's details.
   *
   * @param Request $request
   *   The request object.
   */
  public function projectDetails(Request $request) {
    $resources = new \stdClass();
    $resources->sources = [
      $this::SOURCE_RESOURCE_UUID,
    ];
    $resources->translations = [
      $this::TRANSLATION_RESOURCE_UUID,
    ];
    $resources->wordcount = 123;
    return $this->formatResponse('0', 'ok', [
      'project_id' => $this::PROJECT_ID,
      'project_type' => 'Translation',
      'project_status_code' => 'signed',
      'source_language' => 'en-us',
      'target_language' => 'de-de',
      'resources' => $resources,
    ]);
  }

}
