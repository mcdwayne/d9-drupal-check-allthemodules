<?php

namespace Drupal\drd\Crypt;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides base encryption functionality.
 *
 * @ingroup drd
 */
class Base implements BaseInterface {

  /**
   * Construct a crypt object.
   *
   * @param array $settings
   *   The settings of the crypt object.
   */
  public function __construct(array $settings = array()) {}

  /**
   * {@inheritdoc}
   */
  public static function getInstance($method, array $settings) {
    self::getMethods();
    $classname = "\\Drupal\\drd\\Crypt\\Method\\$method";
    return new $classname($settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function getMethods($instances = FALSE) {
    $dir = __DIR__ . '/Method';
    $methods = array();
    foreach (['Mcrypt', 'Openssl', 'Tls'] as $item) {
      /* @noinspection PhpIncludeInspection */
      include_once $dir . '/' . $item . '.php';
      $classname = "\\Drupal\\drd\\Crypt\\Method\\$item";
      /* @var BaseMethodInterface $method */
      $method = new $classname();
      if ($method instanceof BaseMethodInterface && $method->isAvailable()) {
        if ($instances) {
          $methods[$method->getLabel()] = $method;
        }
        else {
          $methods[$method->getLabel()] = array(
            'classname' => $classname,
            'cipher' => $method->getCipherMethods(),
          );
        }
      }
    }
    return $methods;
  }

  /**
   * {@inheritdoc}
   */
  public static function countAvailableMethods($remote = NULL) {
    $local = self::getMethods();
    $count = 0;
    foreach ($remote as $key => $value) {
      if (isset($local[$key])) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public static function cryptForm(array $form, FormStateInterface $form_state) {
    $element['drd_crypt'] = array(
      '#type' => 'fieldset',
      '#title' => t('Encryption type'),
    );
    $element['drd_crypt']['description'] = array(
      '#markup' => t('The method how DRD should encrypt the data sent to and received from the remote domains on this core.'),
    );
    $element['drd_crypt']['drd_crypt_type'] = array(
      '#type' => 'select',
      '#default_value' => 'OpenSSL',
    );
    $options = array();
    /* @var string $key */
    /* @var BaseMethodInterface $method */
    foreach (self::getMethods(TRUE) as $key => $method) {
      $options[$key] = $key;
      $condition = array('select#edit-drd-crypt-type' => array('value' => $key));
      $element['drd_crypt'][$key] = array(
        '#type' => 'container',
        '#states' => array(
          'visible' => $condition,
        ),
      );
      $method->settingsForm($element['drd_crypt'][$key], $condition);
    }
    $element['drd_crypt']['drd_crypt_type']['#options'] = $options;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function cryptFormValues(FormStateInterface $form_state) {
    $values = array(
      'crypt' => $form_state->getValue('drd_crypt_type'),
      'cryptsetting' => array(),
    );
    /* @var string $key */
    /* @var BaseMethodInterface $method */
    foreach (self::getMethods(TRUE) as $key => $method) {
      $values['cryptsetting'][$key] = $method->settingsFormValues($form_state);
    }
    return $values;
  }

}
