<?php

namespace Drupal\eloqua_api_redux\Service;

/**
 * Class Contact.
 *
 * A contact is a data entity that contains the explicit data
 * around an individual person in the database.
 *
 * See:
 * https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAA/index.html#CSHID=Contacts
 *
 * For API Refs, See
 * https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/api-application-2.0-contacts.html
 * https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/api-application-1.0-contacts.html
 *
 * @package Drupal\eloqua_api_redux\Service
 */
class Contact {

  /**
   * Eloqua Api Client.
   *
   * @var \Drupal\eloqua_api_redux\Service\EloquaApiClient
   */
  protected $client;

  /**
   * Contact constructor.
   *
   * @param \Drupal\eloqua_api_redux\Service\EloquaApiClient $client
   *   Eloqua API Client.
   */
  public function __construct(EloquaApiClient $client) {
    $this->client = $client;
  }

  /**
   * Create a contact.
   *
   * Creates a contact that matches the criteria specified by the request body
   * See https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/op-api-rest-1.0-data-contact-post.html.
   *
   * @param array $contactArray
   *   The array defines the details of the contact to be created.
   *
   * @return array
   *   Either the user array or empty array.
   */
  public function createContact(array $contactArray) {
    $endpointUrl = '/api/REST/2.0/data/contact';
    $contactArray = array_filter($contactArray);

    // Dont do anything if the contact array is empty.
    if (empty($contactArray)) {
      return [];
    }

    $newContact = $this->client->doEloquaApiRequest('POST', $endpointUrl, $contactArray);
    if (!empty($newContact)) {
      return $newContact;
    }

    return [];
  }

  /**
   * Get Contacts.
   *
   * Retrieves all contacts that match the criteria specified by the parameters.
   * See https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/op-api-rest-1.0-data-contacts-get.html.
   *
   * @param array $queryParams
   *   Available Query Params
   *   - count(optional): integer
   *   - depth(optional): string
   *   - lastUpdatedAt(optional): integer
   *   - orderBy(optional): string
   *   - page(optional): integer
   *   - search(optional): string
   *   - viewId(optional): integer.
   *
   * @return array
   *   Either the user array or empty array.
   */
  public function getContacts(array $queryParams = []) {
    $endpointUrl = '/api/REST/2.0/data/contacts';

    // Dont do anything if the contact array is empty.
    if (empty($queryParams)) {
      return [];
    }

    $contacts = $this->client->doEloquaApiRequest('GET', $endpointUrl, NULL, $queryParams);
    if (!empty($contacts)) {
      return $contacts;
    }

    return [];
  }

  /**
   * Lookup contact by email address.
   *
   * @param string $emailAddress
   *   Contact Email address.
   *
   * @return array
   *   Either the user array or empty array.
   */
  public function getContactByEmail($emailAddress) {
    $queryParams['search'] = $emailAddress;
    $contact = $this->getContacts($queryParams);

    if (!empty($contact['elements'])) {
      return $contact['elements'][0];
    }

    return [];
  }

  /**
   * Retrieve a contact.
   *
   * Retrieves the contact specified by the id parameter.
   * See: https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/op-api-rest-1.0-data-contact-id-get.html.
   *
   * @param int $contactId
   *   Contact ID.
   * @param array $queryParams
   *   Available Params
   *   - depth(optional): string
   *   - viewId(optional): integer.
   *
   * @return array
   *   Either the user array or empty array.
   */
  public function getContactById($contactId, array $queryParams = []) {
    $endpointUrl = '/api/REST/2.0/data/contact/' . $contactId;

    $contact = $this->client->doEloquaApiRequest('GET', $endpointUrl, NULL, $queryParams);
    if (!empty($contact)) {
      return $contact;
    }

    return [];
  }

  /**
   * Update a contact.
   *
   * Updates the contact asset specified by the id parameter
   * https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/op-api-rest-1.0-data-contact-id-put.html.
   *
   * @param int $contactId
   *   Id of the contact.
   * @param array $contact
   *   Following fields are required for performing updates.
   *   - ID
   *   - emailAddress.
   *
   * @return array
   *   Either the user array or empty array.
   */
  public function updateContact($contactId, array $contact) {
    $endpointUrl = '/api/REST/2.0/data/contact/' . $contactId;

    if (!array_key_exists('id', $contact) || !array_key_exists('emailAddress', $contact)) {
      return [];
    }

    $contact = $this->client->doEloquaApiRequest('PUT', $endpointUrl, $contact);
    if (!empty($contact)) {
      return $contact;
    }

    return [];
  }

  /**
   * Delete a contact.
   *
   * Deletes a contact specified by the id parameter.
   *
   * @param int $contactId
   *   Id of the contact.
   *
   * @return array
   *   This is a TODO.
   */
  public function deleteContact($contactId) {
    $endpointUrl = '/api/REST/2.0/data/contact/' . $contactId;

    $contact = $this->client->doEloquaApiRequest('DELETE', $endpointUrl);
    // TODO resolve the correct status to return upstream.
    if (!empty($contact)) {
      return $contact;
    }

    return [];
  }

  /**
   * Create a Dummy User array.
   *
   * @return array
   *   Dummy User array.
   */
  public function dummyContact() {
    $dummyContact = [
      'accessedAt' => '',
      'accountId' => '',
      'accountName' => '',
      'address1' => '',
      'address2' => '',
      'address3' => '',
      'bouncebackDate' => '',
      'businessPhone' => '',
      'city' => '',
      'country' => '',
      'createdAt' => '',
      'createdBy' => '',
      'currentStatus' => '',
      'depth' => '',
      'description' => '',
      'emailAddress' => '',
      'emailFormatPreference' => '',
      'fax' => '',
      'fieldValues' => '',
      'firstName' => '',
      'id' => '',
      'isBounceback' => '',
      'isSubscribed' => '',
      'lastName' => '',
      'mobilePhone' => '',
      'name' => '',
      'permissions' => '',
      'postalCode' => '',
      'province' => '',
      'salesPerson' => '',
      'subscriptionDate' => '',
      'title' => '',
      'type' => '',
      'unsubscriptionDate' => '',
      'updatedAt' => '',
      'updatedBy' => '',
    ];

    return $dummyContact;
  }

}
