<?php

class DrealtyImport {
  public function __construct() {
    $this->dc = new drealtyConnection();
    $this->dm = new drealtyMetaData();
    $this->queue = DrupalQueue::get('drealty_import');
  }

  /**
   * Clears the queue out.
   */
  public function clearQueue() {
    $this->queue->deleteQueue();
  }

  /**
   * Retrieves the current state of the queue.
   * @return mixed
   */
  public static function getQueue() {
    return DrupalQueue::get('drealty_import');
  }

  /**
   * Copy of ProcessRetsClass from the drush daemon. We want to get a list of entities to queue, but not process them at the same time.
   * @param drealtyConnectionEntity $connection
   * @param drealtyRetsResource $resource
   * @param drealtyRetsClass $class
   * @param string $entity_type
   * @return array
   */
  public function ProcessRetsClass(drealtyConnectionEntity $connection, $resource, $class, $entity_type) {
    $mappings = $connection->ResourceMappings();
    $resources = $connection->Resources();
    $key_field = "";
    $chunks = 0;
    $items = array();

    // build a list of fields we are going to request from the RETS server
    $fieldmappings = $connection->FetchFieldMappings($resource, $class);
    $key_field = $fieldmappings['rets_key']->systemname;

    switch ($class->query_type) {
      case 1:
        $this->fetch_listings_offset_not_supported_price($connection, $resource, $class, $key_field);
        $process = TRUE;
        break;
      case 2:
        $query = array();
        $custom_query = token_replace($class->override_status_query_text, array('drealty-class' => $class));
        $query[] = $custom_query;
        $items = $this->fetch_listings_offset_supported_default($connection, $resource, $class, $query);
        break;
      case 3:
        $items = $this->fetch_listings_offset_not_supported_key($connection, $resource, $class, $key_field);
        $process = TRUE;
        break;
      case 0:
      default:
        //build the query
        $statuses = $class->status_values;
        $status_q = "|$statuses";
        $query_field = 'rets_status';
        $query = array();
        $query[] = "{$fieldmappings[$query_field]->systemname}={$status_q}";
        $items = $this->fetch_listings_offset_supported_default($connection, $resource, $class, $query);
    }

    return $items;
  }

  /**
   *
   * @param drealtyConnectionEntity $connection
   * @param drealtyRetsResource $resource
   * @param drealtyRetsClass $class
   * @param string $key_field
   * @return int
   */
  function fetch_listings_offset_not_supported_key(drealtyConnectionEntity $connection, $resource, $class, $key_field) {
    $rets = $this->dc->rets;

    $chunks = 0;
    $id = 0;

    $query = "({$key_field}={$id}+)";
    $limit = $class->chunk_size;

    $options = array(
      'count' => 1,
      'Format' => 'COMPACT-DECODED',
      'Select' => $key_field,
    );

    if ($this->dc->connect($connection->conid)) {
      $search = $rets->SearchQuery($resource->systemname, $class->systemname, $query, $options);

      $total = $rets->TotalRecordsFound($search);
      $rets->FreeResult($search);

      $count = 0;
      $listings = array();

      $options['Select'] = $this->get_fields($connection->conid, $class->cid);

      if ($class->process_images) {
        $options['Select'] .= ',' . $class->photo_timestamp_field;
      }

      $options['Limit'] = $limit;

      switch ($class->systemname) {
        case 'Property':
          $entity_type = 'drealty_listing';
          break;
        case 'Agent':
          $entity_type = 'drealty_agent';
          break;
        case 'Office':
          $entity_type = 'drealty_office';
          break;
        case 'OpenHouse':
          $entity_type = 'drealty_openhouse';
          break;
        default:
          $entity_type = 'drealty_listing';
          break;
      }

      while ($count <= $total) {

        $search = $rets->SearchQuery($resource->systemname, $class->systemname, $query, $options);

        if ($rets->NumRows($search) > 0) {
          while ($item = $rets->FetchRow($search)) {
            $items[] = array('resource' => $resource, 'entity_type' => $entity_type, 'connection' => $connection, 'class' => $class, 'result' => $item, 'remote_id' => $item[$resource->keyfield]);
            $count++;
          }

          ksort($listings);
          $last = end($listings);
          reset($listings);

          $id = $last[$key_field];
          $id = (int) $id + 1;

          unset($listings);
        } else {
          break;
        }

        $rets->FreeResult($search);
        $query = "({$key_field}={$id}+)";
      }
      $this->dc->disconnect();
      return $items;
    } else {
      $error = $rets->Error();
      watchdog('drealty', "drealty encountered an error: (Type: @type Code: @code Msg: @text)", array("@type" => $error['type'], "@code" => $error['code'], "@text" => $error['text']), WATCHDOG_ERROR);
    }
  }

  /**
   *
   * @param drealtyConnectionEntity $connection
   * @param drealtyRetsResource $resource
   * @param drealtyRetsClass $class
   * @param string $key_field
   * @return int
   */
  function fetch_listings_offset_not_supported_price(drealtyConnectionEntity $connection, $resource, $class, $key_field) {
    $rets = $this->dc->rets;

    $chunks = 0;
    $offset_amount = $class->offset_amount;
    $offset_max = $class->offset_max;
    $offset_start = 0;
    $offset_end = $offset_start + $offset_amount;

    $query = token_replace($class->override_status_query_text, array('drealty-class' => $class));
    $options = array(
      'count' => 1,
      'Format' => 'COMPACT-DECODED',
      'Select' => $key_field,
    );


    if ($this->dc->connect($connection->conid)) {


      $search = $rets->SearchQuery($resource->systemname, $class->systemname, $query, $options);

      if ($error = $rets->Error()) {
        #drush_log(dt("drealty encountered an error: (Type: @type Code: @code Msg: @text)", array("@type" => $error['type'], "@code" => $error['code'], "@text" => $error['text']), 'error'));
      }

      $total = $rets->TotalRecordsFound($search);
      $rets->FreeResult($search);

      $offset_query = "$query,({$class->offset_field}={$offset_start}-{$offset_end})";
      $count = 0;

      $options['Select'] = $this->get_fields($connection->conid, $class->cid);

      if ($class->process_images) {
        $options['Select'] .= ',' . $class->photo_timestamp_field;
      }

      while ($count < $total) {

        $search = $rets->SearchQuery($resource->systemname, $class->systemname, $offset_query, $options);

        if ($rets->NumRows($search) > 0) {
          while ($listing = $rets->FetchRow($search)) {
            $listing['hash'] = $this->calculate_hash($listing, $connection, $class);
            $this->queue->createItem($listing);
            $count++;
          }
        }

        $rets->FreeResult($search);
        #drush_log("Resource: {$resource->systemname} Class: {$class->systemname} Listings Downloaded: $count Query: $offset_query");

        if ($offset_end < $offset_max) {
          $offset_start = $offset_end + 1;
          $offset_end += $offset_amount;
          $offset_query = "$query,({$class->offset_field}={$offset_start}-{$offset_end})";
        } else {
          $offset_query = "$query,({$class->offset_field}={$offset_max}+)";
        }
      }
      $this->dc->disconnect();
    } else {
      $error = $rets->Error();
      watchdog('drealty', "drealty encountered an error: (Type: @type Code: @code Msg: @text)", array("@type" => $error['type'], "@code" => $error['code'], "@text" => $error['text']), WATCHDOG_ERROR);
      #drush_log(dt("drealty encountered an error: (Type: @type Code: @code Msg: @text)", array("@type" => $error['type'], "@code" => $error['code'], "@text" => $error['text']), 'error'));
    }
    unset($listings);
  }

  /**
   * Performs a RETS query and adds results to the Drupal Queue.
   * @param drealtyConnectionEntity $connection
   * @param drealtyRetsResource $resource
   * @param drealtyRetsClass $class
   * @param string $query
   * @return int
   */
  function fetch_listings_offset_supported_default(drealtyConnectionEntity $connection, $resource, $class, $query) {
    $offset = 0;
    $count = 0;
    $rets = $this->dc->rets;
    $items = array();
    $limit = $class->chunk_size;

    if ($limit == 0) {
      $limit = 'NONE';
    }

    $this->dc->rets->SetParam("offset_support", TRUE);

    if ($this->dc->connect($connection->conid)) {
      // prepare the query
      $q = implode('),(', $query);
      $fields = $this->get_fields($connection->conid, $class->cid);

      if ($class->process_images) {
        $fields .= ',' . $class->photo_timestamp_field;
      }

      $optional_params = array(
        'Format' => 'COMPACT-DECODED',
        'Limit' => "$limit",
        'Select' => $fields,
        'RestrictedIndicator' => 'xxxx',
      );

      switch ($class->systemname) {
        case 'Property':
          $entity_type = 'drealty_listing';
          break;
        case 'Agent':
          $entity_type = 'drealty_agent';
          break;
        case 'Office':
          $entity_type = 'drealty_office';
          break;
        default:
          $entity_type = 'drealty_listing';
          break;
      }

      // do the actual search
      $search = $rets->SearchQuery($resource->systemname, $class->systemname, "($q)", $optional_params);

      // loop through the search results
      if ($rets->NumRows() > 0) {
        while ($item = $rets->FetchRow($search)) {
          $items[] = array('resource' => $resource, 'entity_type' => $entity_type, 'connection' => $connection, 'class' => $class, 'result' => $item, 'remote_id' => $item[$resource->keyfield]);
        }
      }

      $rets->FreeResult($search);
      $this->dc->disconnect();
      return $items;
    }
  }

  /**
   * Process an item from the queue.
   * @param $item
   */
  public function processQueueItem($item) {
    try {
      $connection = $item['connection'];
      $class = $item['class'];
      $resource = $item['resource'];
      $entity_type = $item['entity_type'];

      // check if the entity exists
      $listing = $this->entityExists($entity_type, $item['result'][$resource->keyfield]);
      if (!($listing)) {
        $entity = $this->create($connection, $class, $item['remote_id'], $item['result'][$resource->keyfield], $entity_type);
        entity_save($entity_type, $entity);
      }
      else {
        if (is_array($listing)) {
          $id = array_keys($listing);
          $id = end($id);
          $entity = entity_load($entity_type, array($id));
          $entity = reset($entity);
        }
      }

      $this->update($entity);
    }
    catch (Exception $e) {
      watchdog("drealty_process_queue", $e->getMessage(), array(), WATCHDOG_ERROR);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Creates an entity ready to be saved.
   * @param $connection
   * @param $class
   * @param $rets_id
   * @param $rets_key
   * @return bool
   */
  public function create($connection, $class, $rets_id, $rets_key, $entity_type) {
    $entity = entity_create($entity_type, array('type' => $class->bundle));
    $entity->active = 1;
    $entity->conid = $connection->conid;
    $entity->class = $class->cid;
    $entity->label = $rets_id;
    $entity->rets_id = $rets_id;
    $entity->rets_key = $rets_key;
    $entity->created = REQUEST_TIME;
    $entity->changed = REQUEST_TIME;
    $entity->uid = 1;
    return $entity;
  }

  /**
   * Check if a drealty entity exists by its rets key value.
   * @param $rets_key
   * @return bool
   */
  public function entityExists($entity_type, $rets_key) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type);
    $query->propertyCondition('rets_key', $rets_key);
    $result = $query->execute();
    return $result ? $result[$entity_type] : FALSE;
  }

  /**
   * Updates a Drealty entity.
   * @param $listing
   * @return bool
   * @throws drealty_rets_disconnect_exception
   */
  public function update($listing) {
    $connection = $this->dc->FetchConnection($listing->conid);
    $class = $this->dm->FetchClass($listing->class);
    $resource = $this->dm->FetchResource($class->rid);

    $field_mappings = $connection->FetchFieldMappings($resource, $class);
    $key_field = $field_mappings['rets_key']->systemname;

    $item_context = array('field_mappings' => $field_mappings, 'connection' => $connection, 'resource' => $resource, 'key_field' => $key_field);

    $query = "({$key_field}={$listing->rets_key})";

    $fields = $this->get_fields($connection->conid, $class->cid);

    if ($class->process_images) {
      $fields .= ',' . $class->photo_timestamp_field;
    }

    $params = array(
      'Format' => 'COMPACT-DECODED',
      'Limit' => "1",
      'RestrictedIndicator' => 'xxxx',
      'Count' => '0',
      'Select' => $fields,
    );

    if ($this->dc->connect($connection->conid)) {
      $rets = $this->dc->rets;
      $search = $rets->SearchQuery($resource->systemname, $class->systemname, $query, $params);

      if(!$search) {
        watchdog("drealty_import", 'Search for UniqueID # @key listing (MLS # @mls) was unsuccessful.', array('@key' => $listing->rets_key, '@mls' => $listing->rets_id), WATCHDOG_NOTICE);
        //        drupal_set_message("Search for this listing was unsuccessful. Either it doesn't exist or there was an error contacting the server.", 'error');
        return FALSE;
      }

      $item = $rets->FetchRow($search);
      $rets->FreeResult($search);
      try {
        if (!$item && ($listing->active == 1 || empty($listing->inactive_date))) {
            $listing->active = 0;
            $listing->inactive_date = REQUEST_TIME;
            entity_save('drealty_listing', $listing);
        }
        else if ($item && is_array($item)) { // since we found the RETS listing, set DB listing active status to 1 in case it was disabled in the past:
          $listing->active = 1;
          $listing->inactive_date = null;
          $listing->hash = $this->calculate_hash($item, $connection, $class);
          $listing->changed = REQUEST_TIME;

          $this->set_field_data($listing, $item, $field_mappings, $listing->entityType(), $class, TRUE);
          $item_context['rets_item'] = $item;


          if ($class->process_images) {
            //get the images
            $results = $rets->GetObject($resource->systemname, $class->object_type, $listing->rets_key, '*');
            $img_dir = file_default_scheme() . "://{$class->image_dir}";
            $img_field = $class->image_field_name;

            if (!file_prepare_directory($img_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
              #drush_log(dt("Failed to create %directory.", array('%directory' => $img_dir)), "error");
              watchdog("drealty_import", "Failed to create %directory.", array('%directory' => $img_dir), WATCHDOG_WARNING);
              return;
            } else if (!is_dir($img_dir)) {
                #drush_log(dt("Failed to locate %directory.", array('%directory' => $img_dir)), "error");
                watchdog("drealty_import", "Failed to locate %directory.", array('%directory' => $img_dir), WATCHDOG_WARNING);
                return;
            }

            //sort the images
            if ($results) {
              $photos = array();
              foreach ($results as $item) {
                $photos[$item['Object-ID']] = $item;
              }
              ksort($photos, SORT_NUMERIC);

              // delete out any existing images
              if (isset($listing->{$img_field}[LANGUAGE_NONE])) {
                foreach ($listing->{$img_field}[LANGUAGE_NONE] as $key => $file) {
                  $image = file_load($file['fid']);
                  unset($listing->{$img_field}[LANGUAGE_NONE][$key]);
                  if (!empty($image)) {
                    file_delete($image);
                  }
                }
              }

              unset($listing->{$img_field}[LANGUAGE_NONE]);

              foreach ($photos as $photo) {
                $mlskey = $photo['Content-ID'];
                $number = $photo['Object-ID'];
                $filename = $mlskey . '-' . REQUEST_TIME . '-' . $number . '.jpg';
                $filepath = "{$img_dir}/{$filename}";
                //ensure that there is enough data to actually make a file.
                if (strlen($photo['Data']) > 173) {
                  if ($file = file_save_data($photo['Data'], $filepath, FILE_EXISTS_REPLACE)) {
                    //make sure we actually save the image
                          $file->alt = 'MLS #' . $listing->rets_id . ' property image #' . $number;
                          $file->title = 'MLS #' . $listing->rets_id . ' property image #' . $number;
                    $listing->{$img_field}[LANGUAGE_NONE][] = (array) $file;
                  }
                }
              }
            }
          }

          $listing->rets_photo_modification_timestamp = $item_context['rets_item'][$class->photo_timestamp_field];

          drupal_alter('drealty_import_presave', $listing, $item_context);
          $listing->save();
          module_invoke_all('drealty_entity_save', array(&$listing, $item_context));
        }
      } catch (Exception $e) {
        watchdog("drealty", $e->getMessage(), array(), WATCHDOG_ERROR);
        drupal_set_message($e->getMessage(), 'error');
        $this->dc->disconnect();
        return FALSE;
      }
      $this->dc->disconnect();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @todo: is this method necessary for a cron processor?
   * Function to handle the logic of what to do with expired listings
   *
   * @param array $in_rets
   * @param array $conid
   * @param drealtyRetsClass $class
   */
  protected function handle_expired($in_rets, $conid, $class) {
    $results = db_select('drealty_listing', 'dl')
      ->fields('dl', array('id', 'rets_key'))
      ->condition('conid', $conid)
      ->condition('class', $class->cid)
      ->condition('active', 1)
      ->execute()
      ->fetchAllAssoc('rets_key');

    $diff = array_diff_key($results, $in_rets);

    foreach ($diff as $item) {
      $wrapper = entity_metadata_wrapper('drealty_listing', $item->id);
      $wrapper->active = 0;
      $wrapper->inactive_date = REQUEST_TIME;
      $property = $wrapper->value();
      entity_save('drealty_listing', $property);
    }
  }

  /**
   * Calculate an md5 hash on the resulting listing used to determine if we need
   * to perform an update
   *
   * @param array $items
   * @param $connection
   * @param $class
   * @return string
   */
  public function calculate_hash(array $items, $connection, $class) {
    $cache = &drupal_static(__FUNCTION__);

    if (empty($cache[$connection->conid]) || empty($cache[$connection->conid][$class->cid])) {
      $field_mappings = db_select('drealty_field_mappings', 'dfm')
        ->fields('dfm')
        ->condition('conid', $connection->conid)
        ->condition('cid', $class->cid)
        ->condition('hash_exclude', FALSE)
        ->execute()
        ->fetchAll();

      $cache[$connection->conid][$class->cid] = $field_mappings;
    }

    $fields = $cache[$connection->conid][$class->cid];

    $tmp = '';
    foreach ($fields as $field) {
      switch ($field->field_api_type) {
        case 'addressfield':
        case 'location':
          $data = unserialize($field->data);
          foreach ($data as $item) {
            $tmp .= drupal_strtolower(trim($items[$item]));
          }
          break;
        case 'geofield':
          // in the case of a geofield we don't have anything to map to, so we'll skip it.
          break;
        default:
          $tmp .= drupal_strtolower(trim($items[$field->systemname]));
      }
    }

    // Add photo timestamp field so that if photos are added, we update the entry.
    if ($class->process_images && $class->photo_timestamp_field && isset($items[$class->photo_timestamp_field])) {
      $tmp .= $items[$class->photo_timestamp_field];
    }

    return md5($tmp);
  }

  /**
   * @todo: is this method necessary for a cron processor?
   * @global type $user
   * @param int $conid
   * @param drealtyRetsResource $resource
   * @param drealtyRetsClass $class
   * @return type
   */
  public function process_images($conid, $resource, $class) {
    if (!$class->process_images) {
      return;
    }

    $img_field = $class->image_field_name;
    $dir = $class->image_dir;

    $rets = $this->dc->rets;
    $entity_type = 'drealty_listing';
    $chunk_size = $class->image_chunk_size;

    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', $entity_type)
      ->entityCondition('bundle', $class->bundle)
      ->propertyCondition('process_images', 1)
      ->execute();

    if (!empty($result[$entity_type])) {
      $items = array_keys($result[$entity_type]); //entity_load($entity_type, array_keys($result[$entity_type]));
    } else {
      #drush_log("No images to process.");
      return;
    }

    //make sure we have something to process
    if (count($items) >= 1) {
      #drush_log("process_images() - Starting.");
      $img_dir = file_default_scheme() . "://$dir";

      if (!file_prepare_directory($img_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
        #drush_log(dt("Failed to create %directory.", array('%directory' => $img_dir)), "error");
        watchdog("drealty_import", "Failed to create %directory.", array('%directory' => $img_dir), WATCHDOG_WARNING);
        return;
      } else {
        if (!is_dir($img_dir)) {
          #drush_log(dt("Failed to locate %directory.", array('%directory' => $img_dir)), "error");
          watchdog("drealty_import", "Failed to locate %directory.", array('%directory' => $img_dir), WATCHDOG_WARNING);
          return;
        }
      }


      $process_array = array_chunk($items, $chunk_size, TRUE);

      foreach ($process_array as $chunk) {

        $listings = entity_load($entity_type, $chunk);

        $ids = array();
        $rekeyed_listings = array();
        foreach ($listings as $key => $listing) {
          $ids[] = $listing->rets_key;
          $listings[$listing->rets_key] = $listing;
          unset($listings[$key]);
        }


        if ($this->dc->connect($conid)) {
          $id_string = implode(',', $ids);
          #drush_log("id string: " . $id_string);

          $results = $rets->GetObject($resource->systemname, $class->object_type, $id_string, '*');

          if ($rets->Error()) {
            $error = $rets->Error();
            #drush_log(dt("drealty encountered an error: (Type: @type Code: @code Msg: @text)", array("@type" => $error['type'], "@code" => $error['code'], "@text" => $error['text'])), 'error');
            return;
          }

          $photos = array();

          if (!empty($results)) {
            $length = 0;
            $total = 0;
            $photos = array();

            foreach ($results as $item) {
              if ($item['Success'] == TRUE) {
                if (strlen($item['Data']) > 173) {
                  $total++;
                  $length += strlen($item['Data']);

                  if (!isset($photos[$item['Content-ID']])) {
                    $photos[$item['Content-ID']] = array();
                  }
                  $photos[$item['Content-ID']][$item['Object-ID']] = $item;
                } else {
                  #drush_log(dt("Images failed to download: @code - @text", array('@code' => $item['ReplyCode'], '@text' => $item['ReplyText'])), 'warning');
                }
              }
            }

            ksort($photos, SORT_NUMERIC);
            foreach ($photos as $key => $set) {
              ksort($photos[$key], SORT_NUMERIC);
            }
            #drush_log(dt("Downloaded a total of @total images for @count Listings.", array("@total" => $total, "@count" => count($ids))));
          } else {
            #drush_log(dt("GetObject for @resource - @class returned an empty result set.", array('@resource' => $resource->systemname, '@class' => $class->systemname)), 'warning');
          }

          $this->dc->disconnect();

          unset($ids, $results);

          $id_string = "";
          $counter = 0;

          foreach ($photos as $list_id => $set) {

            $listing = $listings[$list_id];

            // delete out any existing images
            if (isset($listing->{$img_field}[LANGUAGE_NONE])) {
              foreach ($listing->{$img_field}[LANGUAGE_NONE] as $key => $file) {
                $image = file_load($file['fid']);
                unset($listing->{$img_field}[LANGUAGE_NONE][$key]);
                if (!empty($image)) {
                  file_delete($image);
                }
              }
            }

            unset($listing->{$img_field}[LANGUAGE_NONE]);

            foreach ($set as $key => $photo) {
              $mlskey = $photo['Content-ID'];
              $number = $photo['Object-ID'];
              $filename = $mlskey . '-' . REQUEST_TIME . '-' . $number . '.jpg';
              $filepath = "{$img_dir}/{$filename}";
              //ensure that there is enough data to actually make a file.
              if (strlen($photo['Data']) > 173) {
                if ($file = file_save_data($photo['Data'], $filepath, FILE_EXISTS_REPLACE)) {
                  //make sure we actually save the image
                  $file->alt = 'MLS #' . $listing->rets_id . ' property image #' . $number;
                  $file->title = 'MLS #' . $listing->rets_id . ' property image #' . $number;
                  $listing->{$img_field}[LANGUAGE_NONE][] = (array) $file;
                }
              }
            }

            $listing->process_images = 0;
            $listing->save();

            #drush_log(dt("Saved @count images for @listing", array("@count" => count($set), "@listing" => $list_id)), "success");
            unset($photos[$list_id], $listings[$list_id]);
            drupal_get_messages();
            drupal_static_reset();
          }
          // apparently some mls feeds have listings with 0 images, set them as processed
          foreach ($listings as $listingid => $listing) {
            $listing->process_images = 0;
            $listing->save();
            #drush_log(dt("Listing @id had no images. Marking as processed.", array("@id" => $listingid)), 'warning');
            drupal_get_messages();
            drupal_static_reset();
          }
          unset($photos, $listings);
        }
      }
    }
    cache_clear_all("prop_images_to_process", "cache");
  }

  /**
   * Retrieves all configured fields for a Resource class in a connection.
   * @param $conid
   * @param $class_id
   * @return string
   */
  public function get_fields($conid, $class_id) {
    $results = db_select('drealty_field_mappings', 'dfm')
      ->fields('dfm')
      ->condition('conid', $conid)
      ->condition('cid', $class_id)
      ->execute();


    $fields = array();
    foreach ($results as $result) {
      switch ($result->field_api_type) {
        case 'addressfield':
        case 'location':
          $data = unserialize($result->data);
          foreach ($data as $item) {
            $fields[] = $item;
          }
          break;
        case 'geofield':
          // in the case of a geofield we don't have anything to map to, so we'll skip it.
          break;
        default:
          $fields[] = $result->systemname;
      }
    }
    return implode(',', $fields);
  }

  /**
   * Maps RETS fields to Drupal fields based on the field mapping configuration.
   * @param $item
   * @param $rets_item
   * @param $field_mappings
   * @param $entity_type
   * @param $class
   */
  public function set_field_data(&$item, $rets_item, $field_mappings, $entity_type, $class) {

    foreach ($field_mappings as $mapping) {
      switch ($mapping->field_api_type) {
        case 'addressfield':
          //get the default country code if one exists for the address
          $field_info = field_info_instance($entity_type, $mapping->field_name, $class->bundle);
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['country'] = isset($field_info['default_value'][0]['country']) ? $field_info['default_value'][0]['country'] : 'US';
          if(isset($mapping->data['address_1']) && isset($rets_item[$mapping->data['address_1']])) {
            $item->{$mapping->field_name}[LANGUAGE_NONE][0]['thoroughfare'] = $rets_item[$mapping->data['address_1']];
            if(isset($mapping->data['address_1_1']) && isset($rets_item[$mapping->data['address_1_1']])) {
              $item->{$mapping->field_name}[LANGUAGE_NONE][0]['thoroughfare'] .= " " . $rets_item[$mapping->data['address_1_1']];
            }
            if(isset($mapping->data['address_1_2']) && isset($rets_item[$mapping->data['address_1_2']])) {
              $item->{$mapping->field_name}[LANGUAGE_NONE][0]['thoroughfare'] .= " " . $rets_item[$mapping->data['address_1_2']];
            }
            if(isset($mapping->data['address_1_3']) && isset($rets_item[$mapping->data['address_1_3']])) {
              $item->{$mapping->field_name}[LANGUAGE_NONE][0]['thoroughfare'] .= " " . $rets_item[$mapping->data['address_1_3']];
            }
          } else {
            $item->{$mapping->field_name}[LANGUAGE_NONE][0]['thoroughfare'] = NULL;
          }
          if(isset($mapping->data['address_2']) && isset($rets_item[$mapping->data['address_2']])) {
            $item->{$mapping->field_name}[LANGUAGE_NONE][0]['premise'] = $rets_item[$mapping->data['address_2']];
            if(isset($mapping->data['address_2_1']) && isset($rets_item[$mapping->data['address_2_1']])) {
              $item->{$mapping->field_name}[LANGUAGE_NONE][0]['premise'] .= " " . $rets_item[$mapping->data['address_2_1']];
            }
            if(isset($mapping->data['address_2_2']) && isset($rets_item[$mapping->data['address_2_2']])) {
              $item->{$mapping->field_name}[LANGUAGE_NONE][0]['premise'] .= " " . $rets_item[$mapping->data['address_2_2']];
            }
            if(isset($mapping->data['address_2_3']) && isset($rets_item[$mapping->data['address_2_3']])) {
              $item->{$mapping->field_name}[LANGUAGE_NONE][0]['premise'] .= " " . $rets_item[$mapping->data['address_2_3']];
            }
          } else {
            $item->{$mapping->field_name}[LANGUAGE_NONE][0]['premise'] = NULL;
          }
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['premise'] = isset($mapping->data['address_2']) ? $rets_item[$mapping->data['address_2']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['locality'] = isset($mapping->data['city']) ? $rets_item[$mapping->data['city']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['administrative_area'] = isset($mapping->data['state']) ? $rets_item[$mapping->data['state']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['sub_administrative_area'] = isset($mapping->data['county']) ? $rets_item[$mapping->data['county']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['postal_code'] = isset($mapping->data['zip']) ? $rets_item[$mapping->data['zip']] : NULL;
          break;
        case 'location':
          //get the default country code if one exists for the address
          $field_info = field_info_instance($entity_type, $mapping->field_name, $class->bundle);
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['country'] = isset($field_info['default_value'][0]['country']) ? $field_info['default_value'][0]['country'] : 'CA';
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['street'] = isset($rets_item[$mapping->data['street']]) ? $rets_item[$mapping->data['street']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['additional'] = isset($mapping->data['additional']) ? $rets_item[$mapping->data['additional']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['city'] = isset($mapping->data['city']) ? $rets_item[$mapping->data['city']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['stateorprovince'] = isset($mapping->data['stateorprovince']) ? $rets_item[$mapping->data['stateorprovince']] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['postal_code'] = isset($mapping->data['postal_code']) ? $rets_item[$mapping->data['postal_code']] : NULL;
          break;
        case 'geofield':
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['wkt'] = GEOCODER_DUMMY_WKT;
          break;
        case 'text_long':
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['value'] = isset($rets_item[$mapping->systemname]) ? $rets_item[$mapping->systemname] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['format'] = 'plain_text';
          break;
        case 'number_integer':
        case 'number_decimal':
        case 'number_float':
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['value'] = empty($rets_item[$mapping->systemname]) ? 0 : is_numeric($rets_item[$mapping->systemname]) ? $rets_item[$mapping->systemname] : NULL;
          break;
        case 'list_boolean':
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['value'] = in_array($rets_item[$mapping->systemname], array('true', 'True', 'TRUE', 'yes', 'Yes', 'y', 'Y', '1', 'on', 'On', 'ON', true, 1), true) ? 1 : 0;
          break;
        case 'taxonomy_term_reference':
          $item->{$mapping->field_name}[LANGUAGE_NONE] = array();
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['value'] = isset($rets_item[$mapping->systemname]) ? $rets_item[$mapping->systemname] : NULL;
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['tid'] = '';
          break;
        case 'drealty':
          $item->{$mapping->field_name} = isset($rets_item[$mapping->systemname]) ? $rets_item[$mapping->systemname] : NULL;
          break;
        case 'datetime':
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['value'] = (isset($rets_item[$mapping->systemname]) && !empty($rets_item[$mapping->systemname])) ? $rets_item[$mapping->systemname] : '1960-01-01T00:00:00.000';
          break;
        default:
          $item->{$mapping->field_name}[LANGUAGE_NONE][0]['value'] = isset($rets_item[$mapping->systemname]) ? $rets_item[$mapping->systemname] : NULL;
      }
    }
  }
}