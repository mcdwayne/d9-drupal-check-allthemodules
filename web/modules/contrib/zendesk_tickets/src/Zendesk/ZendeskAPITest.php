<?php

namespace Drupal\zendesk_tickets\Zendesk;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zendesk\API\Resources\ResourceAbstract;
use Zendesk\API\Exceptions\ApiResponseException as ZendeskApiResponseException;
use \Closure;

/**
 * Provides tests for Zendesk API.
 */
class ZendeskAPITest implements ContainerInjectionInterface {

  /**
   * Zendesk API object.
   *
   * @var ZendeskAPI
   */
  protected $api;

  /**
   * Constructor.
   *
   * @param ZendeskAPI $api
   *   The Zendesk API handler.
   */
  public function __construct(ZendeskAPI $api) {
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zendesk_tickets.zendesk_api')
    );
  }

  /**
   * Check the status of the ticket forms end point.
   *
   * @return array
   *   See runResourceTests().
   */
  public function ticketFormsStatusCheck() {
    $resource = $this->api->ticketFormsResource();
    $tests = [];

    // Find all.
    $tests['findAll'] = [
      'scenario' => 'Allow: Retrieve all ticket forms.',
      'f' => function () use ($resource) {
        return $resource->findAll();
      },
    ];

    // Find a single item.
    $tests['find'] = [
      'scenario' => 'Allow: Retrieve a single ticket form.',
      'f' => function () use ($resource) {
        return $resource->find(95843);
      },
    ];

    // Create.
    $tests['create'] = [
      'scenario' => 'Deny: Create a ticket form.',
      'expected_codes' => [403, 903],
      'f' => function () use ($resource) {
        return $resource->create([
          'name' => 'TEST: Drupal Help Desk API',
        ]);
      },
    ];

    // Run the tests.
    return $this->runResourceTests($resource, $tests);
  }

  /**
   * Check the status of the tickets end point.
   *
   * @return array
   *   See runResourceTests().
   */
  public function ticketsStatusCheck() {
    $resource = $this->api->ticketsResource();
    $tests = [];

    // Find all.
    $tests['findAll'] = [
      'scenario' => 'Deny: Retrieving all tickets.',
      'expected_codes' => [403, 903],
      'f' => function () use ($resource) {
        return $resource->findAll();
      },
    ];

    // Find a single item.
    // TODO: Use a created ticket number?
    $tests['find'] = [
      'scenario' => 'Deny: Retrieving a single ticket.',
      'expected_codes' => [403, 903],
      'f' => function () use ($resource) {
        return $resource->find(100);
      },
    ];

    // Create.
    // This test attempts to create a ticket without the required "comment"
    // parameter.  The response code should be 422.
    // @TODO: Better way to verify create is allowed.
    $tests['create'] = [
      'scenario' => 'Allow: Create a ticket - Attempt to create a ticket with missing required "comment" parameter.',
      'expected_codes' => [422],
      'f' => function () use ($resource) {
        return $resource->create([
          'subject'  => 'TEST: Drupal Help Desk API',
        ]);
      },
    ];

    return $this->runResourceTests($resource, $tests);
  }

  /**
   * Run all tests for a given resource.
   *
   * @param ResourceAbstract $resource
   *   The resource object.
   * @param array $tests
   *   An array of:
   *   - 'scenario': A description of the test scenario.
   *   - 'f': The Closure function to call.
   *   - 'expected_codes': Optional array of error codes expected.
   *
   * @return array
   *   The return from runTests() with the additional top level properties:
   *   - "resource_name": The name of the resource.
   */
  protected function runResourceTests(ResourceAbstract $resource, array $tests) {
    // Resource not found.
    if (empty($resource)) {
      return [
        'pass' => FALSE,
        'code' => 404,
        'message' => 'Resource Not found',
      ];
    }

    // Initialize return.
    $return = [];

    // Run the tests.
    if (!empty($tests)) {
      $return = $this->runTests($tests);
    }

    // Add top level info.
    $return['resource_name'] = $resource->getResourceName();

    return $return;
  }

  /**
   * Run all tests.
   *
   * @param array $tests
   *   An array of:
   *   - 'scenario': A description of the test scenario.
   *   - 'f': The Closure function to call.
   *   - 'expected_codes': Optional array of error codes expected.
   *
   * @return array
   *   An array of:
   *   - 'pass': TRUE / FALSE.
   *   - 'message': The response message.
   *   - 'tests': Each test array with the result added:
   *      - 'test': The original test item.
   *      - 'result': The test result.
   */
  protected function runTests(array $tests) {
    $results = [];
    $any_pass = FALSE;
    foreach ($tests as $t => $test) {
      $results[$t]['test'] = $test;
      if (isset($test['f'])) {
        $expected_codes = isset($test['expected_codes']) ? $test['expected_codes'] : [];
        $results[$t]['result'] = $this->checkMethodStatus($test['f'], $expected_codes);
        if (!$any_pass && !empty($results[$t]['result']['pass'])) {
          $any_pass = TRUE;
        }
      }
    }

    $return = ['pass' => $any_pass];
    if ($any_pass) {
      $return['message'] = 'All tests passed.';
    }
    else {
      $return['message'] = 'All tests failed.';
    }

    $return['tests'] = $results;

    return $return;
  }

  /**
   * Calls function with an error catcher to report status.
   *
   * @param Closure $function
   *   An anonymous function to be called.
   * @param array $expected_codes
   *   An array of expected codes other than succesful.
   *
   * @return array
   *   An array of:
   *   - 'pass': TRUE / FALSE.
   *   - 'code': The HTTP status code.
   *   - 'expected_error': TRUE / FALSE.
   *   - 'message': The response message.
   */
  protected function checkMethodStatus(Closure $function, array $expected_codes = []) {
    $return = [];
    try {
      $response = $function();
      if (isset($response)) {
        $return['pass'] = TRUE;
        $return['code'] = 200;
      }
      else {
        // NULL return = Limited by API handler.
        $return['pass'] = FALSE;
        $return['code'] = 903;
        $return['message'] = 'Limited by API handler';
        $return['expected_error'] = FALSE;
      }
    }
    catch (ZendeskApiResponseException $error) {
      // API errors.
      $return['pass'] = FALSE;
      $return['code'] = $error->getCode();
      $return['message'] = $this->api->getErrorMessage($error);
      $return['expected_error'] = FALSE;
    }

    if ($expected_codes && !empty($return['code']) && in_array($return['code'], $expected_codes)) {
      $return['pass'] = TRUE;
      $return['expected_error'] = TRUE;
    }

    return $return;
  }

}
