<?php

namespace CleverReach\BusinessLogic;

use CleverReach\BusinessLogic\DTO\RecipientDTO;
use CleverReach\BusinessLogic\Entity\AuthInfo;
use CleverReach\BusinessLogic\Entity\OrderItem;
use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\BusinessLogic\Utility\Helper;
use CleverReach\BusinessLogic\Utility\Rule;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\HttpResponse;

/**
 * Class Proxy
 *
 * @package CleverReach\BusinessLogic
 */
class Proxy implements ProxyInterface
{
    const HTTP_STATUS_CODE_DEFAULT = 400;
    const HTTP_STATUS_CODE_UNAUTHORIZED = 401;
    const HTTP_STATUS_CODE_FORBIDDEN = 403;
    const HTTP_STATUS_CODE_CONFLICT = 409;
    const HTTP_STATUS_CODE_NOT_SUCCESSFUL_FOR_DEFINED_BATCH_SIZE = 413;
    /**
     * Instance of HttpClient service.
     *
     * @var HttpClient
     */
    private $client;
    /**
     * Instance of Configuration service.
     *
     * @var Configuration
     */
    private $configService;
    /**
     * API version.
     *
     * @var string
     */
    protected $apiVersion;

    /**
     * Proxy constructor.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct()
    {
        $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $this->apiVersion = 'v3';
    }

    /**
     * Exchanges old access token for new refresh and access tokens.
     *
     * @return AuthInfo
     *   Authentication information object.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function exchangeToken()
    {
        $uri = 'debug/exchange.json';
        $response = $this->call('GET', $uri);
        $body = json_decode($response->getBody(), true);
        if (!isset($body['access_token'], $body['expires_in'], $body['refresh_token'])) {
            $this->logAndThrowHttpRequestException('Token exchange failed. Invalid response from CR.');
        }

        return new AuthInfo($body['access_token'], $body['expires_in'], $body['refresh_token']);
    }

    /**
     * Registers event handler for webhooks and returns call token that will be used
     * as header information for all webhooks.
     *
     * @param array $eventParameters Associative array with URL and verification token. Array keys:
     *   * url: event handler URL
     *   * event: entity name of events
     *   * verify: token for URL verification
     *
     * @return string
     *   If registration succeeds, returns call token.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function registerEventHandler($eventParameters)
    {
        $e = null;
        $response = null;
        try {
            $eventParameters['condition'] = $this->configService->getIntegrationId();

            $response = $this->callWithoutApiVersion('POST', 'hooks/eventhook', $eventParameters);

        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException $e) {
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException $e) {
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $e) {
        }

        if ($e !== null) {
            $response = $this->tryToRegisterNewEventHandler($eventParameters);
            if ($response === null) {
                return '';
            }
        }

        $results = json_decode($response->getBody(), true);
        if (!array_key_exists('call_token', $results) || empty($results['success'])) {
            $this->logAndThrowHttpRequestException('Registration of webhook failed. Invalid response body from CR.');
        }

        return $results['call_token'];
    }

    /**
     * Tries to delete event handler and re-register it.
     *
     * @param array $eventParameters Associative array with URL and verification token. Array keys:
     *   * url: event handler URL
     *   * event: entity name of events
     *   * verify: token for URL verification
     *
     * @return \CleverReach\Infrastructure\Utility\HttpResponse|null
     *   HTTP response of a call if successful; otherwise, null.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    protected function tryToRegisterNewEventHandler($eventParameters)
    {
        try {
            $this->deleteEventHandler($eventParameters['event']);

            return $this->callWithoutApiVersion('POST', 'hooks/eventhook', $eventParameters);
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException $e) {
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException $e) {
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $e) {
        }

        Logger::logError('Cannot register CleverReach event hook! Error: ' . $e->getMessage());

        return null;
    }

    /**
     * Deletes webhooks for Recipient events.
     *
     * @return bool
     *   True if call succeeded; otherwise, false.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function deleteReceiverEvent()
    {
        return $this->deleteEventHandler('receiver');
    }

    /**
     * Removes event handler.
     *
     * @param string $eventName Name of the event.
     *
     * @return bool
     *   True if call succeeded; otherwise, false.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    protected function deleteEventHandler($eventName)
    {
        try {
            $response = $this->callWithoutApiVersion(
                'DELETE',
                'hooks/eventhook/' . $eventName . '?condition=' . $this->configService->getIntegrationId()
            );

            return $response->isSuccessful();
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException $e) {
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException $e) {
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $e) {
        }

        return false;
    }

    /**
     * Calls CleverReach API without version in base url
     *
     * @param string $method HTTP method.
     * @param string $endpoint Endpoint URL.
     * @param array $body Associative array with request data that will be sent as body or query string.
     *
     * @return HttpResponse
     *   HTTP response of a call.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    protected function callWithoutApiVersion($method, $endpoint, array $body = array())
    {
        $apiVersion = $this->apiVersion;
        $this->apiVersion = '';
        $response = $this->call($method, $endpoint, $body);
        $this->apiVersion = $apiVersion;

        return $response;
    }

    /**
     * Returns recipient from CleverReach
     *
     * @param string $groupId List Id
     * @param string $poolId Recipient email or ID
     *
     * @return Recipient
     *   Recipient object with data from CleverReach.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getRecipient($groupId, $poolId)
    {
        $sourceRecipient = $this->getRecipientAsArray($groupId, $poolId);
        if (empty($sourceRecipient['email'])) {
            $this->logAndThrowHttpRequestException(
                'Invalid response body from CleverReach: empty email field on recipient.'
            );
        }

        return Helper::createRecipientEntity($sourceRecipient, $this->configService->getIntegrationName());
    }

    /**
     * Returns recipient from CleverReach as array
     *
     * @param string $groupId CleverReach group ID.
     * @param string $poolId Email or recipient ID.
     *
     * @return array
     *   Recipient fetched from CleverReach.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function getRecipientAsArray($groupId, $poolId)
    {
        $response = $this->call('GET', 'groups.json/' . $groupId . '/receivers/' . $poolId);
        $results = json_decode($response->getBody(), true);
        if (empty($results['email'])) {
            return array();
        }

        return $results;
    }

    /**
     * Deletes recipient from CleverReach.
     *
     * @param string $groupId CleverReach group ID.
     * @param string $poolId Email or recipient ID.
     *
     * @return bool
     *   True if request succeeded; otherwise, false.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function deleteRecipient($groupId, $poolId)
    {
        $response = $this->call('DELETE', 'groups.json/' . $groupId . '/receivers/' . $poolId);
        if ($response->getBody() === 'true') {
            return true;
        }

        return $this->processDeletingFailedResponse($response, 'recipient');
    }

    /**
     * Check if group with given name exists.
     *
     * @param string $serviceName Group name (integration list name).
     *
     * @return int|null
     *   If found returns group ID, otherwise null.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getGroupId($serviceName)
    {
        $response = $this->call('GET', 'groups.json');
        $allGroups = json_decode($response->getBody(), true);

        if ($allGroups !== null && is_array($allGroups)) {
            foreach ($allGroups as $group) {
                if ($group['name'] === $serviceName) {
                    return $group['id'];
                }
            }
        }

        return null;
    }

    /**
     * Creates new group on CleverReach.
     *
     * @param string $name Group name.
     *
     * @return int Group ID on CleverReach.
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     *   Group ID on CleverReach.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function createGroup($name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Argument null not allowed');
        }

        $argument = array('name' => $name);
        $response = $this->call('POST', 'groups.json', $argument);
        $result = json_decode($response->getBody(), true);
        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException('Creation of new group failed. Invalid response body from CR.');
        }

        return $result['id'];
    }

    /**
     * Creates new filter on CleverReach.
     *
     * @param Filter $filter Filter that needs to be created.
     * @param int $integrationID CleverReach integration ID.
     *
     * @return array
     *   Associative array that contains ID of created filter.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function createFilter(Filter $filter, $integrationID)
    {
        if (!is_numeric($integrationID)) {
            throw new \InvalidArgumentException('Integration ID must be numeric!');
        }

        $response = $this->call('POST', 'groups.json/' . $integrationID . '/filters', $filter->toArray());
        $result = json_decode($response->getBody(), true);
        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException(
                'Creation of new filter failed. Invalid response body from CR.'
            );
        }

        return $result;
    }

    /**
     * Delete filter in CleverReach.
     *
     * @param int $filterID Unique identifier for filter.
     * @param int $integrationID CleverReach integration ID.
     *
     * @return bool
     *   On success return true, otherwise false.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function deleteFilter($filterID, $integrationID)
    {
        if (!is_numeric($filterID) || !is_numeric($integrationID)) {
            throw new \InvalidArgumentException('Both arguments must be integers.');
        }

        $response = $this->call('DELETE', 'groups.json/' . $integrationID . '/filters/' . $filterID);

        if ($response->getBody() === 'true') {
            return true;
        }

        return $this->processDeletingFailedResponse($response, 'filter');
    }

    /**
     * Return all segments from CleverReach.
     *
     * @param int $integrationId CleverReach integration ID.
     *
     * @return Filter[]
     *   List of filter objects.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getAllFilters($integrationId)
    {
        $response = $this->call('GET', 'groups.json/' . $integrationId . '/filters');
        $allSegments = json_decode($response->getBody(), true);

        return $this->formatAllFilters($allSegments);
    }

    /**
     * Converts array result fetched from API to filter objects.
     *
     * @param array|null $allSegments Segments retrieved from CleverReach.
     *
     * @return Filter[]
     *   List of filter objects.
     */
    private function formatAllFilters($allSegments)
    {
        $results = array();
        if (empty($allSegments)) {
            return $results;
        }

        foreach ($allSegments as $segment) {
            if (empty($segment['rules'])) {
                continue;
            }

            $rule = new Rule(
                $segment['rules'][0]['field'],
                $segment['rules'][0]['logic'],
                $segment['rules'][0]['condition']
            );

            $filter = new Filter($segment['name'], $rule);

            for ($i = 1, $iMax = count($segment['rules']); $i < $iMax; $i++) {
                $rule = new Rule(
                    $segment['rules'][$i]['field'],
                    $segment['rules'][$i]['logic'],
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
     * Get all global attributes ids from CleverReach.
     *
     * @return array
     *   Associative array where key is attribute name and value is attribute ID.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getAllGlobalAttributes()
    {
        $response = $this->call('GET', 'attributes.json');
        $globalAttributes = json_decode($response->getBody(), true);
        $globalAttributesIds = array();

        if ($globalAttributes !== null && is_array($globalAttributes)) {
            foreach ($globalAttributes as $globalAttribute) {
                $attributeKey = strtolower($globalAttribute['name']);
                $globalAttributesIds[$attributeKey] = $globalAttribute['id'];
            }
        }

        return $globalAttributesIds;
    }

    /**
     * Create global attribute in CleverReach.
     *
     * Request example:
     * array(
     *   "name" => "FirstName",
     *   "type" => "text",
     *   "description" => "Description",
     *   "preview_value" => "real name",
     *   "default_value" => "Bruce"
     * )
     *
     * @param array|null $attribute Attribute that needs to be created.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function createGlobalAttribute($attribute)
    {
        try {
            $response = $this->call('POST', 'attributes.json', $attribute);
            $result = json_decode($response->getBody(), true);
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $ex) {
            // Conflict status code means product search endpoint is already created
            if ($ex->getCode() === self::HTTP_STATUS_CODE_CONFLICT) {
                Logger::logInfo('Global attribute: ' . $attribute['name'] . ' endpoint already exists on CR.');

                return;
            }

            throw $ex;
        }

        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException(
                'Creation of global attribute "' . $attribute['name'] . '" failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Updates global attribute in CleverReach.
     *
     * Request example:
     * array(
     *   "type" => "text",
     *   "description" => "Description",
     *   "preview_value" => "real name"
     * )
     *
     * @param int $id Attribute ID.
     * @param array|null $attribute Attribute data to be updated.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function updateGlobalAttribute($id, $attribute)
    {
        $response = $this->call('PUT', 'attributes.json/' . $id, $attribute);
        $result = json_decode($response->getBody(), true);

        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException(
                'Update of global attribute "' . $attribute['name'] . '" failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Register or update product search endpoint and return ID of registered content.
     *
     * Request data:
     * array(
     *   "name" => "My Shop name (http://myshop.com)",
     *   "url" => "http://myshop.com/myendpoint",
     *   "password" => "as243FF3"
     * )
     *
     * @param array|null $data Associative array with keys name, url and password.
     *
     * @return string
     *   ID of registered content.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function addOrUpdateProductSearch($data)
    {
        try {
            $response = $this->call('POST', 'mycontent.json', $data);
            $result = json_decode($response->getBody(), true);
            $result = !is_array($result) ? array() : $result;

            if (!array_key_exists('id', $result)) {
                $this->logAndThrowHttpRequestException(
                    'Registration/update of product search endpoint failed. Invalid response body from CR.'
                );
            }

            return $result['id'];
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $ex) {
            // Conflict status code means product search endpoint is already created
            if ($ex->getCode() === self::HTTP_STATUS_CODE_CONFLICT) {
                Logger::logInfo('Product search endpoint already exists on CR.');

                return $this->resolveProductSearchEndpointConflict($data);
            }

            throw $ex;
        }
    }

    /**
     * Removes current endpoint and registers new one.
     *
     * Request data:
     * array(
     *   "name" => "My Shop name (http://myshop.com)",
     *   "url" => "http://myshop.com/myendpoint",
     *   "password" => "as243FF3"
     * )
     *
     * @param array|null $data Associative array with keys name, url and password.
     *
     * @return string ID of registered content.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function resolveProductSearchEndpointConflict($data)
    {
        $response = $this->call('GET', 'mycontent.json');
        $result = json_decode($response->getBody(), true);
        $result = !is_array($result) ? array() : $result;

        $message = 'Registration/update of product search endpoint failed. Invalid response body from CR.';
        if (empty($result)) {
            $this->logAndThrowHttpRequestException($message);
        }

        foreach ($result as $content) {
            if ($content['name'] === $data['name'] || $content['url'] === $data['url']) {
                $this->deleteProductSearchEndpoint($content['id']);

                return $this->addOrUpdateProductSearch($data);
            }
        }

        $this->logAndThrowHttpRequestException($message);

        return null;
    }

    /**
     * Delete product search endpoint.
     *
     * @param string $id Content ID.
     *
     * @return bool
     *   On success return true, otherwise false.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function deleteProductSearchEndpoint($id)
    {
        try {
            $this->call('DELETE', 'mycontent.json/' . $id);
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $ex) {
            if ($ex->getCode() !== 404) {
                return false;
            }
        }

        return true;
    }

    /**
     * Does mass update by sending the whole batch to CleverReach.
     *
     * @param array $recipients Array of objects @see \CleverReach\BusinessLogic\DTO\RecipientDTO
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function recipientsMassUpdate(array $recipients)
    {
        $formattedRecipients = $this->prepareRecipientsForApiCall($recipients);

        try {
            $response = $this->upsertPlus($formattedRecipients);
            $this->checkMassUpdateRequestSuccess($response, $recipients);
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $ex) {
            $batchSize = count($recipients);
            $this->checkMassUpdateBatchSizeValidity($ex, $batchSize);

            throw $ex;
        }
    }

    /**
     * Update newsletter status for passed recipient emails.
     *
     * @param array|null $emails Array of recipient emails.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @deprecated
     */
    public function updateNewsletterStatus($emails)
    {
        $receiversForUpdate = $this->getReceiversForNewsletterStatusUpdate($emails);
        $deactivatedReceivers = $this->upsertPlus($receiversForUpdate);

        $this->checkUpdateNewsletterStatusRecipientsResponse($deactivatedReceivers, $emails);
    }

    /**
     * Deactivates recipients.
     *
     * @param Recipient[] $recipients Array of recipient entities.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function deactivateRecipients($recipients)
    {
        $receiversForDeactivation = $this->getReceiversForDeactivation($recipients);

        if (!empty($receiversForDeactivation)) {
            $deactivatedReceivers = $this->upsertPlus($receiversForDeactivation);

            $this->checkDeactivateRecipientsResponse($deactivatedReceivers, $receiversForDeactivation);
        }
    }

    /**
     * Prepares all recipients in a format needed for API call.
     *
     * @param RecipientDTO[] $recipientDTOs Array of objects @see \CleverReach\BusinessLogic\DTO\RecipientDTO
     *
     * @return array
     *   Array of recipients in CleverReach API format.
     */
    private function prepareRecipientsForApiCall(array $recipientDTOs)
    {
        $formattedRecipients = array();

        /** @var RecipientDTO $recipientDTO */
        foreach ($recipientDTOs as $recipientDTO) {
            /** @var Recipient $recipientEntity */
            $recipientEntity = $recipientDTO->getRecipientEntity();
            $registered = $recipientEntity->getRegistered();

            $formattedRecipient = array(
                'email' => $recipientEntity->getEmail(),
                'registered' => $registered !== null ? $registered->getTimestamp() : strtotime('01-01-2010'),
                'source' => $recipientEntity->getSource(),
                'attributes' => $this->formatAttributesForApiCall($recipientEntity->getAttributes()),
                'global_attributes' => $this->formatGlobalAttributesForApiCall($recipientEntity),
                'tags' => $this->formatAndMergeExistingAndTagsForDelete(
                    $recipientEntity->getTags(),
                    $recipientDTO->getTagsForDelete()
                ),
            );

            if ($recipientDTO->shouldActivatedFieldBeSent()) {
                // We use activated timestamp for handling both activation and
                // deactivation. When activated timestamp is set to 0, recipient
                // will be inactive in CleverReach. Setting activated to value > 0
                // will reactivate recipient in CleverReach but only if recipient
                // was not deactivated withing CleverReach system.
                if ($recipientEntity->isActive()) {
                    $formattedRecipient['activated'] = $recipientEntity->getActivated()->getTimestamp();
                } else {
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
     * Formats attributes for CleverReach API call.
     *
     * @param array|null $rawAttributes Associative array ['attribute_name' => 'attribute_value'].
     *
     * @return string
     *   CleverReach API format attr1:val1,attr2:val2
     */
    private function formatAttributesForApiCall($rawAttributes)
    {
        $formattedAttributes = array();

        foreach ($rawAttributes as $key => $value) {
            $formattedAttributes[] = $key . ':' . $value;
        }

        return implode(',', $formattedAttributes);
    }

    /**
     * Formats recipient global attributes to appropriate format for sending.
     *
     * @param Recipient $recipient Recipient object.
     *
     * @return array
     *   CleverReach API format for recipient global attributes.
     */
    private function formatGlobalAttributesForApiCall(Recipient $recipient)
    {
        $newsletterSubscription = $recipient->getNewsletterSubscription() ? 'yes' : 'no';
        $birthday = $recipient->getBirthday();

        return array(
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
            'birthday' => ($birthday !== null) ? date_format($birthday, 'Y-m-d') : '',
            'phone' => $recipient->getPhone(),
            'shop' => $recipient->getShop(),
            'customernumber' => (string)$recipient->getCustomerNumber(),
            'language' => $recipient->getLanguage(),
            'newsletter' => $newsletterSubscription,
        );
    }

    /**
     * Formats list of order objects to CleverReach API format.
     *
     * @param OrderItem[] $orders List of order objects.
     *
     * @return array
     *   CleverReach API format for orders.
     */
    private function formatOrdersForApiCall(array $orders)
    {
        $formattedOrders = array();

        /** @var OrderItem $order */
        foreach ($orders as $order) {
            $formattedOrders[] = $this->getOrderFormattedForRequest($order);
        }

        return $formattedOrders;
    }

    /**
     * Formats single order object to CleverReach API format.
     *
     * @param OrderItem|null $orderItem Order object.
     *
     * @return array
     *   CleverReach API format for single order.
     */
    private function getOrderFormattedForRequest($orderItem)
    {
        $formattedOrder = array();
        $formattedOrder['order_id'] = $orderItem->getOrderId();
        $formattedOrder['product_id'] = (string)$orderItem->getProductId();
        $formattedOrder['product'] = $orderItem->getProduct();

        $dateOfOrder = $orderItem->getStamp();
        $formattedOrder['stamp'] = $dateOfOrder !== null ? $dateOfOrder->getTimestamp() : '';

        $formattedOrder['price'] = $orderItem->getPrice();
        $formattedOrder['currency'] = $orderItem->getCurrency();
        $formattedOrder['quantity'] = $orderItem->getAmount();
        $formattedOrder['product_source'] = $orderItem->getProductSource();
        $formattedOrder['brand'] = $orderItem->getBrand();
        $formattedOrder['product_category'] = implode(',', $orderItem->getProductCategory());
        $formattedOrder['attributes'] = $this->formatAttributesForApiCall($orderItem->getAttributes());

        $mailingId = $orderItem->getMailingId();

        if ($mailingId !== null) {
            $formattedOrder['mailing_id'] = $mailingId;
        }

        return $formattedOrder;
    }

    /**
     * Format and merge tags that already exist with tags for delete.
     *
     * @param \CleverReach\BusinessLogic\Entity\TagCollection|null $recipientTags Existing tags.
     * @param \CleverReach\BusinessLogic\Entity\TagCollection|null $tagsForDelete Tags for delete.
     *
     * @return array
     *   Array representation of tag collection.
     */
    private function formatAndMergeExistingAndTagsForDelete($recipientTags, $tagsForDelete)
    {
        $tagsForDelete->markDeleted();

        return $recipientTags->merge($tagsForDelete)->toStringArray();
    }

    /**
     * Validates if update was successful.
     *
     * @param HttpResponse|null $response Http response object.
     * @param array|null $recipientDTOs List of recipient DTOs.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function checkMassUpdateRequestSuccess($response, $recipientDTOs)
    {
        $responseBody = json_decode($response->getBody(), true);
        if ($responseBody === false) {
            $firstRecipient = !empty($recipientDTOs[0]) ? $recipientDTOs[0]->getRecipientEntity()->getEmail() : '';
            $this->logAndThrowHttpRequestException(
                'Upsert of recipients not done for batch starting from recipient id ' . $firstRecipient . '. '
                . 'Batch size is ' . count($recipientDTOs) . '.'
            );
        }
    }

    /**
     * Checks if request is successful for passed batch size.
     *
     * @param \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException $ex Exception object.
     * @param int $batchSize Test batch size.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     */
    private function checkMassUpdateBatchSizeValidity($ex, $batchSize)
    {
        if ($ex->getCode() === self::HTTP_STATUS_CODE_NOT_SUCCESSFUL_FOR_DEFINED_BATCH_SIZE) {
            Logger::logInfo('Upsert of recipients not done for batch size ' . $batchSize . '.');

            throw new \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException(
                'Batch size ' . $batchSize . ' too big for upsert'
            );
        }
    }

    /**
     * Get user information from CleverReach.
     *
     * @param string $accessToken User access token.
     *
     * @return array
     *   Associative array that contains CleverReach user information.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getUserInfo($accessToken)
    {
        try {
            $response = $this->call('GET', 'debug/whoami.json', array(), $accessToken);
            $userInfo = json_decode($response->getBody(), true);
            if (!isset($userInfo['id'])) {
                $this->logAndThrowHttpRequestException('Get user information failed. Invalid response body from CR.');
            }
        } catch (\CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException $ex) {
            // Invalid access token
            return array();
        }

        return $userInfo;
    }

    /**
     * Uploads order item to CleverReach.
     *
     * @param OrderItem|null $orderItem Order item that needs to be uploaded.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function uploadOrderItem($orderItem)
    {
        $updatedReceiver = $this->call(
            'POST',
            'groups.json/' . $this->configService->getIntegrationId() . '/receivers/upsert',
            array(
                array(
                    'email' => $orderItem->getRecipientEmail(),
                    'tags' => array((string)SpecialTag::buyer()),
                    'orders' => array($this->getOrderFormattedForRequest($orderItem)),
                )
            )
        );

        $this->checkUploadOrderItemResponse($updatedReceiver, $orderItem->getOrderId());
    }

    /**
     * Uploads order item to CleverReach.
     *
     * @param HttpResponse|null $updatedReceiver Http response.
     * @param string $orderId Order ID.
     * @param \Throwable|null $exception Exception.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function checkUploadOrderItemResponse($updatedReceiver, $orderId, $exception = null)
    {
        $updatedReceiverDecoded = json_decode($updatedReceiver->getBody(), true);
        if (!isset($updatedReceiverDecoded[0]['id'])) {
            $this->logAndThrowHttpRequestException(
                'Upload of order item with id: ' . $orderId . ' failed. Invalid response body from CR.',
                0,
                $exception
            );
        }
    }

    /**
     * Gets CleverReach recipient status format.
     *
     * @param array|null $emails List of recipient emails for update.
     *
     * @return array
     *   CleverReach format for newsletter status update.
     */
    private function getReceiversForNewsletterStatusUpdate($emails)
    {
        $receivers = array();
        foreach ($emails as $email) {
            $receivers[] = array(
                'email' => $email,
                'global_attributes' => array(
                    'newsletter' => 'no',
                ),
            );
        }

        return $receivers;
    }

    /**
     * Validates response for newsletter status update.
     *
     * @param HttpResponse|null $response Http response object.
     * @param array|null $emails List of recipient emails for update.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function checkUpdateNewsletterStatusRecipientsResponse($response, $emails)
    {
        $responseBody = json_decode($response->getBody(), true);
        if (empty($responseBody) || !is_array($responseBody)) {
            $this->logAndThrowHttpRequestException(
                'Update newsletter status of recipients with emails: ' . implode(',', $emails)
                . ' failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Prepares request data for deactivating recipients.
     *
     * @param Recipient[] $recipients List of recipients.
     *
     * @return array
     *   CleverReach API format for recipient deactivation.
     */
    private function getReceiversForDeactivation($recipients)
    {
        $receivers = array();
        foreach ($recipients as $recipient) {
            $receivers[] = array(
                'email' => $recipient->getEmail(),
                'activated' => 0,
                'registered' => $recipient->getRegistered()->getTimestamp(),
                'tags' => $recipient->getTags()->toStringArray(),
                'global_attributes' => array(
                    'newsletter' => 'no',
                ),
            );
        }

        return $receivers;
    }

    /**
     * Checks if operation executed successfully.
     *
     * @param HttpResponse|null $response Http response object.
     * @param array|null $recipients List of recipients.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function checkDeactivateRecipientsResponse($response, $recipients)
    {
        $responseBody = json_decode($response->getBody(), true);
        if ((empty($responseBody) || !is_array($responseBody)) && $response->getStatus() !== 200) {
            $emails = array();
            foreach ($recipients as $recipient) {
                if (!empty($recipient['email'])) {
                    $emails[] = $recipient['email'];
                }
            }

            $this->logAndThrowHttpRequestException(
                'Deactivation of recipients with emails: ' . implode(',', $emails)
                . ' failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Call HTTP client.
     *
     * @inheritdoc
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function call($method, $endpoint, $body = array(), $accessToken = '')
    {
        $accessToken = $this->getValidAccessToken($accessToken);

        if (empty($accessToken)) {
            $errorMessage = 'Missing token. Token is not set in Configuration service.';
            Logger::logError($errorMessage);

            throw new \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException($errorMessage);
        }

        $header = array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
            'token' => 'Authorization: Bearer ' . $accessToken,
        );

        $bodyStringToSend = '';
        if (in_array(strtoupper($method), array('POST', 'PUT'))) {
            $bodyStringToSend = json_encode($body);
        }

        $response = $this->getClient()->request($method, $this->getBaseUrl() . $endpoint, $header, $bodyStringToSend);

        $this->validateResponse($response);

        return $response;
    }

    /**
     * Returns authentication information (AuthInfo).
     *
     * @param string $code Access code.
     * @param string $redirectUrl Url for callback.
     *
     * @return AuthInfo Authentication information object.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Exceptions\BadAuthInfoException
     */
    public function getAuthInfo($code, $redirectUrl)
    {
        $header = array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
        );

        // Assemble POST parameters for the request.
        $postFields = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->configService->getClientId(),
            'client_secret' => $this->configService->getClientSecret(),
            'code' => $code,
            'redirect_uri' => urlencode($redirectUrl),
        );

        $response = $this->getClient()->request('POST', $this->getTokenUrl(), $header, json_encode($postFields));
        $result = json_decode($response->getBody(), true);
        if (isset($result['error'])
            || empty($result['access_token'])
            || empty($result['expires_in'])
            || empty($result['refresh_token'])
        ) {
            throw new \CleverReach\Infrastructure\Exceptions\BadAuthInfoException(
                isset($result['error_description']) ? $result['error_description'] : ''
            );
        }

        return new AuthInfo($result['access_token'], $result['expires_in'], $result['refresh_token']);
    }

    /**
     * Returns auth URL.
     *
     * @param string $redirectUrl Redirect URL.
     * @param string $registerData Data for user registration on CleverReach.
     * @param array $additionalParams Additional params in query.
     *
     * @return string
     *   CleverReach auth url.
     */
    public function getAuthUrl($redirectUrl, $registerData = '', array $additionalParams = array())
    {
        $url = $this->getAuthenticationUrl()
            . '?response_type=code'
            . '&grant=basic'
            . '&client_id=' . $this->configService->getClientId()
            . '&redirect_uri=' . urlencode($redirectUrl);

        if (!empty($registerData)) {
            $url .= '&registerdata=' . $registerData;
        }

        if (!empty($additionalParams)) {
            $url .= '&' . http_build_query($additionalParams);
        }

        return $url;
    }

    /**
     * Validate response.
     *
     * @param HttpResponse|null $response Http response.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    protected function validateResponse($response)
    {
        $httpCode = $response->getStatus();
        $body = $response->getBody();
        if ($httpCode !== null && ($httpCode < 200 || $httpCode >= 300)) {
            $message = var_export($body, true);

            $error = json_decode($body, true);
            if (is_array($error)) {
                if (isset($error['error']['message'])) {
                    $message = $error['error']['message'];
                }

                if (isset($error['error']['code'])) {
                    $httpCode = $error['error']['code'];
                }
            }

            if ($httpCode === self::HTTP_STATUS_CODE_UNAUTHORIZED || $httpCode === self::HTTP_STATUS_CODE_FORBIDDEN) {
                Logger::logInfo($message);

                throw new \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException(
                    $message, $httpCode
                );
            }

            $this->logAndThrowHttpRequestException($message, $httpCode);
        }
    }

    /**
     * Calls upsert method for given receivers and returns updated data.
     *
     * @param array|null $receivers CleverReach receivers.
     *
     * @return \CleverReach\Infrastructure\Utility\HttpResponse
     *   Http response object.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    private function upsertPlus($receivers)
    {
        return $this->call(
            'POST',
            'groups.json/' . $this->configService->getIntegrationId() . '/receivers/upsertplus',
            $receivers
        );
    }

    /**
     * Get instance of http client.
     *
     * @return HttpClient
     *   Http client object.
     *
     * @throws \InvalidArgumentException
     */
    private function getClient()
    {
        if ($this->client === null) {
            $this->client = ServiceRegister::getService(HttpClient::CLASS_NAME);
        }

        return $this->client;
    }

    /**
     * Gets CleverReach REST API base url.
     *
     * @return string
     *   Base url.
     */
    protected function getBaseUrl()
    {
        $baseUrl = 'https://rest.cleverreach.com/';

        return !empty($this->apiVersion) ? $baseUrl . $this->apiVersion . '/' : $baseUrl;
    }

    /**
     * Gets CleverReach REST API authentication url.
     *
     * @return string
     *   Authentication url.
     */
    protected function getAuthenticationUrl()
    {
        return 'https://rest.cleverreach.com/oauth/authorize.php';
    }

    /**
     * Gets CleverReach REST API token url.
     *
     * @return string
     *   Token url.
     */
    protected function getTokenUrl()
    {
        return 'https://rest.cleverreach.com/oauth/token.php';
    }

    /**
     * Process deleting failed response.
     *
     * @param HttpResponse|null $response Http response.
     * @param string $entity Entity code.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function processDeletingFailedResponse($response, $entity)
    {
        // invalid API response
        $response = json_decode($response->getBody(), true);

        // default error message
        $errorMessage = 'Deleting ' . $entity . ' failed. Invalid response body from CR.';

        if (!empty($response['error']['message'])) {
            $errorMessage .= ' Response message: ' . $response['error']['message'];
        }

        $errorCode = $response['error']['code'] ?: self::HTTP_STATUS_CODE_DEFAULT;

        $this->logAndThrowHttpRequestException($errorMessage, $errorCode);
    }

    /**
     * Retrieves valid access token.
     *
     * @param string $token Current access token.
     *
     * @return string
     *   Valid access token.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     */
    private function getValidAccessToken($token)
    {
        if (empty($token)) {
            // if token is not given, try to get it from config and validate expiration
            $token = $this->configService->getAccessToken() ?: '';
            if ($this->configService->isAccessTokenExpired()) {
                try {
                    $result = $this->refreshAccessToken();
                } catch (\CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException $e) {
                    $this->configService->setRefreshToken(null);

                    throw $e;
                }

                $token = $result['access_token'];
                $this->configService->setAccessToken($token);
                $this->configService->setRefreshToken($result['refresh_token']);
                $this->configService->setAccessTokenExpirationTime($result['expires_in']);
            }

            if (empty($token)) {
                $message = 'Access token missing';
                Logger::logError($message);

                throw new \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException($message);
            }
        }

        return $token;
    }

    /**
     * Refreshes access token.
     *
     * @return array
     *   An associative array with tokens
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    private function refreshAccessToken()
    {
        $refreshToken = $this->configService->getRefreshToken();

        if (empty($refreshToken)) {
            throw new \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException(
                'Refresh token not found! User must re-authenticate.'
            );
        }

        $payload = '&grant_type=refresh_token&refresh_token=' . $refreshToken;
        $identity = base64_encode($this->configService->getClientId() . ':' . $this->configService->getClientSecret());
        $header = array('Authorization: Basic ' . $identity);

        $response = $this->getClient()->request('POST', $this->getTokenUrl(), $header, $payload);

        if (!$response->isSuccessful()) {
            throw new \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException(
                'Refresh token expired! User must re-authenticate.'
            );
        }

        $result = json_decode($response->getBody(), true);

        if (empty($result['access_token']) || empty($result['expires_in'])) {
            throw new \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException(
                'CleverReach API invalid response.'
            );
        }

        return $result;
    }

    /**
     * Logs provided message as error and throws exception.
     *
     * @param string $message Message to be logged and put to exception.
     * @param int $code Status code.
     * @param null $previousException
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function logAndThrowHttpRequestException($message, $code = 0, $previousException = null)
    {
        Logger::logError($message);

        throw new \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException(
            $message,
            $code,
            $previousException
        );
    }
}
