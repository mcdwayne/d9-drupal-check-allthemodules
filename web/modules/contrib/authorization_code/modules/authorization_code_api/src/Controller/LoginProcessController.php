<?php

namespace Drupal\authorization_code_api\Controller;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\authorization_code\Exceptions\InvalidCodeException;
use Drupal\authorization_code\Exceptions\UserNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controller for the login process api.
 */
class LoginProcessController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  private $serializer;

  /**
   * {@inheritdoc}
   */
  public function __construct(SerializerInterface $serializer, RendererInterface $renderer, LoggerInterface $logger) {
    $this->serializer = $serializer;
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serializer'),
      $container->get('renderer'),
      $container->get('logger.channel.authorization_code')
    );
  }

  /**
   * Starts the login process.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function startLoginProcess(Request $request, LoginProcess $login_process): JsonResponse {
    $content = $this->getContent($request, ['identifier']);

    return $this->executeInPrivateRenderContext(function () use ($login_process, $content) {
      try {
        $login_process->startLoginProcess($content['identifier']);
      }
      catch (UserNotFoundException $e) {
        $this->logger->warning("Failed login attempt - The @label login process couldn't find a user identified by %identifier.", [
          '@label' => $login_process->label(),
          '%identifier' => $content['identifier'],
        ]);
      }

      return new JsonResponse(['message' => 'Authorization code was sent.']);
    });
  }

  /**
   * Starts the login process.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function completeLoginProcess(Request $request, LoginProcess $login_process): JsonResponse {
    $content = $this->getContent($request, ['identifier', 'code']);

    return $this->executeInPrivateRenderContext(function () use ($login_process, $content) {
      try {
        $login_process->completeLoginProcess($content['identifier'], $content['code']);
        return new JsonResponse(['message' => 'You are now logged in.']);
      }
      catch (UserNotFoundException $e) {
        $this->logger->warning("Failed login attempt - The @label login process couldn't find a user identified by %identifier.", [
          '@label' => $login_process->label(),
          '%identifier' => $content['identifier'],
        ]);
        throw new HttpException(401, 'Invalid authorization code', $e);
      }
      catch (InvalidCodeException $e) {
        $this->logger->warning("Failed login attempt - Invalid authorization code for @label login process and a user identified by %identifier.", [
          '@label' => $login_process->label(),
          '%identifier' => $content['identifier'],
        ]);
        throw new HttpException(401, 'Invalid authorization code', $e);
      }
    });
  }

  /**
   * Executes a function in a private render context.
   *
   * It is required to prevent Drupal from false flagging this response with an
   * early rendering error.
   *
   * @param callable $callback
   *   The function to execute in the private render context.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  private function executeInPrivateRenderContext(callable $callback): JsonResponse {
    return $this->renderer->executeInRenderContext(new RenderContext(), $callback);
  }

  /**
   * Decodes and validates the request content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string[] $required_parameters
   *   The required parameters.
   *
   * @return array
   *   The request content after it has been decoded.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  private function getContent(Request $request, array $required_parameters): array {
    if ($this->serializer instanceof DecoderInterface) {
      $content = $this->serializer->decode($request->getContent(), $request->getContentType());
    }
    else {
      throw new HttpException(500, $this->t('The appropriate DecoderInterface was not found.'));
    }

    foreach ($required_parameters as $parameter) {
      if (!isset($content[$parameter])) {
        throw new HttpException(400, $this->t('Parameter `@parameter` is missing.', ['@parameter' => $parameter]));
      }
    }

    return $content;
  }

}
