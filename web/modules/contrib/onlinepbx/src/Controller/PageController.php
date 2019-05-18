<?php

namespace Drupal\onlinepbx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;

/**
 * Page Controller.
 */
class PageController extends ControllerBase {

  /**
   * ONline.
   */
  public function online() {
    $output = "ONline";
    $config = \Drupal::config('onlinepbx.settings');
    $url = $config->get('url');
    $key = $config->get('key');

    $div = "log";
    return [
      'output' => [
        '#theme' => "call-online",
        '#data' => [
          'id' => $div,
        ],
        '#attached' => [
          'drupalSettings' => [
            'onlinepbx' => [
              'url' => base64_encode($url),
              'key' => base64_encode($key),
              'log' => $div,
            ],
          ],
          'library' => [
            'vuejs/vue',
            'onlinepbx/online',
          ],
        ],
      ],
    ];
  }

  /**
   * Page.
   */
  public function page($params = []) {
    $route = \Drupal::routeMatch()->getRouteName();
    $rows = [];
    $charts = [];
    $users = [];
    $gateways = [];
    $days = "";
    if ($calls = OnpbxCalls::getCalls($params)) {
      $rows = $this->tableRows($calls);
      $charts = Charts::makeData($calls);
      foreach ($calls['users'] as $k => $user) {
        $users[] = Link::createFromRoute($user['xname'], $route, ['users' => $k]);
      }
      foreach ($calls['gateways'] as $k => $gw) {
        $gateways[] = Link::createFromRoute($gw['xname'], $route, ['gateways' => $k]);
      }
      $dayarr = [];
      foreach ($calls['days'] as $k => $day) {
        $dlink = Link::createFromRoute($day['day'], 'onlinepbx.today', ['day' => $k]);
        $dayarr[] = $dlink->toString()->getGeneratedLink();
      }
      $days = implode(", ", $dayarr);
    }

    $div = 'onlinepbx-charts';
    $duble_link = Link::createFromRoute("Убрать дубли", $route, ['duble' => 'skip']);
    $gw_link = Link::createFromRoute("Показать линии", $route, ['display' => 'gateway']);

    return [
      'carts' => [
        '#markup' => "<div id='$div'>Loading...</div>",
        '#attached' => [
          'drupalSettings' => [
            'onlinepbx' => [
              'chartsData' => $charts,
              'div' => $div,
              'height' => 300,
            ],
          ],
          'library' => [
            'onlinepbx/table.charts',
          ],
        ],
      ],
      'filter' => [
        'dubleskip' => $duble_link->toRenderable(),
        'delimetr' => ['#markup' => ", "],
        'displaygw' => $gw_link->toRenderable(),
        'days' => ['#markup' => "<div>Дни: $days</div>"],
        'users' => [
          '#type' => 'details',
          '#title' => 'Трубки',
          '#open' => FALSE,
          'ulist' => [
            '#theme' => 'item_list',
            '#items' => $users,
            '#list_type' => 'ol',
          ],
        ],
        'gateways' => [
          '#type' => 'details',
          '#title' => 'Лини',
          '#open' => FALSE,
          'glist' => [
            '#theme' => 'item_list',
            '#items' => $gateways,
            '#list_type' => 'ol',
          ],
        ],
      ],
      'talbe' => $this->tableRender($rows),
    ];
  }

  /**
   * Income.
   */
  public function pageIncome() {
    return $this->page(['type' => 'inbound']);
  }

  /**
   * Outcome.
   */
  public function pageOutcome() {
    return $this->page(['type' => 'outbound']);
  }

  /**
   * Outcome.
   */
  public function pageMissing() {
    return $this->page(['type' => 'inbound', 'billsec_to' => 0]);
  }

  /**
   * Today Title.
   */
  public function todayPageTitle() {
    $params = Period::getToDayParams();
    return format_date($params['start'], 'custom', 'l, j F Y');
  }

  /**
   * Today.
   */
  public function today() {
    $params = Period::getToDayParams();
    $charts = FALSE;
    if ($calls = OnpbxCalls::getCalls($params)) {
      $charts = Charts::makeDayPulse($calls);
    }
    $start = format_date($params['start'], 'custom', 'Y-m-d');
    $div = 'onlinepbx-charts';
    $start = strtotime("$start - 5 days");
    $end = strtotime("tomorrow");
    $dayarr = [];
    foreach (Period::days($start, $end) as $k => $day) {
      $dlink = Link::createFromRoute($day['day'], 'onlinepbx.today', ['day' => $k]);
      $dayarr[] = $dlink->toString()->getGeneratedLink();
    }
    $days = implode(", ", $dayarr);
    $page = $this->page($params);
    $page['filter']['days'] = ['#markup' => "<div>Дни: $days</div>"];
    return [
      'carts' => [
        '#attached' => [
          'drupalSettings' => [
            'onlinepbx' => [
              'chartsData' => $charts,
              'div' => $div,
              'height' => 300,
            ],
          ],
          'library' => [
            'onlinepbx/table.charts',
          ],
        ],
      ],
      'page' => $page,
    ];
  }

  /**
   * Page Users.
   */
  public function users() {
    $output = "Users";
    $users = OnpbxUsers::getCached();
    $list = [];
    foreach ($users as $key => $value) {
      $list[$key] = "$key - $value";
    }
    $count = count($users);
    $price = 250;
    $sum = $count * $price;
    $output .= " $count (х{$price}₽={$sum}₽)";
    return [
      'output' => ['#markup' => $output],
      'users' => [
        '#theme' => 'item_list',
        '#items' => $list,
        '#list_type' => 'ol',
      ],
    ];
  }

  /**
   * Table Render.
   */
  public function tableRender($rows = []) {
    $headers = [
      ['data' => '#', 'class' => ['text-center']],
      ['data' => 'Клиент', 'class' => ['text-left']],
      ['data' => 'Имя', 'class' => ['text-left']],
      ['data' => 'Линия', 'class' => ['text-left']],
      ['data' => 'Дата', 'class' => ['text-left']],
      ['data' => 'Звонок', 'class' => ['text-left']],
      ['data' => 'Трубка', 'class' => ['text-left']],
      ['data' => 'Запись', 'class' => ['text-left'], 'width' => 305],
      ['data' => 'Информация', 'class' => ['text-left']],
    ];
    return [
      '#type' => 'table',
      '#header' => $headers,
      '#rows'   => $rows,
      '#attributes' => [
        'class' => ['table', 'table-striped', 'table-hover'],
      ],
      '#allowed_tags' => ['p', 'table', 'tr', 'td', 'th', 'small', 'br'],
      '#attached' => [
        'library' => [
          'onlinepbx/table.record',
        ],
      ],
    ];
  }

  /**
   * Собрать содержимое таблицы по звонкам.
   */
  private function tableRows($calls = []) {
    $rows = [];
    $rowsData = [];
    $counter = [];

    if ($key = count($calls['calls'])) {
      $route = \Drupal::routeMatch()->getRouteName();
      $duble_hide = \Drupal::request()->query->get('duble');
      $clients = $calls['clietns'];
      \Drupal::moduleHandler()->alter('onlinepbx_clients', $clients);

      foreach ($calls['calls'] as $call_line) {
        $submission = '';
        $dubl = FALSE;
        $view = '';
        $more = '';

        $day = $call_line['day'];
        $client = $call_line['client'];
        if (isset($counter[$day][$client])) {
          $counter[$day][$client] = $counter[$day][$client] + 1;
          $dubl = "<span title='Дубль'>🔹<span>";
        }
        else {
          $counter[$day][$client] = 1;
        }

        $gw = $call_line['gwname'];
        $time = $this->secToTime($call_line['bsec']);
        $uuid = $call_line['uuid'];
        $start_voice = $call_line['dur'] - $call_line['bsec'];
        $audio = "<a href='/onlinepbx/record/{$uuid}/rec.mp3' id='call-$uuid'
        data-uuid='$uuid' data-start='$start_voice' class='call-record'>запись</a>";
        $audioCheck = FALSE;

        $time = "<b>{$time}</b> +{$start_voice}s.";

        $t = "<span title='Исходящий'>📞</span>";
        if ($call_line['type'] == 'inbound') {
          $t = "<span title='Входящий'>✔️</span>";
        }
        $x = "<span title='Ок'>⚪</span>";
        if ($call_line['bsec'] < 7) {
          $x = "<span title='Короткий'>⚫</span>";
          $audio = $this->rowBage("разговор: {$call_line['bsec']} +{$call_line['dur']}s.");
          if ($call_line['bsec'] == 0 && $call_line['type'] == 'inbound') {
            $x = "<span title='Пропущен'>🔥</span>";
            $audio = $this->rowBage("пропущен +{$call_line['dur']}s.", 'danger');
          }
          $time = "";
          $audioCheck = TRUE;
        }

        $date = format_date($call_line['date'], 'custom', 'd M H:i');
        $link = Link::createFromRoute($client, $route, ['clients' => $client]);
        if (!$dubl || !$duble_hide) {
          $rows[] = [
            ['data' => "$key", 'class' => ['text-center']],
            $this->rowData($link->toString() . " $dubl"),
            $this->rowData($clients[$client]['name']),
            $this->rowData($gw),
            $this->rowData("{$date}"),
            $this->rowData("{$time}"),
            $this->rowData("{$t}{$x} {$call_line['uname']}"),
            $this->rowData($audio, ['audio', 'source', 'span', 'a']),
            $this->rowData("<small>{$call_line['msg']}</small>", ['b', 'small']),
          ];
        }
        $key--;
      }
    }
    $rows = array_reverse($rows);
    return $rows;
  }

  /**
   * Row Bage.
   */
  private function secToTime($sec) {
    return intdiv($sec, 60) . ":" . str_pad(bcmod($sec, 60), 2, '0', STR_PAD_LEFT);
  }

  /**
   * Row Bage.
   */
  private function rowBage($text, $type = 'warning') {
    return "<span class='badge badge-roundless badge-$type'> $text </span>";
  }

  /**
   * Row.
   */
  private function rowData($text, $allowed = ['strong', 'span', 'p', 'b', 'a']) {
    return [
      'data' => [
        '#markup' => $text,
        '#allowed_tags' => $allowed,
      ],
    ];
  }

}
