<?php

namespace Drupal\oauth2\ResponseType;

use AuthBucket\OAuth2\ResponseType\ResponseTypeHandlerFactory as BaseResponseTypeHandlerFactory;
use AuthBucket\OAuth2\TokenType\TokenTypeHandlerFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\user\UserAuthInterface;

/**
 * OAuth2 response type handler factory implemention.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class ResponseTypeHandlerFactory extends BaseResponseTypeHandlerFactory {

  public function __construct(
    UserAuthInterface $userAuth,
    TypedDataManagerInterface $typedDataManager,
    EntityManagerInterface $entityManager,
    TokenTypeHandlerFactoryInterface $tokenTypeHandlerFactory,
    array $classes = []
  ) {
    $tokenStorage = '__todo__';

    $validator = $typedDataManager->getValidator();

    $modelManagerFactory = '__todo__';

    parent::__construct(
      $tokenStorage,
      $validator,
      $modelManagerFactory,
      $tokenTypeHandlerFactory,
      $classes
    );
  }

}
