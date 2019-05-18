<?php

/**
 * @file
 * Contains \Drupal\opcachectl\Controller\OpcacheCtlController.
 */

namespace Drupal\opcachectl\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * OPcache control.
 */
class OpcacheCtlController extends ControllerBase {

	/**
	 * Drupal\Core\Logger\LoggerChannelFactory definition.
	 *
	 * @var \Drupal\Core\Logger\LoggerChannelFactory
	 */
	protected $logger;


	/**
	 * List of IP addresses / network addresses allowed to access opcachectl sites.
	 *
	 * @var array
	 */
	protected $authorizedAddresses = [];

	/**
	 * Generate token via
	 * #> cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1
	 *
	 * settings.php:
	 * $config['opcachectl']['reset_token'] = 'somerandomvalue';
	 *
	 * @var string
	 */
	protected $resetToken;


	/**
	 * Constructs a new OpcacheCtlController object.
	 *
	 * @param LoggerChannelFactory   $logger
	 * @param ConfigFactoryInterface $config_factory
	 */
	public function __construct(LoggerChannelFactory $logger, ConfigFactoryInterface $config_factory) {
		$this->logger = $logger->get('opcachectl');
		$this->resetToken = trim($config_factory->get('opcachectl')->get('reset_token'));
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('logger.factory'),
			$container->get('config.factory')
		);
	}

	/**
	 * Callback for the OPcache statistics page.
	 *
	 * @return string
	 *   The page output.
	 */
	public function settingsPage() {
		$output  = [];

		return $output;
	}


	protected function createControlResponse(array $data, $status = Response::HTTP_OK) {
		$data['host'] = gethostname();
		$data['address'] = $_SERVER['SERVER_ADDR'];
		$data['timestamp'] = $_SERVER['REQUEST_TIME_FLOAT'];
		return new JsonResponse($data, $status);
	}


	public function controlGet(Request $request) {
		if (!function_exists('opcache_get_status')) {
			return $this->createControlResponse(['error' => 'PHP OPcache not enabled'], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
		return $this->createControlResponse(['status' => opcache_get_status(FALSE)]);
	}


	public function controlPurge(Request $request) {
		if (!function_exists('opcache_get_status')) {
			return $this->createControlResponse(['error' => 'PHP OPcache not enabled'], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
		$request_path_without_token = dirname($request->getPathInfo());

		$this->logger->debug('OPcache reset call via ' . $request_path_without_token . ' on host ' . gethostname());
		if (opcachectl_reset()) {
			return $this->createControlResponse(['status' => opcache_get_status(FALSE)]);
		} else {
			return $this->createControlResponse(['error' => 'opcache_reset() Failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}


}
