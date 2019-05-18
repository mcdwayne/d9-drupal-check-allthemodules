<?php

namespace Drupal\scheduling\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Access\AccessResult;

class Scheduling {

  const BEGINNING_OF_ALL_TIME = '1970-01-01T00:00:01';

  const END_OF_ALL_TIME = '2037-01-19T23:59:59';

  /**
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $now;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Scheduling constructor.
   */
  public function __construct(\Drupal\Core\Session\AccountProxyInterface $account) {
    $this->account = $account;
    $this->now = new DrupalDateTime();
  }


  public function getStatus($mode, $values, $ignore_bypass_permission = FALSE) {

    if ($mode === 'published' || ($this->account->hasPermission('bypass scheduling access') && !$ignore_bypass_permission)) {
      return TRUE;
    }

    switch ($mode) {
      case 'range':
        return $this->getRangeStatus($values);
      case 'recurring':
        return $this->getRecurringStatus($values);
      default:
        return FALSE;
    }

  }

  public function getNextStatusChange($values) {
    return new DrupalDateTime('2018-03-01T10:10:10+01:00');
  }

  public function getNextStatusChangeInSeconds($mode, $values) {
    switch ($mode) {
      case 'range':
        return $this->getRangeNextStatusChangeInSeconds($values);
      case 'recurring':
        return $this->getRecurringNextStatusChangeInSeconds($values);
      default:
        return null;
    }
  }

  protected function getRangeStatus($items) {
    foreach ($items as $item) {
      if ($value = $item->value) {
        if ($item->value['mode'] === 'range' && $this->fromDateTime($value['from']) <= $this->now && $this->now <= $this->toDateTime($value['to'])) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  protected function getRecurringStatus($items) {
    $day = substr($this->now->format('D'), 0, 2);
    foreach ($items as $item) {
      if ($value = $item->value) {
        if ($item->value['mode'] === 'recurring' && in_array($day, $value['weekdays'], TRUE)) {
          $from = $this->fromDateTime($value['from']);
          $to = $this->toDateTime($value['to']);
          if ($from->format('His') <= $this->now->format('His') && $this->now->format('His') <= $to->format('His')) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  protected function getRangeNextStatusChangeInSeconds($items) {
    $expiries = [];

    foreach ($items as $item) {
      if ($item->value['mode'] === 'range' && $value = $item->value) {
        $from = $this->fromDateTime($value['from']);
        $to = $this->toDateTime($value['to']);
        if (!empty($value['from']) && $this->now->getTimestamp() < $from->getTimestamp()) {
          $expiries[] = $from->getTimestamp() - $this->now->getTimestamp();
        } else if (!empty($value['to']) && $this->now->getTimestamp() < $to->getTimestamp()) {
          $expiries[] = $to->getTimestamp() - $this->now->getTimestamp();
        }
      }
    }

    return count($expiries) > 0 ? min($expiries) : null;
  }

  protected function getRecurringNextStatusChangeInSeconds($items) {

    $expiries = [];
    $day = substr($this->now->format('D'), 0, 2);

    foreach ($items as $item) {
      if (($value = $item->value) && $item->value['mode'] === 'recurring') {
        $from = $this->fromDateTime($value['from']);
        $to = $this->toDateTime($value['to']);

        foreach ($value['weekdays'] as $weekday) {
          if ($weekday === $day) {
            if ($from->format('His') > $this->now->format('His')) {
              $expiries[] = $from->format('His') - $this->now->format('His');
            } else if ($from->format('His') <= $this->now->format('His') && $this->now->format('His') < $to->format('His')) {
              $expiries[] = $to->format('His') - $this->now->format('His');
            }
          } else if ($weekday !== 0) {

            $mapping = [
              'Su' => 'sunday',
              'Mo' => 'monday',
              'Tu' => 'tuesday',
              'We' => 'wednesday',
              'Th' => 'thursday',
              'Fr' => 'friday',
              'Sa' => 'saturday',
            ];

            $weekday_intermediary = DrupalDateTime::createFromFormat('dmY', $this->now->format('dmY'));
            $weekday_intermediary->modify('next ' . $mapping[$weekday]);
            $weekday_from = DrupalDateTime::createFromFormat('dmYHis', $weekday_intermediary->format('dmY') . $from->format('His'));
            $weekday_to = DrupalDateTime::createFromFormat('dmYHis', $weekday_intermediary->format('dmY') . $to->format('His'));

            if ($this->now->getTimestamp() < $weekday_from->getTimestamp()) {
              $expiries[] = $weekday_from->getTimestamp() - $this->now->getTimestamp();
            } else if ($this->now->getTimestamp() < $weekday_to->getTimestamp()) {
              $expiries[] = $weekday_to->getTimestamp() - $this->now->getTimestamp();
            }
          }
        }

      }
    }

    return count($expiries) > 0 ? min($expiries) : null;
  }

  protected function fromDateTime($value) {
    if ($value !== null || $value !== '') {
      return new DrupalDateTime($value);
    } else {
      return new DrupalDateTime(static::BEGINNING_OF_ALL_TIME);
    }
  }

  protected function toDateTime($value) {
    if ($value !== null || $value !== '') {
      return new DrupalDateTime($value);
    } else {
      return new DrupalDateTime(static::END_OF_ALL_TIME);
    }
  }

}
