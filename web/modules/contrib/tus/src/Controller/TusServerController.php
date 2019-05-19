<?php

namespace Drupal\tus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tus\TusServerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Class TusServerController.
 */
class TusServerController extends ControllerBase {

  /**
   * Drupal\tus\TusServerInterface definition.
   *
   * @var \Drupal\tus\TusServerInterface
   */
  protected $tusServer;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * Constructs a new TusServerController object.
   */
  public function __construct(TusServerInterface $tus_server, Serializer $serializer, array $serializer_formats) {
    $this->tusServer = $tus_server;
    $this->serializer = $serializer;
    $this->serializerFormats = $serializer_formats;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    if ($container->hasParameter('serializer.formats') && $container->has('serializer')) {
      $serializer = $container->get('serializer');
      $formats = $container->getParameter('serializer.formats');
    }
    else {
      $formats = ['json'];
      $encoders = [new JsonEncoder()];
      $serializer = new Serializer([], $encoders);
    }

    return new static(
      $container->get('tus.server'),
      $serializer,
      $formats
    );
  }

  /**
   * Gets the format of the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   The format of the request.
   */
  protected function getRequestFormat(Request $request) {
    $format = $request->getRequestFormat();
    if (!in_array($format, $this->serializerFormats)) {
      throw new BadRequestHttpException("Unrecognized format: $format.");
    }
    return $format;
  }

  /**
   * Upload a file via TUS protocol.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @param string $uuid
   *   UUID for the file being uploaded.
   *
   * @return TusPhp\Tus\Server response.
   */
  public function upload(Request $request, $uuid) {
    $metaValues = [];
    $metadata = $request->headers->get('upload-metadata');

    if (!empty($metadata)) {
      $pieces = explode(',', $metadata);
      foreach ($pieces as $piece) {
        list($metaName, $metaValue) = explode(' ', $piece);
        $metaValues[$metaName] = base64_decode($metaValue);
      }
      // If meta isn't passed from the client, we cannot proceed.
      if (empty($metaValues['entityType'])) {
        throw new BadRequestHttpException('TusServerController: POST metadata missing required entityType.');
      }
    }

    // UUID is passed on PATCH and other certain calls, or as the
    // header upload-key on others.
    $uuid = $uuid ?? $request->headers->get('upload-key') ?? '';
    $server = $this->tusServer->getServer($uuid, $metaValues);
    $response = $server->serve();

    return $response->send();
  }

  /**
   * Create the file in Drupal and send response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The created file details.
   */
  public function uploadComplete(Request $request) {
    $response = [];
    $format = $this->getRequestFormat($request);
    $content = $request->getContent();
    $postData = $this->serializer->decode($content, $format);

    // If file isn't passed from the client, we cannot proceed.
    if (empty($postData['file'])) {
      throw new BadRequestHttpException('TusServerController: POST file empty.');
    }

    // Process uploadComplete and create file.
    $response = $this->tusServer->uploadComplete($postData);

    $encoded_response_data = $this->serializer->encode($response, $format);
    return new Response($encoded_response_data);
  }

}
