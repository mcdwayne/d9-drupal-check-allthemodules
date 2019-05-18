<?php

namespace CleverReach\BusinessLogic;

use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\BusinessLogic\Utility\Rule;
use CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use InvalidArgumentException;

/**
 *
 */
class Proxy implements ProxyInterface {
  const HTTP_STATUS_CODE_DEFAULT = 400;
  const HTTP_STATUS_CODE_UNAUTHORIZED = 401;
  const HTTP_STATUS_CODE_FORBIDDEN = 403;
  const HTTP_STATUS_CODE_CONFLICT = 409;
  const HTTP_STATUS_CODE_NOT_SUCCESSFUL_FOR_DEFINED_BATCH_SIZE = 413;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\HttpClient
   */
  private $client;
  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private $configService;

  /**
   * Proxy constructor.
   *
   * @throws \InvalidArgumentException
   */
  public function __construct() {
    $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
  }

  /**
   * Check if group with given name exists.
   *
   * @param string $serviceName
   *
   * @return int|null
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function getGroupId($serviceName) {
    $response = $this->call('GET', 'groups.json');
    $allGroups = json_decode($response->getBody(), TRUE);

    if ($allGroups !== NULL && is_array($allGroups)) {
      foreach ($allGroups as $group) {
        if ($group['name'] === $serviceName) {
          return $group['id'];
        }
      }
    }

    return NULL;
  }

  /**
   * Creates new group.
   *
   * @param string $serviceName
   *
   * @throws \InvalidArgumentException
   * @throws HttpRequestException
   *
   * @return int
   *
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function createGroup($serviceName) {
    if (!$serviceName || empty($serviceName)) {
      throw new InvalidArgumentException('Argument null not allowed');
    }

    $argument = ['name' => $serviceName];
    $response = $this->call('POST', 'groups.json', $argument);
    $result = json_decode($response->getBody(), TRUE);
    if (!isset($result['id'])) {
      $message = 'Creation of new group failed. Invalid response body from CR.';
      Logger::logError($message);
      throw new HttpRequestException($message);
    }

    return $result['id'];
  }

  /**
   * Creates new filter on CR API.
   *
   * @param \CleverReach\BusinessLogic\Utility\Filter $filter
   * @param int $integrationID
   *
   * @return array|bool
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function createFilter(Filter $filter, $integrationID) {
    if (!is_numeric($integrationID)) {
      throw new InvalidArgumentException('Integration ID must be numeric!');
    }

    $response = $this->call('POST', 'groups.json/' . $integrationID . '/filters', $filter->toArray());
    $result = json_decode($response->getBody(), TRUE);
    if (!isset($result['id'])) {
      throw new HttpRequestException('Creation of new filter failed. Invalid response body from CR');
    }

    return $result;
  }

  /**
   * Delete filter in CR.
   *
   * @param int $filterID
   * @param int $integrationID
   *
   * @return bool
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function deleteFilter($filterID, $integrationID) {
    if (!is_numeric($filterID) || !is_numeric($integrationID)) {
      throw new InvalidArgumentException('Both arguments must be integers.');
    }

    $response = $this->call('DELETE', 'groups.json/' . $integrationID . '/filters/' . $filterID);

    if ($response->getBody() === 'true') {
      return TRUE;
    }

    // Invalid API response.
    $response = json_decode($response->getBody(), TRUE);

    // Default error message.
    $errorMessage = 'Deleting filter failed. Invalid response body from CR.';

    if (!empty($response['error']['message'])) {
      $errorMessage .= ' Response message: ' . $response['error']['message'];
    }

    $errorCode = $response['error']['code'] ?: self::HTTP_STATUS_CODE_DEFAULT;

    throw new HttpRequestException($errorMessage, $errorCode);
  }

  /**
   * Return all segments from CR.
   *
   * @param int $integrationId
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function getAllFilters($integrationId) {
    $response = $this->call('GET', 'groups.json/' . $integrationId . '/filters');
    $allSegments = json_decode($response->getBody(), TRUE);

    return $this->formatAllFilters($allSegments);
  }

  /**
   * @param array $allSegments
   *
   * @return \CleverReach\BusinessLogic\Utility\Filter[]
   */
  private function formatAllFilters($allSegments) {
    $results = [];
    if (empty($allSegments)) {
      return $results;
    }

    foreach ($allSegments as $segment) {
      if (empty($segment['rules'])) {
        continue;
      }

      $rule = new Rule(
        $segment['rules'][0]['field'], $segment['rules'][0]['logic'],
        $segment['rules'][0]['condition']
      );

      $filter = new Filter($segment['name'], $rule);

      for ($i = 1, $iMax = count($segment['rules']); $i < $iMax; $i++) {

        $rule = new Rule(
        $segment['rules'][$i]['field'], $segment['rules'][$i]['logic'],
        $segment['rules'][$i]['condition']
        );
        $filter->addRule($rule);
      }

      $filter->setId($segment['id']);
      $results[] = $filter;
    }

    return $results;
  }

  /**
   * Get all global attributes ids from CR.
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function getAllGlobalAttributes() {
    $response = $this->call('GET', 'attributes.json');
    $globalAttributes = json_decode($response->getBody(), TRUE);
    $globalAttributesIds = [];

    if ($globalAttributes !== NULL && is_array($globalAttributes)) {
      foreach ($globalAttributes as $globalAttribute) {
        $attributeKey = strtolower($globalAttribute['name']);
        $globalAttributesIds[$attributeKey] = $globalAttribute['id'];
      }
    }

    return $globalAttributesIds;
  }

  /**
   * Create attribute
   * Example attribute:
   * array(
   *   "name" => "FirstName",
   *   "type" => "text",
   *   "description" => "Description",
   *   "preview_value" => "real name",
   *   "default_value" => "Bruce"
   * )
   *
   * @param array $attribute
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function createGlobalAttribute($attribute) {
    try {
      $response = $this->call('POST', 'attributes.json', $attribute);
      $result = json_decode($response->getBody(), TRUE);
    }
    catch (HttpRequestException $ex) {
      // Conflict status code means product search endpoint is already created.
      if ($ex->getCode() === self::HTTP_STATUS_CODE_CONFLICT) {
        Logger::logInfo('Global attribute: ' . $attribute['name'] . ' endpoint already exists on CR.');

        return;
      }

      throw $ex;
    }

    if (!isset($result['id'])) {
      $message = 'Creation of global attribute "' . $attribute['name'] . '" failed. Invalid response body from CR.';
      Logger::logError($message);
      throw new HttpRequestException($message);
    }
  }

  /**
   * Update attribute
   * Example attribute:
   * array(
   *   "type" => "text",
   *   "description" => "Description",
   *   "preview_value" => "real name"
   * )
   *
   * @param int $id
   * @param array $attribute
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function updateGlobalAttribute($id, $attribute) {
    $response = $this->call('PUT', 'attributes.json/' . $id, $attribute);
    $result = json_decode($response->getBody(), TRUE);

    if (!isset($result['id'])) {
      $message = 'Update of global attribute "' . $attribute['name'] . '" failed. Invalid response body from CR.';
      Logger::logError($message);
      throw new HttpRequestException($message);
    }
  }

  /**
   * Register or update product search endpoint
   * Example data:
   * array(
   *   "name" => "My Shop name (http://myshop.com)",
   *   "url" => "http://myshop.com/myendpoint",
   *   "password" => "as243FF3"
   * )
   *
   * @param array $data
   *
   * @throws \InvalidArgumentException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   * @throws HttpAuthenticationException
   */
  public function addOrUpdateProductSearch($data) {
    try {
      $response = $this->call('POST', 'mycontent.json', $data);
      $result = json_decode($response->getBody(), TRUE);
      $result = !is_array($result) ? [] : $result;

      if (!array_key_exists('id', $result)) {
        $message = 'Registration/update of product search endpoint failed. Invalid response body from CR.';
        Logger::logError($message);
        throw new HttpRequestException($message);
      }
    }
    catch (HttpRequestException $ex) {
      // Conflict status code means product search endpoint is already created.
      if ($ex->getCode() === self::HTTP_STATUS_CODE_CONFLICT) {
        Logger::logInfo(
        'Product search endpoint already exists on CR. If you want to update you need to change name and endpoint url.'
        );

        return;
      }

      throw $ex;
    }
  }

  /**
   * Does mass update by sending the whole batch to CleverReach.
   *
   * @param $recipients
   *   array of objects CleverReach\BusinessLogic\DTO\RecipientDTO
   *
   * @throws \InvalidArgumentException
   * @throws HttpBatchSizeTooBigException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function recipientsMassUpdate(array $recipients) {
    $formattedRecipients = $this->prepareRecipientsForApiCall($recipients);

    try {
      $response = $this->upsertPlus($formattedRecipients);
      $this->checkMassUpdateRequestSuccess($response, $recipients);
    }
    catch (HttpRequestException $ex) {
      $batchSize = count($recipients);
      $this->checkMassUpdateBatchSizeValidity($ex, $batchSize);

      throw $ex;
    }
  }

  /**
   * @param array $emails
   *
   * @throws \InvalidArgumentException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   * @throws HttpAuthenticationException
   */
  public function updateNewsletterStatus($emails) {
    $receiversForUpdate = $this->getReceiversForNewsletterStatusUpdate($emails);
    $deactivatedReceivers = $this->upsertPlus($receiversForUpdate);

    $this->checkUpdateNewsletterStatusRecipientsResponse($deactivatedReceivers, $emails);
  }

  /**
   * Deactivates recipients for provided emails.
   *
   * @param array $recipients
   *
   * @throws \InvalidArgumentException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   *
   * @deprecated
   */
  public function deactivateRecipients($recipients) {
    /** @noinspection PhpDeprecationInspection */
    $receiversForDeactivation = $this->getReceiversForDeactivation($recipients);

    if (!empty($receiversForDeactivation)) {
      $deactivatedReceivers = $this->upsertPlus($receiversForDeactivation);

      /** @noinspection PhpDeprecationInspection */
      $this->checkDeactivateRecipientsResponse($deactivatedReceivers, $receiversForDeactivation);
    }
  }

  /**
   * Prepares all recipients in a format needed for API call.
   *
   * @param \CleverReach\BusinessLogic\DTO\RecipientDTO[] $recipientDTOs
   *   array of objects CleverReach\BusinessLogic\DTO\RecipientDTO.
   *
   * @return array
   */
  private function prepareRecipientsForApiCall(array $recipientDTOs) {
    $formattedRecipients = [];

    /** @var \CleverReach\BusinessLogic\DTO\RecipientDTO $recipientDTO */
    foreach ($recipientDTOs as $recipientDTO) {
      /** @var \CleverReach\BusinessLogic\Entity\Recipient $recipientEntity */
      $recipientEntity = $recipientDTO->getRecipientEntity();
      $registered = $recipientEntity->getRegistered();

      $formattedRecipient = [
        'email' => $recipientEntity->getEmail(),
        'registered' => $registered !== NULL ? $registered->getTimestamp() : strtotime('01-01-2010'),
        'source' => $recipientEntity->getSource(),
        'attributes' => $this->formatAttributesForApiCall($recipientEntity->getAttributes()),
        'global_attributes' => $this->formatGlobalAttributesForApiCall($recipientEntity),
        'tags' => $this->formatAndMergeExistingAndTagsForDelete(
            $recipientEntity->getTags(),
            $recipientDTO->getTagsForDelete()
        ),
      ];

      if ($recipientDTO->shouldActivatedFieldBeSent()) {
        // We us activated timestamp for handling both activation and deactivation. When activated timestamp
        // is set to 0, recipient will be inactive in CleverReach. Setting activated to value >0 will reactivate
        // recipient in CleverReach but only if recipient was not deactivated withing CleverReach system.
        if ($recipientEntity->isActive()) {
          $formattedRecipient['activated'] = $recipientEntity->getActivated()->getTimestamp();
        }
        else {
          $formattedRecipient['activated'] = 0;
        }
      }

      if ($recipientDTO->shouldDeactivatedFieldBeSent()) {
        $formattedRecipient['deactivated'] = $recipientEntity->getDeactivated()->getTimestamp();
      }

      if ($recipientDTO->isIncludeOrdersActivated()) {
        $formattedRecipient['orders'] = $this->formatOrdersForApiCall($recipientEntity->getOrders());
      }

      $formattedRecipients[] = $formattedRecipient;
    }

    return $formattedRecipients;
  }

  /**
   * @param array $rawAttributes
   *
   * @return string
   */
  private function formatAttributesForApiCall($rawAttributes) {
    $formattedAttributes = [];

    foreach ($rawAttributes as $key => $value) {
      $formattedAttributes[] = $key . ':' . $value;
    }

    return implode(',', $formattedAttributes);
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\Recipient $recipient
   *
   * @return array
   */
  private function formatGlobalAttributesForApiCall($recipient) {
    $newsletterSubscription = $recipient->getNewsletterSubscription() ? 'yes' : 'no';
    $birthday = $recipient->getBirthday();

    return [
      'firstname' => $recipient->getFirstName(),
      'lastname' => $recipient->getLastName(),
      'salutation' => $recipient->getSalutation(),
      'title' => $recipient->getTitle(),
      'street' => $recipient->getStreet(),
      'zip' => $recipient->getZip(),
      'city' => $recipient->getCity(),
      'company' => $recipient->getCompany(),
      'state' => $recipient->getState(),
      'country' => $recipient->getCountry(),
      'birthday' => ($birthday !== NULL) ? date_format($birthday, 'Y-m-d') : '',
      'phone' => $recipient->getPhone(),
      'shop' => $recipient->getShop(),
      'customernumber' => (string) $recipient->getCustomerNumber(),
      'language' => $recipient->getLanguage(),
      'newsletter' => $newsletterSubscription,
    ];
  }

  /**
   * @param array $orders
   *
   * @return array
   */
  private function formatOrdersForApiCall(array $orders) {
    $formattedOrders = [];

    /** @var \CleverReach\BusinessLogic\Entity\OrderItem $order */
    foreach ($orders as $order) {
      $formattedOrders[] = $this->getOrderFormattedForRequest($order);
    }

    return $formattedOrders;
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\OrderItem $orderItem
   *
   * @return array
   */
  private function getOrderFormattedForRequest($orderItem) {
    $formattedOrder = [];
    $formattedOrder['order_id'] = $orderItem->getOrderId();
    $formattedOrder['product_id'] = (string) $orderItem->getProductId();
    $formattedOrder['product'] = $orderItem->getProduct();

    $dateOfOrder = $orderItem->getStamp();
    $formattedOrder['stamp'] = $dateOfOrder !== NULL ? $dateOfOrder->getTimestamp() : '';

    $formattedOrder['price'] = $orderItem->getPrice();
    $formattedOrder['currency'] = $orderItem->getCurrency();
    $formattedOrder['quantity'] = $orderItem->getAmount();
    $formattedOrder['product_source'] = $orderItem->getProductSource();
    $formattedOrder['brand'] = $orderItem->getBrand();
    $formattedOrder['product_category'] = implode(',', $orderItem->getProductCategory());
    $formattedOrder['attributes'] = $this->formatAttributesForApiCall($orderItem->getAttributes());

    $mailingId = $orderItem->getMailingId();

    if ($mailingId !== NULL) {
      $formattedOrder['mailing_id'] = $mailingId;
    }

    return $formattedOrder;
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $recipientTags
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tagsForDelete
   *
   * @return array
   */
  private function formatAndMergeExistingAndTagsForDelete($recipientTags, $tagsForDelete) {
    $tagsForDelete->markDeleted();

    return $recipientTags->merge($tagsForDelete)->toStringArray();
  }

  /**
   * @param \CleverReach\Infrastructure\Utility\HttpResponse $response
   * @param array $recipientDTOs
   *
   * @throws HttpRequestException
   */
  private function checkMassUpdateRequestSuccess($response, $recipientDTOs) {
    $responseBody = json_decode($response->getBody(), TRUE);
    if ($responseBody === FALSE) {
      $firstRecipient = !empty($recipientDTOs[0]) ? $recipientDTOs[0]->getRecipientEntity()->getEmail() : '';
      $message = 'Upsert of recipients not done for batch starting from recipient id ' . $firstRecipient . '.' .
                'Batch size is ' . count($recipientDTOs) . '.';
      Logger::logError($message);

      throw new HttpRequestException($message);
    }
  }

  /**
   * @param \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $ex
   * @param int $batchSize
   *
   * @throws HttpBatchSizeTooBigException
   */
  private function checkMassUpdateBatchSizeValidity($ex, $batchSize) {
    if ($ex->getCode() === self::HTTP_STATUS_CODE_NOT_SUCCESSFUL_FOR_DEFINED_BATCH_SIZE) {
      Logger::logInfo('Upsert of recipients not done for batch size ' . $batchSize . '.');

      throw new HttpBatchSizeTooBigException('Batch size ' . $batchSize . ' too big for uspert');
    }
  }

  /**
   * Get user information from CleverReach.
   *
   * @param $accessToken
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function getUserInfo($accessToken) {
    try {
      $response = $this->call('GET', 'debug/whoami.json', [], $accessToken);
      $userInfo = json_decode($response->getBody(), TRUE);
      if (!isset($userInfo['id'])) {
        $message = 'Get user information failed. Invalid response body from CR.';
        Logger::logError($message);
        throw new HttpRequestException($message);
      }
    }
    catch (HttpAuthenticationException $ex) {
      // Invalid access token.
      return [];
    }

    return $userInfo;
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\OrderItem $orderItem
   *
   * @throws \InvalidArgumentException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   * @throws HttpAuthenticationException
   */
  public function uploadOrderItem($orderItem) {
    $updatedReceiver = $this->call(
        'POST',
        'groups.json/' . $this->configService->getIntegrationId() . '/receivers/upsert',
        [
          'email' => $orderItem->getRecipientEmail(),
          'orders' => [$this->getOrderFormattedForRequest($orderItem)],
        ]
    );

    $this->checkUploadOrderItemResponse($updatedReceiver, $orderItem->getOrderId());
  }

  /**
   * @param \CleverReach\Infrastructure\Utility\HttpResponse $updatedReceiver
   * @param $orderId
   * @param null $exception
   *
   * @throws HttpRequestException
   */
  private function checkUploadOrderItemResponse($updatedReceiver, $orderId, $exception = NULL) {
    $updatedReceiverDecoded = json_decode($updatedReceiver->getBody(), TRUE);
    if (!isset($updatedReceiverDecoded['id'])) {
      $message = 'Upload of order item with id: ' . $orderId . ' failed. Invalid response body from CR.';
      Logger::logError($message);
      throw new HttpRequestException($message, 0, $exception);
    }
  }

  /**
   * @param array $emails
   *
   * @return array
   */
  private function getReceiversForNewsletterStatusUpdate($emails) {
    $receivers = [];
    foreach ($emails as $email) {
      $receivers[] = [
        'email' => $email,
        'global_attributes' => [
          'newsletter' => 'no',
        ],
      ];
    }

    return $receivers;
  }

  /**
   * @param \CleverReach\Infrastructure\Utility\HttpResponse $response
   * @param array $emails
   *
   * @throws HttpRequestException
   */
  private function checkUpdateNewsletterStatusRecipientsResponse($response, $emails) {
    $responseBody = json_decode($response->getBody(), TRUE);
    if (empty($responseBody) || !is_array($responseBody)) {
      $message = 'Update newsletter status of recipients with emails: ' . implode(
            ',',
            $emails
        ) . ' failed. Invalid response body from CR.';
      Logger::logError($message);
      throw new HttpRequestException($message);
    }
  }

  /**
   * Prepares request data for deactivating recipients.
   *
   * @param array $recipients
   *
   * @return array
   *
   * @deprecated
   */
  private function getReceiversForDeactivation($recipients) {
    $receivers = [];
    foreach ($recipients as $recipient) {
      if (empty($recipient) ||
        (is_array($recipient) && empty($recipient['email']))) {
        continue;
      }

      if (is_array($recipient)) {
        $email = $recipient['email'];
      }
      else {
        $email = $recipient;
      }

      $receivers[] = [
        'email' => $email,
        'activated' => 0,
        'registered' => isset($recipient['registered'])
        ? $recipient['registered']->getTimestamp()
        : strtotime(
                '01-01-2010'
        ),
      ];
    }

    return $receivers;
  }

  /**
   * Checks if operation executed successfully.
   *
   * @param \CleverReach\Infrastructure\Utility\HttpResponse $response
   * @param array $recipients
   *
   * @throws HttpRequestException
   *
   * @deprecated
   */
  private function checkDeactivateRecipientsResponse($response, $recipients) {
    $responseBody = json_decode($response->getBody(), TRUE);
    if (empty($responseBody) || !is_array($responseBody)) {
      $emails = [];
      foreach ($recipients as $recipient) {
        if (!empty($recipient['email'])) {
          $emails[] = $recipient['email'];
        }
      }

      $message = 'Deactivation of recipients with emails: '
                . implode(',', $emails) . ' failed. Invalid response body from CR.';
      Logger::logError($message);

      throw new HttpRequestException($message);
    }
  }

  /**
   * Call http client.
   *
   * @param string $method
   * @param string $endpoint
   * @param array $body
   * @param string $accessToken
   *
   * @return \CleverReach\Infrastructure\Utility\HttpResponse
   *
   * @throws \InvalidArgumentException
   * @throws HttpAuthenticationException
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   */
  public function call($method, $endpoint, $body = [], $accessToken = '') {
    if (empty($accessToken)) {
      $accessToken = $this->configService->getAccessToken();
    }

    if (empty($accessToken)) {
      $errorMessage = 'Missing token. Token is not set in Configuration service.';
      Logger::logError($errorMessage);
      throw new HttpCommunicationException($errorMessage);
    }

    $header = [
      'accept' => 'Accept: application/json',
      'content' => 'Content-Type: application/json',
      'token' => 'Authorization: Bearer ' . $accessToken,
    ];

    $bodyStringToSend = '';
    if (in_array(strtoupper($method), ['POST', 'PUT'])) {
      $bodyStringToSend = json_encode($body);
    }

    $response = $this->getClient()->request(
        $method,
        $this->getBaseUrl() . '/v3/' . $endpoint,
        $header,
        $bodyStringToSend
    );
    $this->validateResponse($response);

    return $response;
  }

  /**
   * Returns an associative array with fields: access_token, expires_in, token_type, scope.
   *
   * @param string $code
   * @param string $redirectUrl
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   */
  public function getAccessToken($code, $redirectUrl) {
    $header = [
      'accept' => 'Accept: application/json',
    ];

    // Assemble POST parameters for the request.
    $postFields = '&grant_type=authorization_code&client_id=' . $this->configService->getClientId()
            . '&client_secret=' . $this->configService->getClientSecret()
            . '&code=' . $code
            . '&redirect_uri=' . urlencode($redirectUrl);

    $response = $this->getClient()->request('POST', $this->getTokenUrl(), $header, $postFields);
    $result = json_decode($response->getBody(), TRUE);

    if (!is_array($result)) {
      $result = [];
    }

    return $result;
  }

  /**
   * Returns auth URL.
   *
   * @param string $redirectUrl
   * @param string $registerData
   * @param array $additionalParams
   *
   * @return string
   */
  public function getAuthUrl($redirectUrl, $registerData, array $additionalParams = []) {
    return $this->getAuthenticationUrl() . '?response_type=code&grant=basic&client_id=' . $this->configService->getClientId()
            . '&redirect_uri=' . urlencode($redirectUrl) . '&registerdata=' . $registerData
            . '&' . http_build_query($additionalParams);
  }

  /**
   * Validate response.
   *
   * @param HttpResponse $response
   *
   * @throws HttpAuthenticationException
   * @throws HttpRequestException
   */
  protected function validateResponse($response) {
    $httpCode = $response->getStatus();
    $body = $response->getBody();
    if ($httpCode !== NULL && ($httpCode < 200 || $httpCode >= 300)) {
      $message = var_export($body, TRUE);

      $error = json_decode($body, TRUE);
      if (is_array($error)) {
        if (isset($error['error']['message'])) {
          $message = $error['error']['message'];
        }

        if (isset($error['error']['code'])) {
          $httpCode = $error['error']['code'];
        }
      }

      Logger::logInfo($message);
      if ($httpCode === self::HTTP_STATUS_CODE_UNAUTHORIZED
        || $httpCode === self::HTTP_STATUS_CODE_FORBIDDEN
      ) {
        throw new HttpAuthenticationException($message, $httpCode);
      }

      throw new HttpRequestException($message, $httpCode);
    }
  }

  /**
   * Calls upsert method for given receivers and returns updated data for receivers.
   *
   * @param array $receivers
   *
   * @return \CleverReach\Infrastructure\Utility\HttpResponse
   *
   * @throws \InvalidArgumentException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  private function upsertPlus($receivers) {
    return $this->call(
        'POST',
        'groups.json/' . $this->configService->getIntegrationId() . '/receivers/upsertplus',
        $receivers
    );
  }

  /**
   * Get http client.
   *
   * @return \CleverReach\Infrastructure\Interfaces\Required\HttpClient
   *
   * @throws \InvalidArgumentException
   */
  private function getClient() {
    if ($this->client === NULL) {
      $this->client = ServiceRegister::getService(HttpClient::CLASS_NAME);
    }

    return $this->client;
  }

  /**
   * @return string
   */
  protected function getBaseUrl() {
    return 'https://rest.cleverreach.com';
  }

  /**
   * @return string
   */
  protected function getAuthenticationUrl() {
    return 'https://rest.cleverreach.com/oauth/authorize.php';
  }

  /**
   * @return string
   */
  protected function getTokenUrl() {
    return 'https://rest.cleverreach.com/oauth/token.php';
  }

}
