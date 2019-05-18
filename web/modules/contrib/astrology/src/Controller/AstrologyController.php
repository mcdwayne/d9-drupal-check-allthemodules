<?php

namespace Drupal\astrology\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;

/**
 * Class AstrologyController.
 */
class AstrologyController extends ControllerBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Form\FormBuilderInterface.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Drupal\Core\Routing\RedirectDestinationInterface.
   *
   * @var Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectService;

  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, $formBuilder, RedirectDestinationInterface $redirectService) {
    $this->connection = $connection;
    $this->config = $config_factory;
    $this->formBuilder = $formBuilder;
    $this->redirectService = $redirectService;
    $this->utility = new UtilityController($this->connection, $this->config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Astrological sign page for given date of birth.
   *
   * @param string $sign_name
   *   The sign name to list page and its text.
   */
  public function astrologcalSignPage($sign_name = NULL) {

    $astrology_config = $this->config('astrology.settings');
    $astrology_id = $astrology_config->get('astrology');
    $formatter = $astrology_config->get('format_character');
    $query = $this->connection->select('astrology_signs', 'as_')
      ->fields('as_')
      ->condition('name', $sign_name, '=')
      ->condition('astrology_id ', $astrology_id, '=')
      ->execute();
    $query->allowRowCount = TRUE;
    $sign = $query->fetchObject();
    if (!$query->rowCount()) {
      throw new NotFoundHttpException();
    }
    $about_sign_summary = text_summary($sign->about_sign, $sign->about_sign_format);
    $from_date = explode('/', $sign->date_range_from);
    $to_date = explode('/', $sign->date_range_to);
    $from_month = $from_date[0];
    $from_day = $from_date[1];
    $to_month = $to_date[0];
    $today = $to_date[1];
    $date_range_sign = date('M, j', mktime(0, 0, 0, $from_month, $from_day));
    $date_range_sign .= ' - ' . date('M, j', mktime(0, 0, 0, $to_month, $today));
    $build[] = [
      '#theme' => 'astrology-dob-sign',
      '#sign' => $sign,
      '#formatter' => $formatter,
      '#date_range_sign' => $date_range_sign,
      '#about_sign_summary' => $about_sign_summary,
    ];
    $build['#title'] = $this->t('Your astrological sign is ":sign"', [':sign' => $sign_name]);
    return $build;
  }

  /**
   * Astrology sign details page.
   *
   * @param string $sign_name
   *   Sign name.
   */
  public function astrologySignDetailsPage($sign_name = NULL) {

    $astrology_config = $this->config('astrology.settings');
    $astrology_id = $astrology_config->get('astrology');
    $query = $this->connection->select('astrology_signs', 'as_')
      ->fields('as_', [
        'id', 'icon', 'name', 'about_sign', 'date_range_from', 'date_range_to',
      ])
      ->condition('name', $sign_name, '=')
      ->condition('astrology_id ', $astrology_id, '=')
      ->execute();
    $query->allowRowCount = TRUE;
    $sign = $query->fetchObject();
    if (!$query->rowCount()) {
      // Throw new AccessDeniedHttpException();
      throw new NotFoundHttpException();
    }
    $build[] = [
      '#theme' => 'astrology-sign-text',
      '#sign' => $sign,
    ];
    return $build;
  }

  /**
   * Displaying text for sign according to the format selected.
   *
   * @param string $sign_name
   *   Aries, Taurus etc.
   * @param string $formatter
   *   Day, week etc.
   * @param string $next_prev
   *   Day number, week number, month number etc, default is 0.
   */
  public function astrologyListTextSignPage($sign_name = NULL, $formatter = NULL, $next_prev = NULL) {

    $astrology_config = $this->config('astrology.settings');
    $astrology_id = $astrology_config->get('astrology');
    $sign_info = $astrology_config->get('sign_info');

    $format_char = $formatter;
    $allowed_format = ['day', 'week', 'month', 'year'];
    $query = $this->connection->select('astrology_signs', 'as_')
      ->fields('as_')
      ->condition('name', $sign_name, '=')
      ->condition('astrology_id ', $astrology_id, '=')
      ->execute();
    $query->allowRowCount = TRUE;
    $sign = $query->fetchObject();
    if ($query->rowCount() == 0 || !in_array($formatter, $allowed_format)) {
      throw new NotFoundHttpException();
    }
    if ($next_prev && !$this->utility->astrologyCheckValidDate($formatter, $next_prev)) {
      throw new NotFoundHttpException();
    }

    $about_sign_summary = text_summary($sign->about_sign, $sign->about_sign_format);
    $from_date = explode('/', $sign->date_range_from);
    $to_date = explode('/', $sign->date_range_to);
    $from_month = $from_date[0];
    $from_day = $from_date[1];
    $to_month = $to_date[0];
    $today = $to_date[1];

    switch ($format_char) {
      case 'day':
        $format = 'z';
        $date = $next_prev ? $next_prev : date('z');
        $post_date = mktime(0, 0, 0, 1, ($date + 1), date('o'));
        $date_format = 'l, j F';
        break;

      case 'week':
        $format = 'W';
        $date = $next_prev ? $next_prev : date('W');
        $post_date = mktime(0, 0, 0, 1, (4 + 7 * ($date - 1)), date('o'));
        $date_format = 'j, M';
        break;

      case 'month':
        $format = 'n';
        $date = $next_prev ? $next_prev : date('n');
        $post_date = mktime(0, 0, 0, $date);
        $date_format = 'F';
        break;

      case 'year':
        $format = 'o';
        $date = $next_prev ? $next_prev : date('o');
        $date_format = 'Y';
        $post_date = mktime(0, 0, 0, 1, 1, $date);
        break;
    }
    $query1 = $this->connection->select('astrology_text', 'h')
      ->fields('h', ['text', 'id', 'text_format'])
      ->condition('value', $date)
      ->condition('astrology_sign_id', $sign->id)
      ->condition('format_character', $format)
      ->execute();
    $astrology_text = $query1->fetchObject();

    if ($next_prev) {
      $next_prev_val = $this->utility->astrologyCheckNextPrev($formatter, $next_prev);
    }
    else {
      $next_prev = $date;
      $next_prev_val = $this->utility->astrologyCheckNextPrev($formatter, $next_prev);
    }

    $weeks = $this->utility->getFirstLastDow($post_date);

    $date_range_sign = date('M, j', mktime(0, 0, 0, $from_month, $from_day));
    $date_range_sign .= ' - ' . date('M, j', mktime(0, 0, 0, $to_month, $today));

    $build[] = [
      '#theme' => 'astrology-text',
      '#sign' => $sign,
      '#sign_info' => $sign_info,
      '#formatter' => $formatter,
      '#date_format' => $date_format,
      '#astrology_text' => $astrology_text,
      '#about_sign_summary' => $about_sign_summary,
      '#post_date' => $post_date,
      '#weeks' => $weeks,
      '#for_date' => $next_prev_val,
      '#date_range_sign' => $date_range_sign,
    ];
    if ($format_char == 'day') {
      $title = $this->t('Astrology of the day');
    }
    else {
      $title = $this->t('Astrology for the :date', [':date' => $format_char]);
    }
    $build['#title'] = $title;
    return $build;
  }

  /**
   * Search text for available signs from selected astrology.
   *
   * @param int $astrology_id
   *   Astrology id.
   */
  public function astrologySignTextSearch($astrology_id = NULL) {

    $build['config_data'] = $this->formBuilder->getForm('Drupal\astrology\Form\AstrologySignTextSearch', $astrology_id);

    $astrology_config = $this->config('astrology.settings');
    $formater = $astrology_config->get('admin_format_character');
    $cdate = $astrology_config->get('admin_cdate');
    $astrology_sign = $astrology_config->get('sign_id');

    $build['config_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Icon'),
        $this->t('Name'),
        $this->t('Text'),
        $this->t(':dated', [':dated' => $formater]),
        $this->t('Operations'),
      ],
      '#sticky' => TRUE,
      '#empty' => $this->t('There is no item to display.'),
    ];

    switch ($formater) {
      case 'day':
        $format = 'z';
        $newdate = $cdate ? $cdate : date('m/d/Y');
        $timestmp = $this->utility->getFormatDateValue('z', $newdate);
        if ($astrology_sign) {
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name', 'astrology_id']);
          $query->fields('h', ['id']);
          $query->condition('h.id', $astrology_id, '=')
            ->condition('hs.id', $astrology_sign, '=')
            ->condition('ht.format_character', $format, '=')
            ->condition('ht.value', $timestmp, '=')
            ->execute();
        }
        else {
          $subquery = $this->connection->select('astrology', 'h');
          $subquery->join('astrology_signs', 'hs', 'hs.astrology_id = h.id');
          $subquery->fields('hs', ['id'])->condition('hs.astrology_id', $astrology_id, '=');
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name']);
          $query->fields('h', ['id'])
            ->condition('h.id', $astrology_id, '=')
            ->condition('astrology_sign_id', $subquery, 'IN')
            ->condition('format_character', $format, '=')
            ->condition('value', $timestmp, '=')
            ->execute();
        }
        break;

      case 'week':
        $format = 'W';
        $newdate = $cdate ? $cdate : date('m/d/Y');
        $timestmp = $this->utility->getFormatDateValue('W', $newdate);
        if ($astrology_sign) {
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name', 'astrology_id']);
          $query->fields('h', ['id']);
          $query->condition('h.id', $astrology_id, '=')
            ->condition('hs.id', $astrology_sign, '=')
            ->condition('ht.format_character', $format, '=')
            ->condition('ht.value', $timestmp, '=')
            ->execute();
        }
        else {
          $subquery = $this->connection->select('astrology', 'h');
          $subquery->join('astrology_signs', 'hs', 'hs.astrology_id = h.id');
          $subquery->fields('hs', ['id'])
            ->condition('hs.astrology_id', $astrology_id, '=');
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name']);
          $query->fields('h', ['id'])
            ->condition('h.id', $astrology_id, '=')
            ->condition('astrology_sign_id', $subquery, 'IN')
            ->condition('format_character', $format, '=')
            ->condition('value', $timestmp, '=')
            ->execute();
        }
        break;

      case 'month':
        $format = 'n';
        $timestmp = $cdate ? $cdate : date('n', mktime(0, 0, 0, date("m"), date("d")));
        if ($astrology_sign) {
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name', 'astrology_id']);
          $query->fields('h', ['id']);
          $query->condition('h.id', $astrology_id, '=')
            ->condition('hs.id', $astrology_sign, '=')
            ->condition('ht.format_character', $format, '=')
            ->condition('ht.value', $timestmp, '=')
            ->execute();
        }
        else {
          $subquery = $this->connection->select('astrology', 'h');
          $subquery->join('astrology_signs', 'hs', 'hs.astrology_id = h.id');
          $subquery->fields('hs', ['id'])
            ->condition('hs.astrology_id', $astrology_id, '=');
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name']);
          $query->fields('h', ['id'])
            ->condition('h.id', $astrology_id, '=')
            ->condition('astrology_sign_id', $subquery, 'IN')
            ->condition('format_character', $format, '=')
            ->condition('value', $timestmp, '=')
            ->execute();
        }
        $month = date('F, Y', mktime(0, 0, 0, (int) $timestmp, date('d'), date('y')));
        break;

      case 'year':
        $format = 'o';
        $timestmp = $cdate ? $cdate : date('o', mktime(0, 0, 0, date("m"), date("d")));
        if ($astrology_sign) {
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name', 'astrology_id']);
          $query->fields('h', ['id']);
          $query->condition('h.id', $astrology_id, '=')
            ->condition('hs.id', $astrology_sign, '=')
            ->condition('ht.format_character', $format, '=')
            ->condition('ht.value', $timestmp, '=')
            ->execute();
        }
        else {
          $subquery = $this->connection->select('astrology', 'h');
          $subquery->join('astrology_signs', 'hs', 'hs.astrology_id = h.id');
          $subquery->fields('hs', ['id'])
            ->condition('hs.astrology_id', $astrology_id, '=');
          $query = $this->connection->select('astrology_text', 'ht');
          $query->join('astrology_signs', 'hs', 'hs.id = ht.astrology_sign_id');
          $query->join('astrology', 'h', 'hs.astrology_id = h.id');
          $query->fields('ht', [
            'id', 'text', 'text_format', 'value', 'astrology_sign_id', 'post_date',
          ]);
          $query->fields('hs', ['icon', 'name']);
          $query->fields('h', ['id'])
            ->condition('h.id', $astrology_id, '=')
            ->condition('astrology_sign_id', $subquery, 'IN')
            ->condition('format_character', $format, '=')
            ->condition('value', $timestmp, '=')
            ->execute();
        }
        break;
    }

    $result = $query->execute();
    foreach ($result as $row) {
      $weeks = $this->utility->getFirstLastDow($row->post_date);
      $icon = $this->t('<img src=":src" alt=":alt" height=":height" width=":width" />', [
        ':src' => file_create_url($row->icon),
        ':alt' => $row->name,
        ':height' => '30',
        ':width' => '30',
      ]);
      $week = date('j, M', $weeks[0]) . ' - ' . date('j, M', $weeks[1]);
      $dated = ($formater == 'day') ? date('j M,Y', $row->post_date) :
       (($formater == 'week') ? $week :
        (($formater == 'month') ? $month : $timestmp));

      $build['config_table'][$row->astrology_sign_id]['icon'] = [
        '#markup' => $icon,
      ];
      $build['config_table'][$row->astrology_sign_id]['name'] = [
        '#markup' => $row->name,
      ];
      $build['config_table'][$row->astrology_sign_id]['text'] = [
        '#markup' => text_summary($row->text, $row->text_format),
      ];
      $build['config_table'][$row->astrology_sign_id]['dated'] = [
        '#plain_text' => $dated,
      ];

      // Operations (drop down button) column.
      $build['config_table'][$row->astrology_sign_id]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $build['config_table'][$row->astrology_sign_id]['operations']['#links']['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('astrology.astrology_sign_text_edit', [
          'astrology_id' => $astrology_id,
          'sign_id' => $row->astrology_sign_id,
          'text_id' => $row->id,
        ]),
      ];
    }
    return $build;
  }

  /**
   * List all signs from available astrology.
   *
   * @param int $astrology_id
   *   The node to add banners to.
   */
  public function astrologyListSign($astrology_id = NULL) {

    $build['config_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Icon'),
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no signs yet.</a>'),
    ];

    $query = $this->connection->select('astrology_signs', 's')
      ->fields('s')
      ->condition('s.astrology_id', $astrology_id, '=');
    $result = $query->execute();

    // Populate the rows.
    foreach ($result as $row) {

      $icon = $this->t('<img src=":src" alt=":alt" height=":height" width=":width" />', [
        ':src' => file_create_url($row->icon),
        ':alt' => $row->name,
        ':height' => '30',
        ':width' => '30',
      ]);
      $build['config_table'][$row->id]['icon'] = [
        '#markup' => $icon,
      ];
      $build['config_table'][$row->id]['name'] = [
        '#plain_text' => $row->name,
      ];
      $build['config_table'][$row->id]['about_sign'] = [
        '#markup' => text_summary($row->about_sign, $row->about_sign_format),
      ];

      $destination = $this->redirectService->getAsArray();

      // Operations (drop down button) column.
      $build['config_table'][$row->id]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $build['config_table'][$row->id]['operations']['#links']['add_text'] = [
        'title' => $this->t('Add text'),
        'url' => Url::fromRoute('astrology.add_text_astrology_sign', [
          'astrology_id' => $row->astrology_id,
          'sign_id' => $row->id,
        ]),
      ];
      $build['config_table'][$row->id]['operations']['#links']['edit_sign'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('astrology.edit_astrology_sign', [
          'astrology_id' => $row->astrology_id,
          'sign_id' => $row->id,
        ])->setOption('query', $destination),
      ];
      // Remove delete option for astrology signs from zodiac.
      if ($row->astrology_id != 1) {
        $build['config_table'][$row->id]['operations']['#links']['delete_sign'] = [
          'title' => $this->t('Delete'),
        // ->setOption('query', $destination),.
          'url' => Url::fromRoute('astrology.delete_astrology_sign', [
            'astrology_id' => $row->astrology_id,
            'sign_id' => $row->id,
          ]),
        ];
      }
    }
    return $build;
  }

  /**
   * Configuration page that shows available astrology and its default setting.
   */
  public function astrologyConfig() {

    $build['config_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Enabled'),
        $this->t('Operations'),
      ],
    ];

    $query = $this->connection->select('astrology', 'a');
    $query->fields('a');
    $result = $query->execute();

    // Populate the rows.
    foreach ($result as $row) {

      $build['config_table'][$row->id]['name'] = [
        '#plain_text' => $this->t(':name', [':name' => $row->name]),
      ];
      $build['config_table'][$row->id]['about'] = [
        '#markup' => text_summary($row->about, $row->about_format),
      ];
      $build['config_table'][$row->id]['enabled'] = [
        '#markup' => ($row->enabled) ? '<strong>Yes</strong>' : 'No',
      ];

      $destination = $this->redirectService->getAsArray();

      // Operations (drop down button) column.
      $build['config_table'][$row->id]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $build['config_table'][$row->id]['operations']['#links']['list_sign'] = [
        'title' => $this->t('List sign'),
        'url' => Url::fromRoute('astrology.list_astrology_sign', ['astrology_id' => $row->id]),
      ];
      $build['config_table'][$row->id]['operations']['#links']['list_text'] = [
        'title' => $this->t('List text'),
        'url' => Url::fromRoute('astrology.astrology_sign_list_text', ['astrology_id' => $row->id]),
      ];
      if ($row->id != 1) {
        $build['config_table'][$row->id]['operations']['#links']['add_sign'] = [
          'title' => $this->t('Add sign'),
          'url' => Url::fromRoute('astrology.add_astrology_sign', ['astrology_id' => $row->id]),
        ];
      }
      $build['config_table'][$row->id]['operations']['#links']['edit_astrology'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('astrology.edit_astrology', [
          'astrology_id' => $row->id,
        ])->setOption('query', $destination),
      ];

      // Remove un-necessary option for astrology zodiac.
      if ($row->id != 1) {
        $build['config_table'][$row->id]['operations']['#links']['delete_astrology'] = [
          'title' => $this->t('Delete'),
        // ->setOption('query', $destination),.
          'url' => Url::fromRoute('astrology.delete_astrology', ['astrology_id' => $row->id]),
        ];
      }
    }
    $build['config_data'] = $this->formBuilder->getForm('Drupal\astrology\Form\AstrologyConfig');
    return $build;
  }

}
