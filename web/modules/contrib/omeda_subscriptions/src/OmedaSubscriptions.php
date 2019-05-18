<?php

namespace Drupal\omeda_subscriptions;

use Drupal\omeda\Omeda;

/**
 * Manage user optins and optouts.
 */
class OmedaSubscriptions {

  /**
   * The Omeda service for making API requests.
   *
   * @var \Drupal\omeda\Omeda
   */
  protected $omeda;

  /**
   * Constructs an OmedaSubscriptionsManager object.
   *
   * @param \Drupal\omeda\Omeda $omeda
   *   The Omeda service for making API requests.
   */
  public function __construct(Omeda $omeda) {
    $this->omeda = $omeda;
  }

  /**
   * Retrieve opt in/out information stored for a given customer.
   *
   * @param string $email
   *   The email address you are searching for.
   *
   * @see https://jira.omeda.com/wiki/en/Opt_In/Out_Lookup_Service
   *
   * @return array
   *   An array keyed by deployment type IDs, each set to either "IN" or "OUT"
   *   to indicate the status.
   *
   * @throws \Exception
   */
  public function optLookup($email) {
    $api_response = $this->omeda->submitRequest('/filter/email/' . $email . '/*', 'GET');

    if (isset($api_response['Errors'][0]['Error'])) {
      $error_message = $api_response['Errors'][0]['Error'];

      // If a user has no opt-ins or opt-outs, the Omeda API gives us an error
      // state, but we just want to treat it as a success which just happens to
      // be empty.
      if ($error_message === 'There are no opt-ins/opt-outs for ' . $email) {
        return [];
      }

      $this->omeda->handleApiError($error_message);
    }

    $results = [];
    foreach ($api_response['Filters'] as $filter) {
      $results[$filter['DeploymentTypeId']] = $filter['Status'];
    }

    return $results;
  }

  /**
   * OptIn a given customer/subscriber to email deployments of the given types.
   *
   * @param string $email
   *   The email address to update.
   * @param array|int $deployment_type_ids
   *   One or more deployment type IDs for the user to be opted in to.
   * @param bool $delete_opt_out
   *   Whether or not to set the DeleteOptOut parameter in the API call.
   *
   * @see https://jira.omeda.com/wiki/en/Optin_Queue_Service
   *
   * @return array
   *   Returns the response from the Omeda API
   *
   * @throws \Exception
   */
  public function optInDeploymentTypes($email, $deployment_type_ids, $delete_opt_out = FALSE) {
    if (!is_array($deployment_type_ids)) {
      $deployment_type_ids = [$deployment_type_ids];
    }

    $data = [
      'DeploymentTypeOptIn' => [
        [
          'EmailAddress' => $email,
          'DeploymentTypeId' => $deployment_type_ids,
        ],
      ],
    ];

    if ($delete_opt_out) {
      $data['DeploymentTypeOptIn'][0]['DeleteOptOut'] = 1;
    }

    $api_response = $this->omeda->submitRequest('/optinfilterqueue/*', 'POST', $data, FALSE, TRUE);
    if (isset($api_response['Errors'][0]['Error'])) {
      $this->omeda->handleApiError($api_response['Errors'][0]['Error']);
    }

    return $api_response;
  }

  /**
   * OptOut a customer/subscriber from email deployments of the given types.
   *
   * @param string $email
   *   The email address to update.
   * @param array|int $deployment_type_ids
   *   One or more deployment type IDs for the user to be opted out of.
   *
   * @see https://jira.omeda.com/wiki/en/Optout_Queue_Service
   *
   * @return array
   *   Returns the response from the Omeda API
   *
   * @throws \Exception
   */
  public function optOutDeploymentTypes($email, $deployment_type_ids) {
    if (!is_array($deployment_type_ids)) {
      $deployment_type_ids = [$deployment_type_ids];
    }

    $data = [
      'DeploymentTypeOptOut' => [
        [
          'EmailAddress' => $email,
          'DeploymentTypeId' => $deployment_type_ids,
        ],
      ],
    ];

    $api_response = $this->omeda->submitRequest('/optoutfilterqueue/*', 'POST', $data, FALSE, TRUE);
    if (isset($api_response['Errors'][0]['Error'])) {
      $this->omeda->handleApiError($api_response['Errors'][0]['Error']);
    }

    return $api_response;
  }

}
