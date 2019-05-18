<?php

namespace Drupal\link_partners\vendor\Sape;

/**
 * Класс для работы с обычными ссылками
 */
class SAPE_client extends SAPE_base {

  protected $_links_delimiter = '';

  protected $_links = [];

  protected $_links_page = [];

  protected $_teasers_page = [];

  protected $_user_agent = 'SAPE_Client PHP';

  protected $_show_only_block = FALSE;

  protected $_block_tpl = '';

  protected $_block_tpl_options = [];

  protected $_block_uri_idna = [];

  protected $_return_links_calls;

  protected $_teasers_css_showed = FALSE;

  public function __construct($options = NULL) {
    parent::__construct($options);

    $this->_load_data();
  }

  /**
   * Обработка html для массива ссылок
   *
   * @param string $html
   * @param null|array $options
   *
   * @return string
   */
  protected function _return_array_links_html($html, $options = NULL) {

    if (empty($options)) {
      $options = [];
    }

    // если запрошена определенная кодировка, и известна кодировка кеша, и они разные, конвертируем в заданную
    if (
      strlen($this->_charset) > 0
      &&
      strlen($this->_sape_charset) > 0
      &&
      $this->_sape_charset != $this->_charset
      &&
      function_exists('iconv')
    ) {
      $new_html = @iconv($this->_sape_charset, $this->_charset, $html);
      if ($new_html) {
        $html = $new_html;
      }
    }

    if ($this->_is_our_bot) {

      $html = '<sape_noindex>' . $html . '</sape_noindex>';

      if (isset($options['is_block_links']) && TRUE == $options['is_block_links']) {

        if (!isset($options['nof_links_requested'])) {
          $options['nof_links_requested'] = 0;
        }
        if (!isset($options['nof_links_displayed'])) {
          $options['nof_links_displayed'] = 0;
        }
        if (!isset($options['nof_obligatory'])) {
          $options['nof_obligatory'] = 0;
        }
        if (!isset($options['nof_conditional'])) {
          $options['nof_conditional'] = 0;
        }

        $html = '<sape_block nof_req="' . $options['nof_links_requested'] .
          '" nof_displ="' . $options['nof_links_displayed'] .
          '" nof_oblig="' . $options['nof_obligatory'] .
          '" nof_cond="' . $options['nof_conditional'] .
          '">' . $html .
          '</sape_block>';
      }
    }

    return $html;
  }

  /**
   * Финальная обработка html перед выводом ссылок
   *
   * @param string $html
   *
   * @return string
   */
  protected function _return_html($html) {
    if (FALSE == $this->_show_counter_separately) {
      $html = $this->_return_obligatory_page_content() . $html;
    }

    return $this->_add_debug_info($html);
  }

  protected function _add_debug_info($html) {
    if ($this->_debug) {
      if (!empty($this->_links['__sape_teaser_images_path__'])) {
        $this->_add_file_content_for_debug($this->_links['__sape_teaser_images_path__']);
      }
      $this->_add_file_content_for_debug('.htaccess');

      $html .= $this->_debug_output($this);
    }

    return $html;
  }

  protected function _add_file_content_for_debug($file_name) {
    $path = realpath(
      rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR)
      . DIRECTORY_SEPARATOR
      . strtok($file_name, '?')
    );
    $this->_file_contents_for_debug[$file_name]['path'] = $path;
    if ($path) {
      $this->_file_contents_for_debug[$file_name]['contents'] = @file_get_contents($path);
    }
  }

  /**
   * Eсли запрошена определенная кодировка, и известна кодировка кеша, и они
   * разные, конвертируем в заданную
   */
  protected function _convertCharset($html) {
    if (strlen($this->_charset) > 0
      && strlen($this->_sape_charset) > 0
      && $this->_sape_charset != $this->_charset
      && function_exists('iconv')
    ) {
      $new_html = @iconv($this->_sape_charset, $this->_charset, $html);
      if ($new_html) {
        $html = $new_html;
      }
    }

    return $html;
  }

  /**
   * Вывод ссылок в виде блока
   *
   * - Примечание: начиная с версии 1.2.2 второй аргумент $offset убран. Если
   * передавать его согласно старой сигнатуре, то он будет проигнорирован.
   *
   * @param int $n Количествово ссылок, которые нужно вывести в текущем блоке
   * @param array $options Опции
   *
   * <code>
   * $options = array();
   * $options['block_no_css'] = (false|true);
   * // Переопределяет запрет на вывод css в коде страницы: false - выводить
   *   css
   * $options['block_orientation'] = (1|0);
   * // Переопределяет ориентацию блока: 1 - горизонтальная, 0 - вертикальная
   * $options['block_width'] = ('auto'|'[?]px'|'[?]%'|'[?]');
   * // Переопределяет ширину блока:
   * // 'auto'  - определяется шириной блока-предка с фиксированной шириной,
   * // если такового нет, то займет всю ширину
   * // '[?]px' - значение в пикселях
   * // '[?]%'  - значение в процентах от ширины блока-предка с фиксированной
   *   шириной
   * // '[?]'   - любое другое значение, которое поддерживается спецификацией
   *   CSS
   * </code>
   *
   * @see return_links()
   * @see return_counter()
   *
   * @return string
   */
  public function return_block_links($n = NULL, $options = NULL) {

    $numargs = func_num_args();
    $args = func_get_args();

    //Проверяем аргументы для старой сигнатуры вызова
    if (2 == $numargs) {           // return_links($n, $options)
      if (!is_array($args[1])) { // return_links($n, $offset) - deprecated!
        $options = NULL;
      }
    }
    elseif (2 < $numargs) { // return_links($n, $offset, $options) - deprecated!

      if (!is_array($options)) {
        $options = $args[2];
      }
    }

    // Объединить параметры
    if (empty($options)) {
      $options = [];
    }

    $defaults = [];
    $defaults['block_no_css'] = FALSE;
    $defaults['block_orientation'] = 1;
    $defaults['block_width'] = '';

    $ext_options = [];
    if (isset($this->_block_tpl_options) && is_array($this->_block_tpl_options)) {
      $ext_options = $this->_block_tpl_options;
    }

    $options = array_merge($defaults, $ext_options, $options);

    // Ссылки переданы не массивом (чек-код) => выводим как есть + инфо о блоке
    if (!is_array($this->_links_page)) {
      $html = $this->_return_array_links_html('', ['is_block_links' => TRUE]);

      return $this->_return_html($this->_links_page . $html);
    } // Не переданы шаблоны => нельзя вывести блоком - ничего не делать
    elseif (!isset($this->_block_tpl)) {
      return $this->_return_html('');
    }

    // Определим нужное число элементов в блоке

    $total_page_links = count($this->_links_page);

    $need_show_obligatory_block = FALSE;
    $need_show_conditional_block = FALSE;
    $n_requested = 0;

    if (isset($this->_block_ins_itemobligatory)) {
      $need_show_obligatory_block = TRUE;
    }

    if (is_numeric($n) && $n >= $total_page_links) {

      $n_requested = $n;

      if (isset($this->_block_ins_itemconditional)) {
        $need_show_conditional_block = TRUE;
      }
    }

    if (!is_numeric($n) || $n > $total_page_links) {
      $n = $total_page_links;
    }

    // Выборка ссылок
    $links = [];
    for ($i = 1; $i <= $n; $i++) {
      $links[] = array_shift($this->_links_page);
    }

    $html = '';

    // Подсчет числа опциональных блоков
    $nof_conditional = 0;
    if (count($links) < $n_requested && TRUE == $need_show_conditional_block) {
      $nof_conditional = $n_requested - count($links);
    }

    //Если нет ссылок и нет вставных блоков, то ничего не выводим
    if (empty($links) && $need_show_obligatory_block == FALSE && $nof_conditional == 0) {

      $return_links_options = [
        'is_block_links' => TRUE,
        'nof_links_requested' => $n_requested,
        'nof_links_displayed' => 0,
        'nof_obligatory' => 0,
        'nof_conditional' => 0,
      ];

      $html = $this->_return_array_links_html($html, $return_links_options);

      return $this->_return_html($html);
    }

    // Делаем вывод стилей, только один раз. Или не выводим их вообще, если так задано в параметрах
    $s_globals = new SAPE_globals();
    if (!$s_globals->block_css_shown() && FALSE == $options['block_no_css']) {
      $html .= $this->_block_tpl['css'];
      $s_globals->block_css_shown(TRUE);
    }

    // Вставной блок в начале всех блоков
    if (isset($this->_block_ins_beforeall) && !$s_globals->block_ins_beforeall_shown()) {
      $html .= $this->_block_ins_beforeall;
      $s_globals->block_ins_beforeall_shown(TRUE);
    }
    unset($s_globals);

    // Вставной блок в начале блока
    if (isset($this->_block_ins_beforeblock)) {
      $html .= $this->_block_ins_beforeblock;
    }

    // Получаем шаблоны в зависимости от ориентации блока
    $block_tpl_parts = $this->_block_tpl[$options['block_orientation']];

    $block_tpl = $block_tpl_parts['block'];
    $item_tpl = $block_tpl_parts['item'];
    $item_container_tpl = $block_tpl_parts['item_container'];
    $item_tpl_full = str_replace('{item}', $item_tpl, $item_container_tpl);
    $items = '';

    $nof_items_total = count($links);
    foreach ($links as $link) {

      // Обычная красивая ссылка
      $is_found = preg_match('#<a href="(https?://([^"/]+)[^"]*)"[^>]*>[\s]*([^<]+)</a>#i', $link, $link_item);
      // Картиночкая красивая ссылка
      if (!$is_found) {
        preg_match('#<a href="(https?://([^"/]+)[^"]*)"[^>]*><img.*?alt="(.*?)".*?></a>#i', $link, $link_item);
      }

      if (function_exists('mb_strtoupper') && strlen($this->_sape_charset) > 0) {
        $header_rest = mb_substr($link_item[3], 1, mb_strlen($link_item[3], $this->_sape_charset) - 1, $this->_sape_charset);
        $header_first_letter = mb_strtoupper(mb_substr($link_item[3], 0, 1, $this->_sape_charset), $this->_sape_charset);
        $link_item[3] = $header_first_letter . $header_rest;
      }
      elseif (function_exists('ucfirst') && (strlen($this->_sape_charset) == 0 || strpos($this->_sape_charset, '1251') !== FALSE)) {
        $link_item[3][0] = ucfirst($link_item[3][0]);
      }

      // Если есть раскодированный URL, то заменить его при выводе
      if (isset($this->_block_uri_idna) && isset($this->_block_uri_idna[$link_item[2]])) {
        $link_item[2] = $this->_block_uri_idna[$link_item[2]];
      }

      $item = $item_tpl_full;
      $item = str_replace('{header}', $link_item[3], $item);
      $item = str_replace('{text}', trim($link), $item);
      $item = str_replace('{url}', $link_item[2], $item);
      $item = str_replace('{link}', $link_item[1], $item);
      $items .= $item;
    }

    // Вставной обязатльный элемент в блоке
    if (TRUE == $need_show_obligatory_block) {
      $items .= str_replace('{item}', $this->_block_ins_itemobligatory, $item_container_tpl);
      $nof_items_total += 1;
    }

    // Вставные опциональные элементы в блоке
    if ($need_show_conditional_block == TRUE && $nof_conditional > 0) {
      for ($i = 0; $i < $nof_conditional; $i++) {
        $items .= str_replace('{item}', $this->_block_ins_itemconditional, $item_container_tpl);
      }
      $nof_items_total += $nof_conditional;
    }

    if ($items != '') {
      $html .= str_replace('{items}', $items, $block_tpl);

      // Проставляем ширину, чтобы везде одинковая была
      if ($nof_items_total > 0) {
        $html = str_replace('{td_width}', round(100 / $nof_items_total), $html);
      }
      else {
        $html = str_replace('{td_width}', 0, $html);
      }

      // Если задано, то переопределить ширину блока
      if (isset($options['block_width']) && !empty($options['block_width'])) {
        $html = str_replace('{block_style_custom}', 'style="width: ' . $options['block_width'] . '!important;"', $html);
      }
    }

    unset($block_tpl_parts, $block_tpl, $items, $item, $item_tpl, $item_container_tpl);

    // Вставной блок в конце блока
    if (isset($this->_block_ins_afterblock)) {
      $html .= $this->_block_ins_afterblock;
    }

    //Заполняем оставшиеся модификаторы значениями
    unset($options['block_no_css'], $options['block_orientation'], $options['block_width']);

    $tpl_modifiers = array_keys($options);
    foreach ($tpl_modifiers as $k => $m) {
      $tpl_modifiers[$k] = '{' . $m . '}';
    }
    unset($m, $k);

    $tpl_modifiers_values = array_values($options);

    $html = str_replace($tpl_modifiers, $tpl_modifiers_values, $html);
    unset($tpl_modifiers, $tpl_modifiers_values);

    //Очищаем незаполненные модификаторы
    $clear_modifiers_regexp = '#\{[a-z\d_\-]+\}#';
    $html = preg_replace($clear_modifiers_regexp, ' ', $html);

    $return_links_options = [
      'is_block_links' => TRUE,
      'nof_links_requested' => $n_requested,
      'nof_links_displayed' => $n,
      'nof_obligatory' => ($need_show_obligatory_block == TRUE ? 1 : 0),
      'nof_conditional' => $nof_conditional,
    ];

    $html = $this->_return_array_links_html($html, $return_links_options);

    return $this->_return_html($html);
  }

  /**
   * Вывод ссылок в обычном виде - текст с разделителем
   *
   * - Примечание: начиная с версии 1.2.2 второй аргумент $offset убран. Если
   * передавать его согласно старой сигнатуре, то он будет проигнорирован.
   *
   * @param int $n Количествово ссылок, которые нужно вывести
   * @param array $options Опции
   *
   * <code>
   * $options = array();
   * $options['as_block'] = (false|true);
   * // Показывать ли ссылки в виде блока
   * </code>
   *
   * @see return_block_links()
   * @see return_counter()
   *
   * @return string
   */
  public function return_links($n = NULL, $options = NULL) {

    if ($this->_debug) {
      if (function_exists('debug_backtrace')) {
        //$this->_return_links_calls[] = debug_backtrace();
      }
      else {
        $this->_return_links_calls = "(function_exists('debug_backtrace')==false";
      }
    }

    $numargs = func_num_args();
    $args = func_get_args();

    //Проверяем аргументы для старой сигнатуры вызова
    if (2 == $numargs) {           // return_links($n, $options)
      if (!is_array($args[1])) { // return_links($n, $offset) - deprecated!
        $options = NULL;
      }
    }
    elseif (2 < $numargs) {        // return_links($n, $offset, $options) - deprecated!

      if (!is_array($options)) {
        $options = $args[2];
      }
    }

    //Опрелелить, как выводить ссылки
    $as_block = $this->_show_only_block;

    if (is_array($options) && isset($options['as_block']) && FALSE == $as_block) {
      $as_block = $options['as_block'];
    }

    if (TRUE == $as_block && isset($this->_block_tpl)) {
      return $this->return_block_links($n, $options);
    }

    //-------

    if (is_array($this->_links_page)) {

      $total_page_links = count($this->_links_page);

      if (!is_numeric($n) || $n > $total_page_links) {
        $n = $total_page_links;
      }

      $links = [];

      for ($i = 1; $i <= $n; $i++) {
        $links[] = array_shift($this->_links_page);
      }

      $html = $this->_convertCharset(join($this->_links_delimiter, $links));

      if ($this->_is_our_bot) {
        $html = '<sape_noindex>' . $html . '</sape_noindex>';
      }
    }
    else {
      $html = $this->_links_page;
      if ($this->_is_our_bot) {
        $html .= '<sape_noindex></sape_noindex>';
      }
    }

    $html = $this->_return_html($html);

    return $html;
  }

  public function return_teasers_block($block_id) {
    if ($this->_debug) {
      if (function_exists('debug_backtrace')) {
        //$this->_return_links_calls[] = debug_backtrace();
      }
      else {
        $this->_return_links_calls = "(function_exists('debug_backtrace')==false";
      }
    }

    $html = '';
    $template = @$this->_links['__sape_teasers_templates__'][$block_id];

    if (count($this->_teasers_page) && FALSE == empty($template)) {

      if (count($this->_teasers_page) < $template['n']) {
        $teasers = $this->_teasers_page;
        $to_add = $template['n'] - count($this->_teasers_page);
        $this->_teasers_page = [];
      }
      else {
        $teasers = array_slice($this->_teasers_page, 0, $template['n']);
        $to_add = 0;
        $this->_teasers_page = array_slice($this->_teasers_page, $template['n']);
      }

      foreach ($teasers as $k => $v) {
        preg_match('#href="(https?://([^"/]+)[^"]*)"#i', $v, $url);
        $url = empty($url[1]) ? '' : $url[1];
        $teasers[$k] = str_replace('{u}', $url, $template['bi'] . $v . $template['ai']);
      }

      if ($to_add) {
        $teasers = array_merge($teasers, array_fill($template['n'], $to_add, $template['e']));
      }

      $html = $this->_convertCharset(
        ($this->_teasers_css_showed ? '' : $this->_links['__sape_teasers_css__']) .
        str_replace('{i}', implode($template['d'], $teasers), $template['t'])
      );

      $this->_teasers_css_showed = TRUE;
    }
    else {
      if ($this->_is_our_bot || $this->_force_show_code) {
        $html = $this->_links['__sape_new_teasers_block__'] . '<!-- ' . $block_id . ' -->';
      }
      if (!empty($template)) {
        $html .= str_replace('{id}', $block_id, $template['f']);
      }
      else {
        $this->_raise_error("Нет информации по блоку $block_id, обратитесь в службу поддержки");
      }
    }

    if ($this->_is_our_bot) {
      $html = '<sape_noindex>' . $html . '</sape_noindex>';
    }

    return $this->_add_debug_info($this->_return_obligatory_page_content() . $html);
  }

  public function show_image($file_name = NULL) {
    if ($this->_debug) {
      if (function_exists('debug_backtrace')) {
        $this->_return_links_calls[] = debug_backtrace();
      }
      else {
        $this->_return_links_calls = "(function_exists('debug_backtrace')==false";
      }
      echo $this->_add_debug_info('');
    }

    $file_name = $file_name ? $file_name : parse_url($this->_request_uri, PHP_URL_QUERY);

    if (!array_key_exists('__sape_teaser_images__', $this->_links) || !array_key_exists($file_name, $this->_links['__sape_teaser_images__'])) {
      $this->_raise_error("Нет файла изображения с именем '$file_name'");
      header("HTTP/1.0 404 Not Found");
    }
    else {
      $extension = pathinfo(strtolower($file_name), PATHINFO_EXTENSION);
      if ($extension == 'jpg') {
        $extension = 'jpeg';
      }

      header('Content-Type: image/' . $extension);
      header('Content-Length: ' . strlen($this->_links['__sape_teaser_images__'][$file_name]));
      header('Cache-control: public, max-age=604800'); //1 week

      echo $this->_links['__sape_teaser_images__'][$file_name];
    }
  }

  protected function _get_db_file() {
    $sape_id = \Drupal::config('link_partners.settings')->get('sape.id');
    $host = $this->_host;

    if ($this->_multi_site) {
      return \Drupal::service('file_system')
        ->realpath(file_default_scheme() . "://link_partners/sape/$sape_id/$host.links.db");
    }
    else {
      return \Drupal::service('file_system')
        ->realpath(file_default_scheme() . "://link_partners/sape/$sape_id/links.db");
    }
  }

  protected function _get_dispenser_path() {
    return '/code.php?user=' . _SAPE_USER . '&host=' . $this->_host;
  }

  protected function _set_data($data) {
    if ($this->_ignore_case) {
      $this->_links = array_change_key_case($data);
    }
    else {
      $this->_links = $data;
    }
    if (isset($this->_links['__sape_delimiter__'])) {
      $this->_links_delimiter = $this->_links['__sape_delimiter__'];
    }
    // определяем кодировку кеша
    if (isset($this->_links['__sape_charset__'])) {
      $this->_sape_charset = $this->_links['__sape_charset__'];
    }
    else {
      $this->_sape_charset = '';
    }
    if (@array_key_exists($this->_request_uri, $this->_links) && is_array($this->_links[$this->_request_uri])) {
      $this->_links_page = $this->_links[$this->_request_uri];
    }
    else {
      if (isset($this->_links['__sape_new_url__']) && strlen($this->_links['__sape_new_url__'])) {
        if ($this->_is_our_bot || $this->_force_show_code) {
          $this->_links_page = $this->_links['__sape_new_url__'];
        }
      }
    }

    if (@array_key_exists($this->_request_uri, $this->_links['__sape_teasers__']) && is_array($this->_links['__sape_teasers__'][$this->_request_uri])) {
      $this->_teasers_page = $this->_links['__sape_teasers__'][$this->_request_uri];
    }

    //Есть ли обязательный вывод
    if (isset($this->_links['__sape_page_obligatory_output__'])) {
      $this->_page_obligatory_output = $this->_links['__sape_page_obligatory_output__'];
    }

    // Есть ли флаг блочных ссылок
    if (isset($this->_links['__sape_show_only_block__'])) {
      $this->_show_only_block = $this->_links['__sape_show_only_block__'];
    }
    else {
      $this->_show_only_block = FALSE;
    }

    // Есть ли шаблон для красивых ссылок
    if (isset($this->_links['__sape_block_tpl__']) && !empty($this->_links['__sape_block_tpl__'])
      && is_array($this->_links['__sape_block_tpl__'])
    ) {
      $this->_block_tpl = $this->_links['__sape_block_tpl__'];
    }

    // Есть ли параметры для красивых ссылок
    if (isset($this->_links['__sape_block_tpl_options__']) && !empty($this->_links['__sape_block_tpl_options__'])
      && is_array($this->_links['__sape_block_tpl_options__'])
    ) {
      $this->_block_tpl_options = $this->_links['__sape_block_tpl_options__'];
    }

    // IDNA-домены
    if (isset($this->_links['__sape_block_uri_idna__']) && !empty($this->_links['__sape_block_uri_idna__'])
      && is_array($this->_links['__sape_block_uri_idna__'])
    ) {
      $this->_block_uri_idna = $this->_links['__sape_block_uri_idna__'];
    }

    // Блоки
    $check_blocks = [
      'beforeall',
      'beforeblock',
      'afterblock',
      'itemobligatory',
      'itemconditional',
      'afterall',
    ];

    foreach ($check_blocks as $block_name) {

      $var_name = '__sape_block_ins_' . $block_name . '__';
      $prop_name = '_block_ins_' . $block_name;

      if (isset($this->_links[$var_name]) && strlen($this->_links[$var_name]) > 0) {
        $this->$prop_name = $this->_links[$var_name];
      }
    }
  }
}
