<?php

namespace Drupal\invite\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\invite\Entity\Invite;
use Symfony\Component\Routing\Route;

/**
 * Class InviteParamConverter.
 */
class InviteParamConverter implements ParamConverterInterface {

  /**
   * Applies function.
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'reg_code');
  }

  /**
   * Converts reg_code to invite.
   */
  public function convert($reg_code, $definition, $name, array $defaults) {
    $invite = \Drupal::entityQuery('invite')
      ->condition('reg_code', $reg_code)
      ->execute();
    return Invite::load(reset($invite));
  }

}
