<?php

namespace Drupal\condition_pack_date\Plugin\Condition;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\condition_pack_date\Plugin\Condition\Date;

/**
 * Provides a date-sensitive condition for before date.
 *
 * @Condition(
 *   id = "date_before",
 *   label = @Translation("Date, before"),
 * )
 */
class DateBeforeCondition extends DateCondition implements ContainerFactoryPluginInterface, CacheableDependencyInterface {

  /**
   * This class is an exact duplicate of the Date condition, but stored as a separate
   * variable, which provides added flexibility.
   */
  protected $variable = 'date_before';

  public function title() {
   return $this->t('Date, before');
  }

  public function description() {
    return $this->t('Display before the listed date. Enter the date in a format recognized by PHP strtotime().');
  }

}
