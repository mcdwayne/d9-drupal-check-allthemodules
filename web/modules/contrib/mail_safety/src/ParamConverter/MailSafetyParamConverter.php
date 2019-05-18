<?php
namespace Drupal\mail_safety\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\mail_safety\Controller\MailSafetyController;

use Symfony\Component\Routing\Route;

/**
 * Class MailSafetyParamConverter.
 *
 * @package Drupal\mail_safety\ParamConverter
 */
class MailSafetyParamConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($mail_id, $definition, $name, array $defaults) {
    return MailSafetyController::load($mail_id);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'mail_safety');
  }

}
