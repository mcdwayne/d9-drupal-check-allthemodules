<?php

/**
 * @file
 * Module API documentation.
 */

/**
 * @defgroup phpexcel_api PHPExcel API
 * @{
 * PHPExcel (the library) is a powerful PHP library to export and import data
 * to and from Excel file. It is very flexible, and well built. The PHPExcel
 * Drupal module, on the other hand, provides a "wrapper", a simpler API that
 * behaves in a "Drupal" way. This module simplifies the export or import of
 * data, abstracting much of the complexity, at the cost of flexibility.
 *
 * @section export Exporting data
 *
 * The idea is to provide an API very similar to Drupal's theme_table()
 * function.
 *
 * Using the module's functions requires the phpexcel.inc file to be loaded:
 * @code
 * module_load_include('inc', 'phpexcel');
 * @endcode
 *
 * Exporting data is done via phpexcel_export().
 * @code
 * phpexcel_export(array('Header 1', 'Header 2'), array(
 *   array('A1', 'B1'),
 *   array('A2', 'B2'),
 * ), 'path/to/file.xls');
 * @endcode
 *
 * It is also possible to pass an array of options to the export function:
 * @code
 * phpexcel_export(array('Header 1', 'Header 2'), array(
 *   array('A1', 'B1'),
 *   array('A2', 'B2'),
 * ), 'path/to/file.xls', array('description' => "Some description"));
 * @endcode
 *
 * If the target file already exists, data will be appended to it, instead of
 * overwriting its content. It is also possible to use an existing file as a
 * template. This is done by specifying the "template" option:
 * @code
 * phpexcel_export(array('Header 1', 'Header 2'), array(
 *   array('A1', 'B1'),
 *   array('A2', 'B2'),
 * ), 'path/to/file.xls', array('template' => 'path/to/template.xls'));
 * @endcode
 *
 * It is possible to export data to multiple worksheets. In that case, the
 * headers array becomes a 2-dimensional array, and the data takes 3
 * dimensions. The 1st dimension represents the Worksheets. They can be
 * keyed by name, or simply numerically. The headers array determines the
 * worksheet names, unless the "ignore_headers", in which case worksheet names
 * are determined by the data array.
 * @code
 * phpexcel_export(
 *   array('Worksheet 1' => array(
 *     'Header 1',
 *     'Header 2',
 *   )),
 *   array(array(
 *     array('A1', 'B1'),
 *     array('A2', 'B2'),
 *   )),
 *   'path/to/file.xls'
 * );
 * @endcode
 *
 * Or, if ignoring headers:
 * @code
 * phpexcel_export(
 *   NULL,
 *   array('Worksheet 1' => array(
 *     array('A1', 'B1'),
 *     array('A2', 'B2'),
 *   )),
 *   'path/to/file.xls',
 *   array('ignore_headers' => TRUE)
 * );
 * @endcode
 *
 * phpexcel_export() accepts the following options, which must be given in
 * array format as the 4th parameter:
 * - ignore_headers: a boolean indicating whether the headers array should be
 *   used, or simply ignored. If ignored, worksheet names will be computed
 *   based on the data parameter.
 * - merge_cells: an array with sheets and cell ranges that need to be merged
 *   in the end result. For example:
 *   @code
 *   array('merge_cells' => array(
 *     'Worksheet 1' => array(
 *       'A1:C1',
 *       'D1:G1',
 *     ),
 *   ))
 *   @endcode
 *   Notice that for merging cells, contrary to the $header and $data
 *   parameters, you MUST give at least 1 worksheet, be it an index or a valid
 *   worksheet name.
 * - template: a path to an existing file, to be used as a template.
 * - format: The EXCEL format. Can be either 'xls', 'xlsx', 'csv', or 'ods'. By
 *   default, the extension of the file given as the target path will be used
 *   (e.g., 'path/to/file.csv' means a format of 'csv'). If the file has no
 *   extension, or an extension that is not supported, it will fallback to
 *   'xls'.
 * - creator: (metadata) The name of the creator of the file.
 * - title: (metadata) The title of the file.
 * - subject: (metadata) The subject of the file.
 * - description: (metadata) The description of the file.
 *
 * Any other options will simply be ignored, but can be useful for modules that
 * implement hook_phpexcel_export().
 *
 * phpexcel_export() will always return an integer. This integer can be one of
 * the following constants:
 * - PHPEXCEL_SUCCESS: Export was successful.
 * - PHPEXCEL_ERROR_NO_HEADERS: Error. Returned when no headers are passed, and
 *   the "ignore_headers" option is different than true.
 * - PHPEXCEL_ERROR_NO_DATA: Error. Returned when there is no data to export.
 * - PHPEXCEL_ERROR_PATH_NOT_WRITABLE: Error. Returned when the path is not
 *   writable.
 * - PHPEXCEL_ERROR_LIBRARY_NOT_FOUND: Error. Returned when the library could
 *   not be loaded. Usually this means the library is not in the correct
 *   location, or not extracted correctly. Remember that the changelog.txt file
 *   that comes with the source code must be present in the library directory.
 * - PHPEXCEL_ERROR_FILE_NOT_WRITTEN: Error. Even though the path is writable,
 *   something prevented PHPExcel from actually saving the file.
 * - PHPEXCEL_CACHING_METHOD_UNAVAILABLE: Error. This is a configuration error,
 *   and happens when the site administrator uses an unavailable caching method,
 *   like Memcached, when there's no Memcached server running.
 *
 * @section import Importing data
 *
 * Using the module's functions requires the phpexcel.inc file to be loaded:
 * @code
 * module_load_include('inc', 'phpexcel');
 * @endcode
 *
 * Importing data is done via phpexcel_import().
 * @code
 * $data = phpexcel_import('path/to/file.xls');
 * @endcode
 *
 * This will return the cell data in array format. The array structure is as
 * follows:
 * @code
 * array(
 *   0 => array(
 *     0 => array(
 *       'Header 1' => 'A1',
 *       'Header 2' => 'B1',
 *     ),
 *     1 => array(
 *       'Header 1' => 'A2',
 *       'Header 2' => 'B2',
 *     ),
 *   ),
 * );
 * @endcode
 *
 * The 1st dimension is the worksheet(s). The 2nd is the rows. Each row is keyed
 * by the table header by default.
 *
 * It is possible to export the headers as a row of data, and not key the
 * following rows by these header names by passing FALSE as the second
 * parameter:
 * @code
 * $data = phpexcel_import('path/to/file.xls', FALSE);
 * @endcode
 *
 * This will return the following format:
 * @code
 * array(
 *   0 => array(
 *     0 => array(
 *       'Header 1',
 *       'Header 2',
 *     ),
 *     1 => array(
 *       'A1',
 *       'B1',
 *     ),
 *     2 => array(
 *       'A2',
 *       'B2',
 *     ),
 *   ),
 * );
 * @endcode
 *
 * It is also possible to use the worksheet names as keys for the worksheet
 * data. This is done by passing TRUE as the third parameter.
 * @code
 * $data = phpexcel_import('path/to/file.xls', TRUE, TRUE);
 * @endcode
 *
 * This will return the following format:
 * @code
 * array(
 *   'Worksheet 1' => array(
 *     0 => array(
 *       'Header 1' => 'A1',
 *       'Header 2' => 'B1',
 *     ),
 *     1 => array(
 *       'Header 1' => 'A2',
 *       'Header 2' => 'B2',
 *     ),
 *   ),
 * );
 * @endcode
 *
 * It is possible to specify method calls to the PHPExcel reader before
 * processing the file data. This is done via the fourth parameter, which is
 * an array, keyed by method name, and whose value is the parameters. For
 * instance, if you only want to load specific worksheets to save memory:
 * @code
 * $data = phpexcel_import('path/to/file.xls', TRUE, TRUE, array(
 *   'setLoadSheetsOnly' => array('My sheet'))
 * );
 * @endcode
 *
 * The specified methods must exist, and are called on an instance of
 * PHPExcel_Reader_IReader.
 *
 * The returned data is either an array (meaning the import was successful) or
 * an integer. The integer can be one of the following:
 * - PHPEXCEL_ERROR_FILE_NOT_READABLE: Error. The file was not found or isn't
 *   readable.
 * - PHPEXCEL_ERROR_LIBRARY_NOT_FOUND: Error. Returned when the library could
 *   not be loaded. Usually this means the library is not in the correct
 *   location, or not extracted correctly. Remember that the changelog.txt file
 *   that comes with the source code must be present in the library directory.
 *
 * @}
 */

/**
 * @defgroup phpexcel_perf PHPExcel performance settings
 * @{
 * PHPExcel (the library) allows developers to have fine-grained control over
 * the way cell data is cached. Large Excel files can quickly take up a lot of
 * memory (about 1Kb per cell), and PHP processes could die running out of
 * available memory.
 *
 * PHPExcel is usable with APC, Memcached, SQLite or static file storage, to
 * optimize memory usage. PHPExcel can also gzip cell data, resulting in less
 * memory usage, but at the cost of speed.
 *
 * Because this should not be controlled by any modules, but depends on each
 * site install, there's no API to set what caching method to use. By default,
 * PHPEXcel will use in-memory caching for fastest performance. Site
 * administrators can change this by going to /admin/config/system/phpexcel.
 * This configuration form will allow them to choose an appropriate caching
 * method, and provide any related settings. The module will then start using
 * these settings when exporting and importing data, to optimize memory usage.
 *
 * The following methods are available:
 * - In memory, which is the fastest, but also consumes the most memory.
 * - In memory, serialized. Slightly slower, but decreases memory usage.
 * - In memory, gzipped. Slighty slower than serialized, but decreases memory
 *   usage even further.
 * - APC, requires APC to be installed. Fast, and doesn't increase memory usage.
 * - Memcache, requires Memcached to be installed and running. Fast, and doesn't
 *   increase memory usage.
 * - SQLite3. Ships with most PHP installs. Slow, but doesn't increase memory
 *   usage.
 * - Static files (stored in php://tmp). Slowest, but doesn't increase memory
 *   usage. In this case, it is still possible to set a maximum limit, up until
 *   which PHPExcel will still use in-memory caching. Defaults to 1Mb. After
 *   reaching that limit, data is stored in static files.
 * @}
 */

/**
 * @defgroup phpexcel_cookbook PHPExcel cookbook
 * @{
 * This section provides several real-life examples of using the PHPExcel module
 * with your own modules and sites. For the basics, see
 * @link phpexcel_api the PHPExcel API topic @endlink.
 *
 * @section toc Table of contents
 *
 * - @ref download_result Download the exported result
 * - @ref batch_export Export data in a batch
 * - @ref batch_import Import data in a batch
 * - TODO: adding styles to a cell
 *
 * @section download_result Download the exported result
 *
 * It is possible that an exported result must be downloaded straight away,
 * after which the exported file should be deleted. One way to achieve this is
 * using Drupal's "managed files". We can register files with Drupal, and tell
 * it to mark them as temporary. These temporary files will be garbage collected
 * at regular intervals, on cron runs. The following example shows the code
 * that exports the data to an Excel file, and then registers it with Drupal as
 * a temporary file, and triggers the file transfer. Notice that using the
 * built-in file_download_headers() function requires a hook_file_download()
 * implementation.
 *
 * @see file_download_headers()
 * @see file_transfer()
 * @see hook_file_download()
 *
 * This code could be the content of a page callback, for example:
 *
 * @code
 * module_load_include('inc', 'phpexcel');
 *
 * // Prepare the file path. The PHPExcel library doesn't handle PHP stream
 * // wrappers, so we need the real path.
 * $wrapper = file_stream_wrapper_get_instance_by_uri('temporary://');
 * // Generate a file name. If it's unique, it's less likely to conflict with an
 * // existing file. You could also put up some more checks on this, if it's likely
 * // to conflict (like, when you have many export/download requests).
 * $filename = 'mymodule--download-' . uniqid() . '.xls';
 * $filepath = $wrapper->realpath() . '/' . $filename;
 * // Export, and store to file.
 * $result = phpexcel_export(array('Header 1', 'Header 2'), array(
 *   array('A1', 'B1'),
 *   array('A2', 'B2'),
 * ), $filepath);
 *
 * if ($result === PHPEXCEL_SUCCESS) {
 *   // Exported successfully. Let's register the file with Drupal. We simply
 *   // tell Drupal to copy the file over the existing one, by passing in
 *   // temporary://$filename.
 *   $file = file_save_data(
 *     file_get_contents($filepath),
 *     "temporary://$filename",
 *     FILE_EXISTS_REPLACE
 *   );
 *
 *   // By default, the file is stored as a permanent file. Let's make it
 *   // temporary, so Drupal will remove it (in 6 hours, if your cron is set up
 *   // correctly).
 *   $file->status = 0;
 *   file_save($file);
 *
 *   // Start downloading. This requires a hook_file_download() implementation!
 *   $headers = file_download_headers($file->uri);
 *   file_transfer($file->uri, $headers);
 * }
 * else {
 *   // Error.
 * }
 * @endcode
 *
 * To complement this, we need a hook_file_download() implementation. Add this
 * to your module's .module file:
 *
 * @code
 * function mymodule_file_download($uri) {
 *   if (preg_match('/mymodule--download-(.+?)\.xls$/', $uri)) {
 *     return array(
 *       'Content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
 *       'Content-Disposition' => 'attachment; filename="export.xls"',
 *     );
 *   }
 * }
 * @endcode
 *
 * @section batch_export Export data in a batch
 *
 * It is possible to append data to existing files, allowing modules to export
 * Excel files in a batch operation. This requires the preparation of an
 * Excel file before data starts to be exported. In the following example, the
 * file can be downloaded at the end via a status message and a link. For this
 * to work, we need a hook_file_download() implementation. This is not required,
 * however, if you simply need to treat the file in a different way after the
 * export.
 *
 * @see batch
 *
 * First, provide the batch operation and finish callbacks:
 *
 * @code
 * function mymodule_batch_process(&$context) {
 *   module_load_include('inc', 'phpexcel');
 *
 *   if (!isset($context['sandbox']['progress'])) {
 *     // Store the file in the temporary directory.
 *     $wrapper = file_stream_wrapper_get_instance_by_uri('temporary://');
 *     $context['sandbox']['filename'] = 'mymodule--download-' . uniqid() . '.xls';
 *     $context['sandbox']['file'] = $wrapper->realpath() . '/' . $context['sandbox']['filename'];
 *
 *     // Prepare the Excel file.
 *     $result = phpexcel_export(array('Header 1', 'Header 2'), array(
 *       // Provide some empty data. We will append data later on.
 *       array(),
 *     ), $context['sandbox']['file']);
 *
 *     if ($result !== PHPEXCEL_SUCCESS) {
 *       drupal_set_message("Something went wrong", 'error');
 *       $context['sandbox']['finished'] = 1;
 *       $context['success'] = FALSE;
 *       return;
 *     }
 *
 *     $context['sandbox']['progress'] = 0;
 *     $context['sandbox']['max'] = 40;
 *     // Trick to pass the filepath to the finished callback.
 *     $context['results'] = "temporary://{$context['sandbox']['filename']}";
 *   }
 *
 *   $limit = 10;
 *   while($limit) {
 *     $result = phpexcel_export(array('Header 1', 'Header 2'), array(
 *       // Append some data to the file.
 *       array('Some value', 'Some other value'),
 *     ), $context['sandbox']['file'], array(
 *       // Use our previously prepared file as a "template", which means we
 *       // will append data to it, instead of starting from scratch again.
 *       'template' => $context['sandbox']['file'],
 *     ));
 *
 *     if ($result !== PHPEXCEL_SUCCESS) {
 *       drupal_set_message(t("Something went wrong on pass !pass", array(
 *         '!pass' => $context['sandbox']['progress'],
 *       )), 'error');
 *       $context['sandbox']['finished'] = 1;
 *       $context['success'] = FALSE;
 *       return;
 *     }
 *
 *     $context['sandbox']['progress']++;
 *     $limit--;
 *   }
 *
 *   if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
 *     $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
 *   }
 * }
 *
 * function mymodule_batch_finished($success, $results, $operations) {
 *   if ($success) {
 *     $wrapper = file_stream_wrapper_get_instance_by_uri($results);
 *     drupal_set_message(t("Download it here: !link", array(
 *       '!link' => l($results, $wrapper->getExternalUrl()),
 *     )), 'status', FALSE);
 *   }
 * }
 * @endcode
 *
 * Now, we can set a batch operation like so:
 *
 * @code
 * batch_set(array(
 *   'operations' => array(
 *     array('mymodule_batch_process', array()),
 *   ),
 *   'finished' => 'mymodule_batch_finished',
 * ));
 * @endcode
 *
 * Again, for this example to work, we need a hook_file_download()
 * implementation:
 *
 * @code
 * function mymodule_file_download($uri) {
 *   if (preg_match('/mymodule--download-(.+?)\.xls$/', $uri)) {
 *     return array(
 *       'Content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
 *       'Content-Disposition' => 'attachment; filename="export.xls"',
 *     );
 *   }
 * }
 * @endcode
 *
 * @section batch_import Import data in a batch
 *
 * When dealing with very large PHPExcel files, we sometimes want to import data
 * in a batch instead of everything at once in order to save memory. We can
 * achieve this using a custom PHPExcel_Reader_IReadFilter class. A filter can
 * be used by PHPExcel to only read certain rows, which saves memory.
 *
 * @see batch
 *
 * First, provide the batch operation and finish callbacks:
 *
 * @code
 * function mymodule_batch_process($filepath, &$context) {
 *   module_load_include('inc', 'phpexcel');
 *
 *   if (!isset($context['sandbox']['progress'])) {
 *     $context['sandbox']['progress'] = 0;
 *     // We have no idea how many lines we have to load. Provide some large
 *     // number, and we'll adapt as we go along.
 *     $context['sandbox']['max'] = 10000;
 *   }
 *
 *   // We need to load the library before we can instantiate our
 *   // ChunkReaderFilter class.
 *   $library = libraries_load('PHPExcel');
 *   if (empty($library['loaded'])) {
 *     drupal_set_message(t("Couldn't load the PHPExcel library."), 'error');
 *     $context['sandbox']['finished'] = 1;
 *     $context['success'] = FALSE;
 *     return;
 *   }
 *
 *   $limit = 10;
 *   // See our module's info file below.
 *   $chunk_filter = new ChunkReadFilter();
 *   $chunk_filter->setRows($context['sandbox']['progress'], $limit);
 *   $data = phpexcel_import($filepath, TRUE, FALSE, array(
 *     'setReadFilter' => array($chunk_filter),
 *   ));
 *
 *   if (!is_array($data)) {
 *     drupal_set_message(t("Something went wrong on pass !pass", array(
 *       '!pass' => $context['sandbox']['progress'],
 *     )), 'error');
 *     $context['sandbox']['finished'] = 1;
 *     $context['success'] = FALSE;
 *     return;
 *   }
 *
 *   // Get rid of the worksheet.
 *   $data = $data[0];
 *
 *   $i = 0;
 *   while($i < $limit) {
 *     if (!empty($data[$i])) {
 *       // Do something with the data, like creating a node...
 *       $node = (object) array(
 *         'type' => 'page',
 *         'title' => $data[$i]['Header 1'],
 *       );
 *       node_save($node);
 *       $context['results'][] = $node;
 *       $context['sandbox']['progress']++;
 *       $i++;
 *     }
 *     else {
 *       // We have reached the end of our file. Finish now.
 *       $context['sandbox']['finished'] = 1;
 *       $context['success'] = TRUE;
 *       return;
 *     }
 *   }
 *
 *   if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
 *     $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
 *   }
 * }
 *
 * function mymodule_batch_finished($success, $results, $operations) {
 *   if ($success) {
 *     // Here we do something meaningful with the results.
 *     $message = t("!count items were processed.", array(
 *       '!count' => count($results),
 *     ));
 *     $message .= theme('item_list', array('items' => array_map(function($node) {
 *       return l($node->title, "node/{$node->nid}");
 *     }, $results)));
 *     drupal_set_message($message);
 *   }
 * }
 * @endcode
 *
 * In our batch, we use a custom class called ChunkReadFilter. We must define
 * it in its own file:
 *
 * @code
 * // Put this in a separate file, like src/ChunkReadFilter.php.
 *
 * class ChunkReadFilter implements PHPExcel_Reader_IReadFilter {
 *   protected $start = 0;
 *   protected $end = 0;
 *
 *   public function setRows($start, $chunk_size) {
 *     $this->start = $start;
 *     $this->end   = $start + $chunk_size;
 *   }
 *
 *   public function readCell($column, $row, $worksheetName = '') {
 *     // Only read the heading row, and the rows that are between
 *     // $this->start and $this->end.
 *     if (($row == 1) || ($row >= $this->start && $row < $this->end)) {
 *       return TRUE;
 *     }
 *     return FALSE;
 *   }
 * }
 * @endcode
 *
 * We need to register this file with Drupal's autoloader, which is done by
 * adding a files[] directive to the module's .info file:
 *
 * @code
 * ; mymodule.info
 * name = My module
 * core = 7.x
 * files[] = src/ChunkReadFilter.php
 * @endcode
 *
 * Now, we can set a batch operation like so:
 *
 * @code
 * batch_set(array(
 *   'operations' => array(
 *     array('mymodule_batch_process', array('/path/to/file.xls')),
 *   ),
 *   'finished' => 'mymodule_batch_finished',
 * ));
 * @endcode
 *
 * This will import all the rows from the /path/to/file.xls Excel file, and
 * create a node for each row.
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Implements hook_phpexcel_export().
 *
 * Allows modules to interact with the exporting of data. This hook is invoked
 * at different stages of the export, represented by the $op parameter.
 *
 * @see phpexcel_export()
 *
 * @param string $op
 *    The current operation. Can either be "headers", "new sheet", "data",
 *    "pre cell" or "post cell".
 * @param array|string &$data
 *    The data. Depends on the value of $op:
 *    - "headers": The $data parameter will contain the headers in array form.
 *      The headers have not been added to the document yet and can be altered
 *      at this point.
 *    - "new sheet": The $data parameter will contain the sheet ID. This is a
 *      new sheet and can be altered, if required, using the $phpexcel
 *      parameter.
 *    - "data": The $data parameter contains all the data to be exported as a
 *      3-dimensional array. The data has not been exported yet and can be
 *      altered at this point.
 *    - "pre cell": The $data parameter contains the call value to be rendered.
 *      The value has not been added yet and can still be altered.
 *    - "post cell": The $data parameter contains the call value that was
 *      rendered. This value cannot be altered anymore.
 * @param PHPExcel|PHPExcel_Worksheet $phpexcel
 *    The current object used. Can either be a PHPExcel object when working
 *    with the excel file in general or a PHPExcel_Worksheet object when
 *    iterating through the worksheets. Depends on the value of $op:
 *    - "headers" or "data": The $phpexcel parameter will contain the PHPExcel
 *      object.
 *    - "new sheet", "pre cell" or "post cell": The $phpexcel parameter will
 *      contain the PHPExcel_Worksheet object.
 * @param array $options
 *    The $options array passed to the phpexcel_export() function.
 * @param int $column
 *    The column number. Only available when $op is "pre cell" or "post cell".
 * @param int $row
 *    The row number. Only available when $op is "pre cell" or "post cell".
 *
 * @ingroup phpexcel_api
 */
function hook_phpexcel_export($op, &$data, $phpexcel, $options, $column = NULL, $row = NULL) {
  switch ($op) {
    case 'headers':

      break;

    case 'new sheet':

      break;

    case 'data':

      break;

    case 'pre cell':

      break;

    case 'post cell':

      break;
  }
}

/**
 * Implements hook_phpexcel_import().
 *
 * Allows modules to interact with the importing of data. This hook is invoked
 * at different stages of the import, represented by the $op parameter.
 *
 * @see phpexcel_import()
 *
 * @param string $op
 *    The current operation. Either "full", "sheet", "row", "pre cell" or
 *    "post cell".
 * @param mixed &$data
 *    The data. Depends on the value of $op:
 *    - "full": The $data parameter will contain the fully loaded Excel file,
 *      returned by the PHPExcel_Reader object.
 *    - "sheet": The $data parameter will contain the current
 *      PHPExcel_Worksheet.
 *    - "row": The $data parameter will contain the current PHPExcel_Row.
 *    - "pre cell": The $data parameter will contain the current cell value. The
 *      value has not been added to the data array and can still be altered.
 *    - "post cell": The $data parameter will contain the current cell value.
 *      The value cannot be altered anymore.
 * @param PHPExcel_Reader|PHPExcel_Worksheet|PHPExcel_Cell $phpexcel
 *    The current object used. Can either be a PHPExcel_Reader object when
 *    loading the Excel file, a PHPExcel_Worksheet object when iterating
 *    through the worksheets or a PHPExcel_Cell object when reading data
 *    from a cell. Depends on the value of $op:
 *    - "full", "sheet" or "row": The $phpexcel parameter will contain the
 *      PHPExcel_Reader object.
 *    - "pre cell" or "post cell": The $phpexcel parameter will contain the
 *      PHPExcel_Cell object.
 * @param array $options
 *    The arguments passed to phpexcel_import(), keyed by their name.
 * @param int $column
 *    The column number. Only available when $op is "pre cell" or "post cell".
 * @param int $row
 *    The row number. Only available when $op is "pre cell" or "post cell".
 *
 * @ingroup phpexcel_api
 */
function hook_phpexcel_import($op, &$data, $phpexcel, $options, $column = NULL, $row = NULL) {
  switch ($op) {
    case 'full':

      break;

    case 'sheet':

      break;

    case 'row':

      break;

    case 'pre cell':

      break;

    case 'post cell':

      break;
  }
}

/**
 * @} End of "addtogroup hooks".
 */

