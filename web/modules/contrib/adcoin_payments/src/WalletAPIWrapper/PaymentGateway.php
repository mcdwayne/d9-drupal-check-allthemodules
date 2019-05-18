<?php

namespace Drupal\adcoin_payments\WalletAPIWrapper;
use Drupal\adcoin_payments\WalletAPIWrapper\ClientException;

/**
 * Wrapper class for the payment gateway.
 * Compliant to WordPress' code standards.
 */
class PaymentGateway
{
    /**
     * API key
     *
     * @var string $apiKey
     */
    private $apiKey;

    /**
     * Creates a new PaymentGateway object
     *
     * @param string $apiKey The wallet API key used to authenticate payment
     *                       gateway requests.
     *
     * @throws Exception If the provided API key is not a valid string.
     */
    public function __construct($apiKey)
    {
        // Validate whether the given API key is a string
        if (!is_string($apiKey)) {
            throw new \InvalidArgumentException(
                'Constructor of PaymentGateway should be given an API key as string.'
            );
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Requests for a new payment to be opened on the payment gateway.
     *
     * @param float  $amount      The amount of ACC to be paid.
     * @param string $description Text to be shown on the payment page.
     * @param string $redirectUrl URL to redirect the browser to once the payment
     *                            has been completed.
     * @param string $webhookUrl  The webhook URL that the the payment gateway
     *                            will call once the payment is complete and the
     *                            transaction is complete.
     * @param array  $metadata    JSON compatible array of metadata to send to
     *                            the webhook. e.g:
     *                            ['name' => 'TestUser']
     *
     * @throws Exception If an error occurred while executing this method.
     *
     * @return array $result The payment that is created, the user should be
     *                       redirected to links.paymentUrl
     */
    public function openPayment($amount, $description, $redirectUrl, $webhookUrl, $metadata = array())
    {
        if (!is_float($amount)) {
            throw new \InvalidArgumentException('$amount should be a float');
        }

        if (!filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('$redirectUrl should be a URL');
        }

        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('$webhookUrl should be a URL');
        }

        if (!is_string($description)) {
            throw new \InvalidArgumentException('$description should be a string');
        }

        if (!is_array($metadata)) {
            throw new \Exception('$metadata should be a array');
        }

        $response = $this->call('/payments', array(
            'amount' => $amount,
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $webhookUrl,
            'description' => $description,
            'metadata' => json_encode($metadata),
        ), 'POST');

        return $response;
    }

    /**
     * Get all payments
     *
     * @return array $payments
     */
    public function getPayments()
    {
        return $this->call('/payments');
    }

    /**
     * Fetch basic account information.
     *
     * @return array Account information.
     */
    public function getAccountInformation() {
        return $this->call('/me');
    }

    /**
     * Get single payment
     *
     * @param string $id Payment ID
     *
     * @return array $payment
     */
    public function getPayment($id)
    {
        if (!is_string($id)) {
            throw new \InvalidArgumentException('$id should be a string');
        }

        return $this->call('/payments/'.$id);
    }

    /**
     * Call the API
     *
     * @param string $url        URL to call
     * @param array  $parameters Parameters to send
     * @param string $method     Method to use
     *
     * @return array $response
     */
    private function call($url, array $parameters = array(), $method = 'GET')
    {
		if (function_exists('wp_remote_post')) {
			// Use WordPress' HTTP API
			$response = wp_remote_post(
				'https://wallet.getadcoin.com/api'.$url,
				array(
					'method'      => $method,
					'timeout'     => 20,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array('X-AUTH-TOKEN' => $this->apiKey),
					'body'        => $parameters,
					'cookies'     => array()
				)
			);
			$body = wp_remote_retrieve_body($response);
			$status = wp_remote_retrieve_response_code($response);

		} else {
			// Use CURL
			$headers = [
				'X-AUTH-TOKEN: '.$this->apiKey,
			];

			$curl = curl_init('https://wallet.getadcoin.com/api'.$url);

			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			if ($method === 'POST') {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
			}

			$body = curl_exec($curl);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		}

		// Check HTTP status
		if ($status < 200 || $status >= 300) {
			throw new ClientException($body);
		}

		// Decode and return response body
		return json_decode($body, true);
    }
}
