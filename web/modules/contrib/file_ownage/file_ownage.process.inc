<?php

/**
 * @file
 * The actual processing functions are here. Only included when needed.
 */

use Drupal\Core\Site\Settings;
use Drupal\Core\Language\Language;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Scans the body text for embedded elements and triggers actions on them.
 *
 * A DOM Iterator way of filtering markup.
 *
 * This subroutine does NOT include a re-save of the given node.
 * Do that yourself.
 *
 * Implements hook_ACTION_ID_action().
 */
function file_ownage_scan_embeds_process_node(NodeInterface &$node, $settings = []) {

  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $strings = array(
  //     '!node' => l($node->getTitle(), 'node/' . $node->id()),
  //     '%function' => __FUNCTION__,
  //   );
  \Drupal::logger('file_ownage')->info("Running %function on !node", []);
  // Return this flag if a change needs to be made.
  $modified = FALSE;

  // Use the default global settings if none is defined.
  if (empty($settings)) {
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/file_ownage.settings.yml and config/schema/file_ownage.schema.yml.
    $settings = \Drupal::config('file_ownage.settings')->get('file_ownage_settings');
  }

  if (empty($node->body)) {
    // watchdog("file_ownage",
    // "No body content found in this node. Skipping !node.",
    // array('!node' => l($node->title, 'node/' . $node->nid)), WATCHDOG_DEBUG);.
    return FALSE;
  }

  // Need to run this on each textfield.
  $field_instances = field_info_instances('node', $node->getType());

  if (empty($field_instances)) {
    // This content type has no fields at all.
    return NULL;
  }

  // Look for text fields to process. This is expected to include the 'body',
  // but any others can get got also.
  foreach ($field_instances as $field_id => $field_def) {
    // Skip empty fields.
    if (empty($node->{$field_id})) {
      continue;
    }
    // Not sure where there is a generic handler for 'text' in this field_def
    // array. Use this.
    $field_type = $field_def['widget']['type'];
    switch ($field_type) {

      case 'text_textarea_with_summary':
      case 'text_textarea':
        // May be multiple. body is not usually multiple,
        // but the structure allows for it to be.
        foreach ($node->{$field_id}[$node->language] as $delta => $field_content) {
          $input = $field_content['value'];
          $output = file_ownage_scan_embeds_process_text($input, $node, $settings);
          if ($output === FALSE) {
            // Failed to process.
            \Drupal::logger('image_ownage')->notice("Trouble processing the field $field_id - no output.", []);
            continue;
          }
          else {
            if ($input != $output) {
              $modified = TRUE;
              $node->{$field_id}[$node->language][$delta]['value'] = $output;
            }
          }

        }
        break;

      default:
        // unsupported;.
    }
  }

  if ($modified) {
    // Remember to rebuild the teaser if changing the body,
    // or strange double-ups occur.
    unset($node->teaser);
  }
  return $modified;
}

/**
 * Subroutine for file_ownage_scan_embeds_process_node().
 *
 * Run our replacement on the given text string.
 *
 * @param string $input
 *   HTML markup.
 * @param object $node
 *   Owning Entity.
 * @param array $settings
 *   Context & Configs.
 *
 * @return bool|string
 *   The resulting text, FALSE on failure.
 */
function file_ownage_scan_embeds_process_text($input, $node, $settings = []) {

  $output = $input;

  $xmldoc = filter_dom_load($input);
  if (!$xmldoc) {
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // watchdog("file_ownage", "Text to be processed by file_ownage was <em>not valid XML</em>. Cannot proceed with scanning !node.", array('!node' => l($node->title, 'node/' . $node->nid)), WATCHDOG_WARNING);
    return FALSE;
  }
  $modified = FALSE;

  // Find all images in the page.
  $images = $xmldoc->getElementsByTagName('img');
  // Need to collect the elements in a pre-loop, otherwise changing the document
  // may throw off the indexing of the nodelist. This was a subtle trap.
  $imagelist = [];
  foreach ($images as $element) {
    $imagelist[] = $element;
  }
  foreach ($imagelist as $element) {
    \Drupal::logger('file_ownage')->info('Found an img element in the body text, triggering the file_ownage processing rule.', []);
    // todo, shift some of this logic into rules and actions?
    // $arguments = array(
    //  'element' => &$element,
    //  'node' => &$node,
    //  'settings' => $settings
    // );
    // rules_invoke_event('embed_element_found', $arguments);.
    if (file_ownage_process_img_element($element, $node, $settings)) {
      $modified = TRUE;
    }
  }

  // Also scan all the <a> links for references to files and resources.
  if ($settings['file_ownage']['import_files']) {
    $links = $xmldoc->getElementsByTagName('a');
    // Need to collect the elements in a pre-loop,
    // otherwise changing the document
    // may throw off the indexing of the nodelist. This was a subtle trap.
    /** @var $linklist DOMElement[] */
    $linklist = [];
    foreach ($links as $element) {
      $linklist[] = $element;
    }
    foreach ($linklist as $element) {
      $strings['%href'] = $element->getAttribute('href');
      \Drupal::logger('file_ownage')->info('Found a link element in the body text, triggering the file_ownage processing rule on %href.', []);
      if (file_ownage_process_link_element($element, $node, $settings)) {
        $modified = TRUE;
      }
    }
  }

  if ($modified) {
    $output = filter_dom_serialize($xmldoc);
  }

  return $output;
}

/**
 * Process an img element.
 *
 * @param domElement $element
 *   DOM tag currently being worked on.
 * @param object &$node
 *   Owning entity.
 * @param array $settings
 *   Configs & Context.
 *
 * @return bool
 *   Success.
 */
function file_ownage_process_img_element(domElement $element, stdClass &$node, array $settings) {

  $success = FALSE;
  $img = ['element' => $element];

  // Extract expected attributes from the HTML element.
  $atts = ['src', 'title', 'alt', 'width', 'height', 'class', 'style'];
  foreach ($atts as $att) {
    if ($val = $element->getAttribute($att)) {
      $img[$att] = $val;
    }
  }
  $working_src = ltrim($img['src'], '/');
  // This may get slightly munged in the process. Leave original $src alone.
  // If the remote URL has &amp; in it, then we need to convert that back to '&'
  // before attempting a file_copy. Maybe?
  // however that's the problem for the copy routine.
  // We need to keep the URL as encoded as it came.
  // these two worked against each other.
  // $working_src = urldecode($working_src);
  // If the remote URL has space in it,
  // then we need to encode that or later retrieval will fail.
  $working_src = str_replace(' ', '%20', $working_src);

  // Do we know it?
  // Ensure file exists and has a DB entry.
  // This func will search in other paths
  // and copy it across to the right location if needed.
  $file = file_ownage_get_file_entry($working_src, $settings, $node->uid);
  // The $file['filepath'] may have been updated to something better than the
  // $working_src. Don't forget to update that if needed.
  if (empty($file->fid) || !file_exists(\Drupal::service("file_system")->realpath($file->uri))) {
    // Still failing? Give up.
    \Drupal::logger('file_ownage')->error('Failed to deal with image %filepath. File could not be retrieved. Aborting.', ['%filepath' => $working_src]);
    return FALSE;
  }

  // We now have a DB entry, which contains what we need to know from here on
  // Merge the data array. $file overwrites our own data.
  $img = array_merge($img, (array) $file);

  // Deduce any extra data from context or other methods.
  // @see meta_inspector for this hook definition.
  $img['meta'] = \Drupal::moduleHandler()->invokeAll('metadata_from_file', [$img['uri'], $img]);

  // It's likely that this file needs a title to identify itself
  // so we can find it in the system later.
  if (empty($img['title'])) {
    if (isset($img['meta']['title'])) {
      $img['title'] = reset($img['meta']['title']);
    }
    else {
      // If the meta extraction failed to provide a title...
      // make a sensible one up.
      $img['title'] = $node->title . " : " . basename($img['src']);
    }
  }

  // We now know what we needed to know about the file.
  // Optionally, use the appropriate method to attach the file to the node.
  // (file attach, filefield, media module)
  // It may be a callback defined explicitly in the method info array,
  // or just be a magic function named directly after the method.
  $methods = file_ownage_attachment_methods();
  $method = $settings['image_ownage']['attach_method'];
  $strings['%method'] = $method;
  $method_definition = $methods[$method];
  $process_callback = isset($method_definition['process_callback']) ? $method_definition['process_callback'] : $method;
  if (function_exists($process_callback)) {
    // Shift the per-method subsettings array up into global scope.
    // This makes lazy variable-name re-use possible. EG filefield_name.
    if (!empty($settings['image_ownage'][$method]) && is_array($settings['image_ownage'][$method])) {
      $settings += $settings['image_ownage'][$method];
    }
    // Call the callback!
    // drupal_set_message('Invoking ' . $process_callback);.
    $success = $process_callback($node, $img, $settings) || $success;
  }
  else {
    \Drupal::logger('file_ownage')->error('No process function callback available for method %method', []);
  }
  \Drupal::logger('file_ownage')->debug('Finished processing the file absorbtion callback for one file, used method: %method', []);
  return $success;
}

/**
 * Process a link element.
 *
 * Most links will be to html pages, either local or remote.
 * We only care about 'files' and downloadable resources, so filter for them.
 * If found, those files can be owned.
 *
 * @param domElement $element
 *   The anchor tag.
 * @param object $node
 *   The owner entity.
 * @param array $settings
 *   Configs and context.
 *
 * @return bool
 *   Success.
 */
function file_ownage_process_link_element(domElement $element, &$node, array $settings) {

  $success = FALSE;
  $link = ['element' => $element];

  // Extract expected attributes from the HTML element.
  $atts = ['href', 'title'];
  foreach ($atts as $att) {
    $link[$att] = $element->getAttribute($att);
  }
  $working_src = ltrim($link['href'], '/');
  // This may get slightly munged in the process. Leave original $src alone.
  // If the remote URL has &amp; in it, then we need to convert that back to '&'
  // before attempting a file_copy. Maybe?
  // however that's the problem for the copy routine.
  // We need to keep the URL as encoded as it came.
  // these two worked against each other.
  // $working_src = urldecode($working_src);
  // If the remote URL has space in it,
  // then we need to encode that or later retrieval will fail.
  $working_src = str_replace(' ', '%20', $working_src);

  // Do we care?
  if (!file_ownage_wanted_filetype($working_src, $settings['file_ownage']['suffix_list'])) {
    return FALSE;
  }
  \Drupal::logger('file_ownage')->info('linked file %filepath seems to be something we should own.', ['%filepath' => $working_src]);

  // Do we know it?
  // Ensure file exists and has a DB entry.
  // This func will search in other paths and copy it across to the right
  // location if needed.
  $file = file_ownage_get_file_entry($working_src, $settings, $node->uid);
  // The $file->uri may have been updated to something
  // better than the $working_src. Don't forget to update that if needed.
  if (empty($file->fid) || !file_exists(\Drupal::service("file_system")->realpath($file->uri))) {
    // Still failing? Give up.
    \Drupal::logger('file_ownage')->error('Failed to deal with linked file %filepath. File could not be retrieved.', ['%filepath' => $working_src]);
    return FALSE;
  }

  // We now have a DB entry, which contains what we need to know from here on
  // Merge the data array. The data from the $file needs to take precedence.
  $link = array_merge($link, (array) $file);

  // Deduce any extra data from context or other methods.
  // @see meta_inspector for this hook definition.
  $link['meta'] = \Drupal::moduleHandler()->invokeAll('metadata_from_file', [$link['uri'], $link]);

  // We now know what we needed to know about the file.
  // TODO, should we jazz up the link with a title and filesize?
  // Use the appropriate method to attach the file.
  // It may be a callback defined explicitly in the method info array,
  // or just be a magic function named directly after the method.
  $methods = file_ownage_file_attachment_methods();
  $method = $settings['file_ownage']['attach_method'];
  $method_definition = $methods[$method];
  $process_callback = isset($method_definition['process_callback']) ? $method_definition['process_callback'] : $method;
  if (function_exists($process_callback)) {
    // Shift the per-method subsettings array up into global scope.
    // This makes lazy variable-name re-use possible. EG filefield_name.
    if (!empty($settings['file_ownage'][$method]) && is_array($settings['file_ownage'][$method])) {
      $settings += $settings['file_ownage'][$method];
    }
    // Call the callback!
    // drupal_set_message('Invoking ' . $process_callback);.
    $success = $process_callback($node, $link, $settings) || $success;
  }
  else {
    \Drupal::logger('file_ownage')->error('No process function callback available for method ' . $settings['file_ownage']['attach_method'], []);
  }
  \Drupal::logger('file_ownage')->debug('Finished processing the file absorbtion callback for one file ' . $settings['file_ownage']['attach_method'], []);
  return $success;
}

/**
 * Creates the named file as an filefield attachment, returns success.
 *
 * Attaches it as the chosen supplimentary filefield on the node.
 *
 * @param object $node
 *   Entity to work on.
 * @param array $file
 *   Representing a drupal prepared file entry.
 * @param array $settings
 *   Context and configs.
 *
 * @return bool
 *   Whether the attachment happened.
 *   If the file is already attached, no change is made, so FALSE.
 */
function file_ownage_attach_filefield(&$node, array $file, array $settings = []) {

  // Last-minute paranoia.
  if (!\Drupal::moduleHandler()->moduleExists('file')) {
    drupal_set_message(t('file module not enabled - not attaching file to node'), 'warning');
    return FALSE;
  }

  $filefield_name = $settings['filefield_name'];
  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $strings = array(
  //     '!uri' => $file['uri'],
  //     '%filefield_name' => $filefield_name,
  //     '!node' => l($node->title, 'node/' . $node->nid),
  //   );
  \Drupal::logger('file_ownage')->info("Checking if we need to attach !uri to the node !node as a filefield upload %filefield_name", []);

  // Confirm the field exists and is valid for this node type.
  $strings['%type'] = $node->type;
  $fields = field_info_instances('node', $node->type);
  if (empty($fields[$filefield_name])) {
    \Drupal::logger('file_ownage')->error('Filefield %filefield_name is not available on this %type content type. Check the file_ownage settings', []);
    return FALSE;
  }
  // Can't use the field definition from the $fields array,
  // $field_instance = $fields[$filefield_name];
  // It doesn't know its own cardinality - instead use.
  $field = field_info_field($filefield_name);

  $modified = FALSE;
  // Avoid attaching the same file twice.
  // Because of the recycling we are doing, this is possible (but unwanted).
  $already_attached = FALSE;
  foreach ((array) $node->{$filefield_name}[Language::LANGCODE_NOT_SPECIFIED] as $existing_entry) {
    if ($existing_entry['fid'] == $file['fid']) {
      \Drupal::logger('file_ownage')->notice("Looks like !uri is already attached to this node !node. Fine, move along.", []);
      $already_attached = TRUE;
    }
  }

  if (!$already_attached) {
    // If this field is restricted, and already populated,
    // we won't replace it.
    if (($field['cardinality'] != FIELD_CARDINALITY_UNLIMITED) && !empty($node->$filefield_name)) {
      $current_attachment = reset($node->$filefield_name);
      if (empty($current_attachment)) {
        // An empty $node->$field_name should be null,
        // but sometimes it's an array(0). Check carefully.
      }
      else {
        \Drupal::logger('file_ownage')->notice("This node !node already has a %filefield_name assigned and cardinality is limited. Not attaching a new one.", []);
        return FALSE;
      }
    }
  }

  if (!$already_attached) {
    // Add it. Update the filefield data and attach to the node.
    $file['display'] = isset($field['settings']['display_default']) ? $field['settings']['display_default'] : NULL;
    $file['description'] = (!empty($alt)) ? $alt : $file['filename'];
    $file['weight'] = 0;
    $file['new'] = TRUE;
    $file['title'] = isset($file['title']) ? $file['title'] : basename($file->filename);

    // Programatically attach a filefield to a node.
    if (empty($node->$filefield_name)) {
      $node->$filefield_name = [];
    }
    $node->{$filefield_name}[Language::LANGCODE_NOT_SPECIFIED][] = $file;

    \Drupal::logger('file_ownage')->info("Successfully attached !uri to the node !node as a filefield upload %filefield_name", []);
    $modified = TRUE;
  }

  // In case we moved or renamed the file during this process,
  // the link in the source will need to be updated
  // to point at the new location.
  $modified |= file_ownage_update_element_path($file, $settings);

  return $modified;
}

/**
 * Dummy 'process' function.
 *
 * What subroutine to do when 'no attachment' is selected.
 *
 * A remote file may still have been imported, or a local one renamed.
 * This means the tag in the HTML source needs updating, but nothing more.
 *
 * @param object $node
 *   Entity to work on.
 * @param array $tag
 *   Tag being processed.
 * @param array $settings
 *   Context and Configs.
 *
 * @return bool
 *   Modified or not.
 */
function file_ownage_attach_none(&$node, array $tag, array $settings = []) {
  $modified = FALSE;
  $modified |= file_ownage_update_element_path($tag, $settings);
  return $modified;
}

/**
 * Find or create the file entry for the given file path. Fetching it if needed.
 *
 * If there is a valid entry already in the DB, return that.
 *
 * Otherwise, check if alternatives for that filepath are available
 * or if file needs to be imported to the filesystem,
 * copying it into the right place.
 * Do that, and make a file entry.
 *
 * $settings['path'] is relative to the drupal file system root.
 *
 * @param string $filepath
 *   Filepath to look up.
 * @param array $settings
 *   Configs & Context.
 * @param int $uid
 *   ID to make the file table entry as, defaults to current user.
 *   user ID to make the file table entry as, defaults to current user.
 *
 * @return object
 *   $file object (not an array)
 */
function file_ownage_get_file_entry($filepath, array $settings, $uid = NULL) {

  $strings = ['%filepath' => $filepath];

  // First see if the database already knows about this file.
  // Lookahead, expect failure first time.
  $file = file_ownage_load_file_by_filepath($filepath);
  if ($file) {
    \Drupal::logger('file_ownage')->info('File entry %filepath already exists in the DB, re-using it.', []);
    return $file;
  }
  // Try harder.
  // See if the path is correct and valid.
  // Where SHOULD it be stored?
  $expected_fileuri = file_ownage_correct_path($filepath, $settings);
  $file = file_ownage_load_file_by_filepath($expected_fileuri);
  if ($file) {
    return $file;
  }

  // Looks like we may need to import it.
  // Check we have the biggest/best alternative version of the file
  // HOW, todo - this links in, so far, only to our custom
  // niwa_import_html_file_ownage_best_version() special algorithm.
  // This algorithmic search may support local files or remote URLs.
  \Drupal::logger('file_ownage')->info('Seeing if I can guess a better place to look for the file %filepath. (Optional feature that can expand found thumbs into bigger originals)', []);
  $largers = \Drupal::moduleHandler()->invokeAll('file_ownage_best_version', [$filepath]);
  if (!empty($largers)) {
    $filepath = reset($largers);
    $expected_fileuri = file_ownage_correct_path($filepath, $settings);
    $strings['%larger'] = $filepath;
    \Drupal::logger('file_ownage')->info('There is a better source path for this file. Using %larger instead of %filepath.', []);
    $strings['%filepath'] = $filepath;
    $file = file_ownage_load_file_by_filepath($expected_fileuri);
    if ($file) {
      return $file;
    }
    $filepath = $filepath['file'];
  }

  // Decide if it's local, nearby or remote.
  $path_status = file_ownage_path_status($filepath);

  if (!$path_status) {
    // File wasn't where we expected to find it.
    // Time to start looking for alternatives.
    $search_status = file_ownage_search_for_lost_files($filepath, $settings);
    if ($search_status) {
      $path_status |= $search_status;
    }

    // That may have updated the filepath by reference
    // to point at the found target.
    $strings['%filepath'] = $filepath;

    // If searching for it successfully found the file in another location
    // then maybe that location already has an entry and we don't have to
    // copy. return the result immediately.
    if ($path_status == FILE_OWNAGE_IS_REGISTERED) {
      if ($file = file_ownage_load_file_by_filepath($filepath)) {
        \Drupal::logger('file_ownage')->info('File entry %filepath already exists in the DB, re-using it.', []);
        return $file;
      }
    }
  }
  if (!$path_status) {
    // Couldn't find it anywhere local or remote yet.
    // Basically, there is nothing I can do.
    \Drupal::logger('file_ownage')->notice('Haven\'t found file %filepath anywhere. Giving up.', []);
    return FALSE;
  }

  $strings['%expected_fileuri'] = $expected_fileuri;
  $copied = FALSE;

  // Maybe move it to where it should be stored, and create a DB entry.
  // Import or move it if needed.
  if ($filepath != $expected_fileuri) {
    if ($path_status == FILE_OWNAGE_IS_REMOTE) {
      // Handle our conditional import remote conditions.
      $import_domain_handler = 'ownage';
      $import_domain_filters = [];

      // Add blacklist and whitelist handling
      // And default domains for guessing where to find missing content.
      if (!empty($settings['domains'])) {
        if (in_array($settings['domains']['domain_handler'], ['include', 'exclude'])) {
          $import_domain_filters = $settings['domains']['domain_list'];
          array_map('trim', $import_domain_filters);
          $import_domain_handler = $settings['domains']['domain_handler'];
        }
      }

      // Default import remote action is True.
      $import_remote = TRUE;

      // Do we need to modify the import_remote option?
      switch ($import_domain_handler) {
        case 'include':
          // Only including if triggered. Set default state to False.
          $import_remote = FALSE;
          foreach ($import_domain_filters as $filter) {
            $pos = strpos($filepath, $filter);
            if (0 === $pos) {
              // Found one.
              $import_remote = TRUE;
            }
          }
          break;

        case 'exclude':
          foreach ($import_domain_filters as $filter) {
            $pos = strpos($filepath, $filter);
            if (0 === $pos) {
              $import_remote = FALSE;
            }
          }
          break;

        case 'ownage':
          $import_remote = TRUE;
          break;

        default:
          $import_remote = FALSE;
          break;
      }

      // Looks like we need to move it.
      if ($import_remote) {
        $copied = file_ownage_file_copy($filepath, $expected_fileuri);
        if ($copied) {
          \Drupal::logger('file_ownage')->notice("Imported remote file from %filepath to %expected_fileuri. 0WNAGE!", []);
        }
        else {
          \Drupal::logger('file_ownage')->error("Failed remote file retrieval of %filepath in file_ownage_get_file_entry(). It probably wasn't there", []);
        }
      }
      else {
        \Drupal::logger('file_ownage')->notice("Not copying from remote %filepath due to domain restriction rules", []);
      }
    }
    elseif ($path_status == FILE_OWNAGE_IS_NEARBY && $settings['import_nearby']) {
      // Needs to be moved into the files dir.
      $copied = file_ownage_file_copy($filepath, $expected_fileuri);
    }
    elseif ($path_status == FILE_OWNAGE_IS_LOCAL) {
      // Is already in the files dir, but we have a better destination for it.
      $copied = file_ownage_file_copy($filepath, $expected_fileuri);
    }
    elseif ($path_status == FILE_OWNAGE_IS_REGISTERED) {
      // ?? I think we cannot get here. if IS_REGISTERED,
      // would have returned earlier after the search happened.
      $strings['%function'] = __FUNCTION__;
      \Drupal::logger('file_ownage')->alert("I don't think I should get to this part of the function %function", []);
      // It is already in the files dir, and the system is already using it.
      // re-use that entry.
      if ($file = file_ownage_load_file_by_filepath($filepath)) {
        \Drupal::logger('file_ownage')->info('File entry %filepath already exists in the DB, re-using it.', []);
        return $file;
      }
    }
    else {
      // This would be the case if the source was found on the system,
      // but outside the Drupal root.
      \Drupal::logger('file_ownage')->debug('File may need moving from %filepath to %expected_fileuri but preferences do not allow that right now.', []);
      $copied = file_ownage_file_copy($filepath, $expected_fileuri);
    }

    if ($copied) {
      \Drupal::logger('file_ownage')->info('I have a copy of %filepath at %expected_fileuri', []);
    }
    elseif (file_exists(\Drupal::service("file_system")->realpath($expected_fileuri))) {
      \Drupal::logger('file_ownage')->info('Expected file was found already at %expected_fileuri', []);
    }
    else {
      \Drupal::logger('file_ownage')->error('Failed to copy file from %filepath to %expected_fileuri. Giving up.', []);
      return FALSE;
    }
  }

  // Paranoia - Really check that it all worked before proceeding.
  if (file_exists(\Drupal::service("file_system")->realpath($expected_fileuri))) {
    // Make (or retrieve) DB entry now.
    $file = file_ownage_insert_filename_into_db($expected_fileuri, $uid);
    // Core returns $file as an object.
  }
  else {
    $strings['%realpath'] = \Drupal::service("file_system")->realpath($expected_fileuri);
    \Drupal::logger('file_ownage')->error('File entry %expected_fileuri is still not where I wanted it (%expected_fileuri = "%realpath"). Why?', []);
    return FALSE;
  }

  if (empty($file->fid)) {
    // This means that inserting it into the database failed. Have to give up.
    \Drupal::logger('file_ownage')->warning('File entry %expected_fileuri exists, but could not be inserted in the db. Why?', []);
    return FALSE;
  }
  return $file;
}

/**
 * Create a database file entry and get the new fid.
 *
 * @param string $fileuri
 *   Filepath or URL.
 * @param int $uid
 *   User ID.
 *
 * @return object
 *   The $file description data, should include a $file->fid;
 */
function file_ownage_insert_filename_into_db($fileuri, $uid = NULL) {

  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $strings = array('!filepath' => l($fileuri, $fileuri));
  if (!file_exists(\Drupal::service("file_system")->realpath($fileuri))) {
    \Drupal::logger('file_ownage')->warning("NOT inserting file '!filepath' to db as it's invalid/unavailable", []);
  }

  // $file = file_uri_to_object($fileuri);
  $file = (object) [
    'uri' => $fileuri,
    'filename' => basename($fileuri),
    'filemime' => file_get_mimetype($fileuri),
    'status' => FILE_STATUS_PERMANENT,
  ];

  // Almost ready to make a DB record.
  if ($uid) {
    $file->uid = $uid;
  }
  else {
    $user = \Drupal::currentUser();
    $file->uid = $user->id();
  }
  \Drupal::service("file_system")->chmod($file->uri);
  file_save($file);

  if (empty($file->fid)) {
    // This means that inserting it into the database failed. Have to give up
    // Unknown cause, can this even happen?
    \Drupal::logger('file_ownage')->warning('File entry !filepath could not be inserted in the db. Something low happened', []);
    return FALSE;
  }

  \Drupal::logger('file_ownage')->info("Registered !filepath as a new entry in the DB", []);
  return $file;
}

/**
 * Ensure that the DOM Element link attribute is set to the current filepath.
 *
 * The $file data array we are given must have an element attached, which
 * will be part of the DOM being worked on.
 * We update it directly and the update happens by reference.
 *
 * The DOM Element may be an img or an a. Or others?
 * So we figure it out on the fly.
 *
 * @param array $tag
 *   Tag info extracted from the DOM, including an amount of deductions
 *   made so far.
 * @param array $settings
 *   Context & Config.
 *
 * @return bool
 *   Whether any change was actually made.
 */
function file_ownage_update_element_path(array $tag, $settings = []) {

  $attr = '';
  // We may be dealing with img src or a hrefs here. Don't actually know.
  if ($tag['element']->nodeName == 'a') {
    $attr = 'href';
  }
  if ($tag['element']->nodeName == 'img') {
    $attr = 'src';
  }
  // TODO: NOT WANT this to be absolute
  // $url = file_create_url($tag['uri']);.
  $url = Url::fromUri(file_ownage_create_root_relative($tag['uri'], $settings))->toString();

  if ($attr && ($tag['element']->getAttribute($attr) != $url)) {
    $tag['element']->setAttribute($attr, $url);
    \Drupal::logger('file_ownage')->notice('Relinked element %attr to point at <a href=":url">:url</a>.', [':url' => $url, '%attr' => $attr]);
    return TRUE;
  }
  return FALSE;
}

/**
 * Returns true if the given filepath seems to have a suffix matching the list.
 *
 * @param string $filepath
 *   File URI or URL.
 * @param string $suffixes
 *   List of suffixes we want to process.
 *
 * @return bool
 *   Is this a useful file?
 */
function file_ownage_wanted_filetype($filepath, $suffixes) {
  $suffix_list = explode(',', $suffixes);
  $suffix_list = array_map('trim', $suffix_list);
  $suffix = pathinfo($filepath, PATHINFO_EXTENSION);
  if (in_array($suffix, $suffix_list)) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Search in alternative locations for the expected file.
 *
 * If a better location is found, $filepath will be updated by reference.
 *
 * The settings should define a list of paths - these can be
 * remote URLs,
 * local filesystem paths either inside or outside webroot,
 * or local SEARCH paths to try scanning from.
 *
 * eg
 * http://example.com/downloads/
 * /var/backups/oldsite/images/
 * sites/default/files/imported/
 * /var/backups/files/*
 *
 * If it's not a search, the exact path will be checked.
 * If it is a search, the exact path, and then anything
 * approximating it (by truncating the start of the search string)
 * will be looked for.
 *
 * TODO start thinking about security implications of allowing a
 * filesystem scan that grabs files from elsewhere on the server
 * and makes them public!
 * Currently, this is by design, as we want /var/backups/oldsite/files/*
 * to be a valid source. But...
 *
 * @param string $filepath
 *   Gets modified by reference.
 * @param array $settings
 *   Configs & Context.
 *
 * @return string|bool
 *   The location status. FALSE if not found.
 */
function file_ownage_search_for_lost_files(&$filepath, array $settings) {

  if (empty($settings['seek_paths'])) {
    return FALSE;
  }
  \Drupal::logger('file_ownage')->notice('File %filepath is not found yet. Starting to scan alternate fallback locations it may be.', ['%filepath' => $filepath]);
  // This process MAY produce false matches, but is often correct,
  // and better than the broken link you currently have.
  $seek_paths = $settings['seek_paths'];
  $seek_paths = array_map('trim', $seek_paths);
  $status = FALSE;
  foreach ($seek_paths as $search_path) {
    // There are two types of scan, absolute and wildcard glob.
    // See if the file is *anywhere* under the given filepath.
    if (preg_match('%\*$%', $search_path)) {
      // Need to glob
      // Try to use as much of the path as we can,
      // then chop it back to get more random.
      // Use the shell find, because otherwise we have to do a LOT of work.
      $dir = escapeshellarg(preg_replace('%/\*%', '', $search_path));
      $excludes = implode(
        ' ',
        [
          "! -path '*/imagecache/*'",
          "! -path '*/tmp/*'",
        ]
      );
      // Trim the search string until something turns up.
      $search_for = $filepath;
      while (!empty($search_for)) {
        $strings = [
          '%search_for' => $search_for,
          '%search_path' => $search_path,
        ];
        \Drupal::logger('file_ownage')->debug('Searching filesystem under %search_path to try and find something matching %search_for', []);

        $search = " -path " . escapeshellarg('*/' . $search_for);
        $command = "find $dir $excludes $search";
        $output = [];
        $return = exec($command . ' 2>&1', $output, $result);

        // If the $output has anything in, use the shortest one we find.
        if ($result) {
          $strings = [
            '%command' => $command,
            '%output' => implode("\n", $output),
            '%return' => $return,
          ];
          \Drupal::logger('file_ownage')->warning('File search failed. I ran <pre>%command</pre> and the response was: <pre>%output</pre>', []);
        }
        elseif (!empty($output)) {
          // Cheap order sort. there may be collisions, don't care
          // Create a list indexed by string length,
          // sort by keys and pop the top.
          $ordered_output = array_combine(array_map('strlen', $output), $output);
          ksort($ordered_output);
          $filepath = reset($ordered_output);
          $filepath = realpath($filepath);
          \Drupal::logger('file_ownage')->info('Looks like we found %filepath at %search_path after searching!!', ['%filepath' => $filepath, '%search_path' => $search_path]);
          return file_ownage_path_status($filepath);
        }
        // If not found yet, shorten the search pattern from the left.
        // Eventually we will just end with the filename.
        $search_for = preg_replace('%^[^/]*/?%', '', $search_for);
      } // end loop that shortens the search parameter
    }
    else {
      $possible_path = $search_path . $filepath;
      // We can't yet be sure if remote files exist.
      if ($status = file_ownage_path_status($possible_path)) {
        if ($status == FILE_OWNAGE_IS_REMOTE) {
          \Drupal::logger('file_ownage')->info('We are now hoping that %filepath is at remote location %possible_path', ['%filepath' => $filepath, '%possible_path' => $possible_path]);
        }
        else {
          \Drupal::logger('file_ownage')->info('Looks like we found %filepath at %possible_path after all', ['%filepath' => $filepath, '%possible_path' => $possible_path]);
        }
        $filepath = $possible_path;
        return $status;
      }
    }
  }

  return $status;
}

/**
 * Return the path we ACTUALLY want to save the named file in.
 *
 * TODO - extend this to maybe support more directories for different filetypes
 * or other logic.
 *
 * Repair silly characters while we are here.
 *
 * @param string $src
 *   probably a full URL. Definately a filename of some sort.
 * @param array $settings
 *   Maybe useful for context.
 *
 * @return string
 *   A cleaned up file Drupal URI, defining where we want this to go.
 */
function file_ownage_correct_path($src, array $settings) {

  // First, strip of the files folder if it's there. (D6 legacy or hard-coded)
  $safe_filename = str_replace(Settings::get('file_public_path'), '', $src);

  // Settings may be able to tell us about redundant folder names etc
  // that we can remove.
  foreach ((array) $settings['strip_paths'] as $pattern) {
    $safe_filename = preg_replace('%' . $pattern . '%', '', $safe_filename);
  }
  // Get URL-encoded characters.
  $safe_filename = preg_replace('/\%\d\d/', '-', $safe_filename);
  // Get double slashes.
  $safe_filename = preg_replace('@/+@', '/', $safe_filename);
  // Get wierd characters.
  $safe_filename = preg_replace('%[^a-zA-Z0-9_\./]%', '-', $safe_filename);
  // Save to a designated subfolder.
  if (!empty($settings['storage_path'])) {
    $safe_filename = trim($settings['storage_path'], '/') . '/' . $safe_filename;
  }
  $ret = file_build_uri(ltrim($safe_filename, '/'));
  return $ret;
}

/**
 * Return a path suitable for linking locally.
 *
 * File_create_url() is always absolute,
 * but I don't want absolute links in my HTML.
 *
 * Takes 'public://path/file.ext' and returns
 * '/sites/default/files/path/file.ext'
 *
 * @param string $uri
 *   Given file URI.
 * @param array $settings
 *   Context and configs.
 *
 * @return string
 *   New path.
 */
function file_ownage_create_root_relative($uri, array $settings) {

  if (!empty($settings['prettyfiles'])) {
    // We actually want to create an URL like /files/filename.jpg
    // and chop off the sites/{sitename} bit. See Aegir prettyfiles.
    // This could probably be done globally via hook_file_url_alter()
    // but we really just need to chop off the scheme.
    return str_replace('public://', 'files/', $uri);
  }
  // @FIXME
  // url() expects a route name or an external URI.
  // return str_replace(url('', array('absolute' => TRUE)), '', file_create_url($uri));
}

/**
 * Check if a file uri is considered local or not.
 *
 * Used in my local wrappers to file_exists() and file_copy()
 *
 * Stolen from file_entity.module.
 *
 * @param string $uri
 *   A file uri, likely either public://something or http://something.
 *
 * @return bool
 *   TRUE if the file is using a local stream wrapper, or FALSE otherwise.
 */
function file_ownage_file_is_local($uri) {
  $scheme = \Drupal::service("file_system")->uriScheme($uri);
  $wrappers = \Drupal::service("stream_wrapper_manager")->getWrappers(STREAM_WRAPPERS_LOCAL);
  return !empty($wrappers[$scheme]) && empty($wrappers[$scheme]['remote']);
}

/**
 * Adaptation of file_exists(), also checks remote URLs.
 *
 * @param string $uri
 *   Local or remote place to look for a file.
 *
 * @returns bool:string
 *   FALSE on failure, the URI on success.
 */
function file_ownage_file_exists($uri) {
  $found_uri = FALSE;
  if (file_ownage_file_is_local($uri)) {
    if (file_exists($uri)) {
      $found_uri = $uri;
    }
  }
  else {
    // Non-local URL scheme.
    if (file_ownage_remote_file_exists($uri)) {
      $found_uri = $uri;
    }
  }
  return $found_uri;
}

/**
 * Can we fetch the given URL?
 *
 * A quick(?) stand-in for file_exists to use on external URLs.
 * Your network behaviour may well make this a very slow call!
 * To avoid getting stuck with that,
 * it's got a 2 second timeout to fail fast.
 *
 * @param string $uri
 *   URI to check.
 *
 * @return bool
 *   Found?
 */
function file_ownage_remote_file_exists($uri) {
  // Why is cleaning up a URI MY job? Sheesh!.
  $uri = preg_replace('| |', '%20', $uri);
  // @FIXME
  // drupal_http_request() has been replaced by the Guzzle HTTP client, which is bundled
  // with Drupal core.
  //
  //
  // @see https://www.drupal.org/node/1862446
  // @see http://docs.guzzlephp.org/en/latest
  // $response = drupal_http_request($uri, array('method' => 'HEAD', 'timeout' => 2));
  if (empty($response)) {
    return FALSE;
  }
  if ($response->code != 200) {
    $file_exists = FALSE;
  }
  else {
    $file_exists = TRUE;
  }
  return $file_exists;
}

/**
 * Copy a possibly remote file to our local destination.
 *
 * Possibly renaming it as we go.
 *
 * Actually downloads to a temp file, then compares the temp with anything at
 * the planned destination.
 * If it matches, the path is already OK and the copy discarded.
 * This returns TRUE, as if a copy really happened.
 *
 * If not, a duplicate filename is generated and new content placed there. $dst
 * is modified by ref.
 *
 * @param string $src
 *   Source filepath, uri or whatever.
 * @param string $dst
 *   Destination filepath.
 *
 * @return bool
 *   Success. $dst may be updated to the real path that got saved.
 */
function file_ownage_file_copy($src, &$dst) {
  $local = file_ownage_file_is_local($src);
  if (empty($src) || empty($dst)) {
    \Drupal::logger('file_ownage')->error('Bad parameters to file_ownage_file_copy. $src = "!src" and $dst = "!dst". ' .
      'Neither of these can be empty.', ['!src' => $src, '!dst' => $dst]);
    return FALSE;
  }
  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $strings['!src'] = l($src, $src);
  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $strings['!dst'] = l($dst, $dst);
  $strings['%local'] = '%local' ? 'local' : 'remotely sourced';
  \Drupal::logger('file_ownage')->info("Copying! (unless we already have it) %local !src to !dst.", []);

  // Drupal file_copy will not handle URLs.
  // Could try drupal_http_request for remotes, but trying by hand today.
  // Need to make it local before we try that.
  $temppath = tempnam(file_directory_temp(), 'tmp_');

  if ($local) {
    \Drupal::logger('file_ownage')->debug("Making a local copy of %src", []);
    if (!copy($src, $temppath)) {
      \Drupal::logger('file_ownage')->error("Copy of %src to %temppath failed", []);
      return FALSE;
    }
  }
  else {
    // REMOTE.
    \Drupal::logger('file_ownage')->debug("Taking a copy of remote !src", []);
    if (!file_ownage_remote_file_copy($src, $temppath)) {
      \Drupal::logger('file_ownage')->error("Copy of %src to %temppath failed", []);
      return FALSE;
    }
  }

  // So far so good.
  // Should check the file a little, ensure that it's valid and all.
  // If we expected an image, check it's an image.
  // If we don't know what to expect, can't validate more than that.
  $suffix = strtolower(pathinfo($temppath, PATHINFO_EXTENSION));
  if (in_array($suffix, ['jpg', 'gif', 'png', 'jpeg'])) {
    $image_info = image_get_info($temppath);
    if (!$image_info || !isset($image_info['extension'])) {
      \Drupal::logger('file_ownage')->error("Retrieved file from $src ($temppath) is not a valid image. Only JPG, PNG and GIF files are allowed.", []);
      unlink($temppath);
      return FALSE;
    }
  }

  // Move with possible renaming.
  // ONLY IF THE EXISTING FILE DOES NOT LOOK LIKE A DUPE.
  if (file_exists($dst)) {
    if (@filesize($dst) == @filesize($temppath)) {
      \Drupal::logger('file_ownage')->error("Name and filesize match between %src and the pre-existing %dst. I'll call that a duplicate and link to it.", ['%src' => $src, '%dst' => $dst]);
      // Can just return and pretend the $dst is corrent.
      file_unmanaged_delete($temppath);
      return TRUE;
    }
    else {
      \Drupal::logger('file_ownage')->error("Possible conflict of names. %src is attempting to overwrite %dst. They are not identical, so a renamed COPY will be made instead.", ['%src' => $src, '%dst' => $dst]);
    }
  }

  // By now we guess it is NOT a dupe.
  // Use drupal file move because it handles auto-renaming.
  $target_dir = dirname($dst);
  file_prepare_directory($target_dir, FILE_CREATE_DIRECTORY);
  if ($dst = file_unmanaged_move($temppath, $dst, FILE_EXISTS_RENAME)) {
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $strings = array('!src' => l($src, $src), '!dst' => l($dst, $dst));
    \Drupal::logger('file_ownage')->info("Moved downloaded copy from !src over to !dst.", []);
    return TRUE;
  }
  else {
    \Drupal::logger('file_ownage')->error("Failed to transfer our local copy of $src ($temppath) over to $dst.", []);
    return FALSE;
  }
}

/**
 * Remote-compatible version of file_copy().
 *
 * Uses drupal_http_request and checks the response code.
 *
 * @param string $source
 *   Probably an http URL.
 * @param string $destination
 *   Local storage filepath.
 *
 * @return bool
 *   Success.
 */
function file_ownage_remote_file_copy($source, $destination) {
  // Why is cleaning up a URI MY job? Sheesh!.
  $source = preg_replace('| |', '%20', $source);
  // And why doesn't native file_copy() work for me?
  // I guess we need to respect proxy, timeouts and things.
  // @FIXME
  // drupal_http_request() has been replaced by the Guzzle HTTP client, which is bundled
  // with Drupal core.
  //
  //
  // @see https://www.drupal.org/node/1862446
  // @see http://docs.guzzlephp.org/en/latest
  // $response = drupal_http_request($source, array('timeout' => 10));.
  if ($response->code == 200) {
    // Prepare local target directory and save downloaded file.
    $target_dir = dirname($destination);
    if (file_prepare_directory($target_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      file_put_contents($destination, $response->data);
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Older super-manual method to file_ownage_remote_file_copy().
 *
 * @param string $source
 *   Probably an http URL.
 * @param string $destination
 *   Local storage filepath.
 *
 * @return bool
 *   Success.
 *
 * @deprecated
 */
function file_ownage_remote_file_copy_stream($source, $destination) {

  // Don't use fopen, it doesn't do context until PHP5.3
  // Retrieve, with context to fudge the headers and bypass some anti-leech
  // DOES NOT work on all antileech.
  $opts = [
    'http' => [
      'header' => "Referer: $source\r\n",
    ],
  ];
  $context = stream_context_create($opts);
  // Why is cleaning up a URI MY job? Sheesh!.
  $source = preg_replace('| |', '%20', $source);
  $file_src = fopen($source, 'r', FALSE, $context);
  if (!$file_src) {
    \Drupal::logger('file_ownage')->error("Problem in file_ownage_file_copy fetching remote file %source", ['%source' => $source]);
    return FALSE;
  }
  $file_dst = fopen($destination, 'w');
  while (!feof($file_src)) {
    fwrite($file_dst, fread($file_src, 8192));
  }
  fclose($file_src);
  fclose($file_dst);
  return TRUE;
}

/**
 * Make Imageholder Node, a node that exists mainly to contain a filefield.
 *
 * Details of the content type and target filefield name
 * should be in the settings.
 *
 * @var object $file
 *   A file object
 * @var stdClass $template_node
 *   Entity with some settings on to use as a template when creating
 *   a fileholder. Passing this in allows you to define some useful values.
 * @var array $settings
 *   Context and configs.
 *
 * @return object
 *   The fileholder node
 */
function file_ownage_make_fileholder_node($file, $template_node, $settings) {

  // Assert valid input;.
  if (empty($settings['fileholderholder_type'])
        || empty($settings['filefield_name'])
    ) {
    \Drupal::logger('file_ownage')->error('null settings. This function requires a settings array that defines a fileholder_type and a filefield_name. They were not passed in.', []);
    return NULL;
  }
  $imageholder_type = $settings['fileholder_type'];

  if (empty($file['fid']) || !file_exists($file['filepath'])) {
    \Drupal::logger('file_ownage')->error('Invalid file entry. fid or filepath did not represent a real file. Not making a fileholder node.', []);
    return NULL;
  }

  // We have a file object.  Make an image node from it and return
  // Non-standard additional strings title, caption, description, alt
  // may be available on the file object.
  //
  // Values from the template node MAY be copied down,
  // node->uid and maybe node->taxonomy.
  $title = !empty($file['title']) ? $file['title'] : $file['filename'];
  $caption = isset($file['caption']) ? $file['caption'] : '';

  if (empty($title)) {
    trigger_error('Cant save an image node without a title');
    return NULL;
  }
  \Drupal::logger('file_ownage')->debug("Creating an fileholder node to manage the file %file", ['%file' => $file['filepath']]);

  $node = new stdClass();
  $node->type = $imageholder_type;
  $node->status = 1;
  // $node->uid = $uid;.
  $node->title = $title;
  $node->body = $caption;
  $node->created = REQUEST_TIME;
  $node->changed = $node->created;
  $node->promote = 1;
  $node->sticky = 0;
  $node->format = 2;
  $node->pathauto_perform_alias = FALSE;

  if ($template_node) {
    // Copy any safe values into it.
    foreach ($template_node as $var => $val) {
      $node->$var = $val;
    }
  }
  else {
    $user = \Drupal::currentUser();
    $node->uid = $user->uid;
  }

  // $node->$filefield_name = array(0 => field_file_load($file['fid']));
  // Call our own subroutine to attach the actual filefield,
  // it's got more error-checking.
  file_ownage_attach_filefield($node, $file, $settings);

  $node->save();
  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $strings = array(
  //     '!node' => l($node->title, 'node/' . $node->nid),
  //     '%file' => $file['filepath'],
  //   );
  \Drupal::logger('file_ownage')->debug("New fileholder node !node created to manage the file %file", []);
  return $node;
}

/**
 * Returns a preset.
 *
 * @param array $img
 *   Array containing image.
 *
 * @return string
 *   Preset identifier.
 */
function file_ownage_select_preset(array $img) {
  // At this point we only deal with one preset value.
  $preset = reset($img['meta']['preset']);
  \Drupal::logger('file_ownage')->debug('Selected a preset to use for this image, Returning %preset', ['%preset' => $preset]);
  return $preset;
}
