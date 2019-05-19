<?php namespace Drupal\storychief\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\storychief\Entity\StoryChiefStory;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * MailChimp Webhook controller.
 */
class StoryChiefWebhookController extends ControllerBase {

	/**
	 * {@inheritdoc}
	 */
	public function endpoint() {
		if(!$this->isValidRequest()){
			$response = new JsonResponse('Invalid request', 400);
		}else{
			$data = file_get_contents("php://input");
			$payload = json_decode($data, true);
			$method = $payload['meta']['event'];

			$story = new StoryChiefStory($payload);
			if (method_exists($story, $method)) {
				$return = $story->$method();
				$response = new JsonResponse($this->appendMac($return));
			} else {
				$response = new JsonResponse('Invalid method', 400);
			}
		}

		return $response;
	}

	/**
	 * Validate the request from Story Chief
	 *
	 * @return bool
	 */
	protected function isValidRequest() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') return false;
		$data = file_get_contents("php://input");
		$payload = json_decode($data, true);
		if (empty($payload)) return false;
		if (!isset($payload['meta']['mac'])) return false;

		$givenMac = $payload['meta']['mac'];
		unset($payload['meta']['mac']);
		$calcMac = hash_hmac('sha256', json_encode($payload), \Drupal::config('storychief.settings')->get('api_key'));

		return hash_equals($givenMac, $calcMac);
	}

	/**
	 * Appends HMAC to the response
	 *
	 * @param array $payload
	 * @return array
	 */
	protected function appendMac(array $payload) {
		$payload['mac'] = hash_hmac('sha256', json_encode($payload), \Drupal::config('storychief.settings')->get('api_key'));
		return $payload;
	}
}
