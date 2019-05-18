(function ($, Drupal, drupalSettings) {

  'use strict';

  // Set some basic variables.
  const selectClassName = 'ls__select-item';
  const selectClass = '.' + selectClassName;
  const selectWrapperClassName = 'ls__select-wrapper';
  const selectWrapperClass = '.' + selectWrapperClassName;
  const selectLoaderClassName = 'ls__select-loader';
  const selectLoaderClass = '.' + selectLoaderClassName;
  let $lastChangedWrapper = {};
  // Get drupal settings.
  const widgetSettings = drupalSettings.location_selector.form_element_settings;
  const wsParentID = widgetSettings.basic_parent_id;
  const wsSaveLast = widgetSettings.save_last;
  const wsParentInclude = widgetSettings.parent_include;
  const wsLimitLevel = widgetSettings.limit_level;
  const formType = drupalSettings.location_selector.form_type;
  const formWrapperClass = '.' + drupalSettings.location_selector.form_wrapper_class;
  const formWrapperClasses = formWrapperClass + '.' + formType;
  let widgetDefaultValues = drupalSettings.location_selector.form_element_default_values;
  const formElementIds = drupalSettings.location_selector.form_element_ids;
  let defaultValuesIDs = {};

  // Get all IDs in one single array object.
  getDefaultIDs();

  /**
   * Get the Json values.
   */
  function getDefaultIDs() {
    if (widgetDefaultValues) {
      let allDefaultValues = JSON.parse(widgetDefaultValues);
      if (!$.isEmptyObject(allDefaultValues)) {
        $.each(allDefaultValues.path, function () {
          defaultValuesIDs[this.val] = this.text;
        });
      }
    }
    else {
      defaultValuesIDs = {};
    }
  }

  /**
   * Create the HTML Select Lists.
   *
   * @param {Object} arr
   *   The specific id object.
   * @param {number} i
   *   The level number.
   */
  function createSelectList(arr, i) {
    let selectCounter = $(selectClass).length;
    let counter = 1;
    let defaultID = null;
    let selectNewCount = selectCounter + counter;
    let $select = $('<select>').addClass(selectClassName + ' ' + selectClassName + '-' + selectNewCount);
    $select.attr('data-location-selector-count', selectNewCount);
    // $select.appendTo(formWrapperClasses);
    $(formWrapperClasses).append($select);
    $(arr).each(function () {
      if (this.val in defaultValuesIDs) {
        defaultID = this.val;
      }
      $select.append($('<option>').attr('value', this.val).text(this.text));
    });
    if (defaultID) {
      $select.val(defaultID);
    }
    $(selectClass + '-' + selectNewCount).wrapAll('<div class="' + selectWrapperClassName + ' ' + selectWrapperClassName + '-' + selectNewCount + ' ' + 'ls-level-' + i + '"></div>');

  }

  /**
   * Set the value to the textarea.
   *
   * @param {Object} $thisWrapper
   *   The jQuery wrapper object.
   * @param {number} selectedID
   *   The selected ID.
   */
  function setValue($thisWrapper, selectedID) {
    // For normal entity-edit-forms.
    let allValues = {};
    let allValuesJson = '';
    let textSelector = 'textarea';
    $(selectClass).each(function (i) {
      let selected = $(this).children('option:selected');
      if (selected.val() !== 'All') {
        if (i == 0) {
          allValues['path'] = {};
          allValues['selected'] = {};
        }
        allValues['path'][i] = {
          val: selected.val(),
          text: selected.text()
        };
        if (!wsSaveLast) {
          allValues['selected'][selected.val()] = selected.text();
        }
        else {
          allValues['selected'] = {};
          allValues['selected'][selected.val()] = selected.text();
        }
      }
    });
    if (!$.isEmptyObject(allValues)) {
      let allValuesJsonString = JSON.stringify(allValues);
      if (allValuesJsonString.length > 8) {
        allValuesJson = allValuesJsonString;
        // Create validate object.
        // @see
        // \Drupal\location_selector\LocationSelectorController::validateGeoNames
        let validateObject = {
          ids: formElementIds,
          values: allValuesJson
        };
        // Set values in PHP for validating in future.
        setSessionWithAjax(validateObject);
      }
    }
    $thisWrapper.parent(formWrapperClasses).find(textSelector).val(allValuesJson);
  }

  /**
   * Shows a network error modal dialog.
   *
   * @param {string} title
   *   The title to use in the modal dialog.
   * @param {string} message
   *   The message to use in the modal dialog.
   */
  function networkErrorModal(title, message) {
    const $message = $('<div>' + message + '</div>');
    const networkErrorModal = Drupal.dialog($message.get(0), {
      title,
      dialogClass: 'location-selector-network-error',
      buttons: [
        {
          text: Drupal.t('OK'),
          click() {
            networkErrorModal.close();
          },
          primary: true
        }
      ],
      create() {
        $(this)
          .parent()
          .find('.ui-dialog-titlebar-close')
          .remove();
      },
      close(event) {
        // Automatically destroy the DOM element that was used for the dialog.
        $(event.target).remove();
      }
    });
    networkErrorModal.showModal();
  }

  /**
   * Loads a thread from the server.
   *
   * @param {Object} selectedIDs
   *   The selected ID object.
   */
  function getMetadataWithAjax(selectedIDs) {

    // Add gif loader.
    $(formWrapperClasses).append('<span class="' + selectLoaderClassName + '">&nbsp;&nbsp;</span>');

    $.ajax({
      url: Drupal.url('location_selector/geonames'),
      type: 'POST',
      data: {
        selected: selectedIDs
      },
      dataType: 'json',
      timeout: 5000,
      success: function (response) {
        // Remove gif loader.
        $(selectLoaderClass).remove();
        if (!$.isEmptyObject(response[0]) && response[0] !== null) {
          ajaxSuccessCall(response);
        }
      },
      error: function (xhr, textStatus, errorThrown) {
        // Remove gif loader.
        $(selectLoaderClass).remove();
        let errorID = 'unknown';
        if (selectedIDs[0].parent !== undefined) {
          errorID = selectedIDs[0].parent;
        }
        else if (selectedIDs[0].children !== undefined) {
          errorID = selectedIDs[0].children;
        }
        // Reset the list.
        if ($lastChangedWrapper) {
          $lastChangedWrapper.children(selectClass).val('All');
        }
        let message = Drupal.t('Could not load the location select for GeoNames ID <q>@error_ID</q>, either due to a website problem or a network connection problem.<br>Please try again or reload the Website and then try again.', {'@error_ID': errorID});
        networkErrorModal(Drupal.t('Network problem!'), message);
      }
    });
  }

  /**
   * Set the values for the textarea into php session.
   *
   * @param {Object} validateObject
   *   The validate object.
   */
  function setSessionWithAjax(validateObject) {
    $.ajax({
      url: Drupal.url('location_selector/geonames_validate'),
      type: 'POST',
      data: {
        validate: validateObject
      },
      dataType: 'json',
      success: function (response) {
        // Nothing.
        // @see location_selector.geonames_validate
      }
    });
  }

  /**
   * After a succesful ajax call.
   *
   * @param {Object} response
   *   The response object.
   */
  function ajaxSuccessCall(response) {
    $.each(response, function (i) {
      if (typeof response[i].parent !== 'undefined' && response[i].parent != null) {
        createSelectList(response[i].parent, i);
      }
      if (typeof response[i].children !== 'undefined' && response[i].children != null) {
        createSelectList(response[i].children, i);
      }
    });
  }

  /**
   * Create the initial list (normally on page load).
   */
  function createInitialList() {
    // If default values are available.
    if (widgetDefaultValues) {
      let selectIDs = {};
      // For normal entity-edit-forms.
      let allDefaultValues = JSON.parse(widgetDefaultValues);
      if (!$.isEmptyObject(allDefaultValues)) {
        // Because of:
        // https://stackoverflow.com/questions/48382457/mysql-json-column-change-array-order-after-saving
        let allDefaultValuesOrdered = {};
        Object.keys(allDefaultValues.path).sort().forEach(function (key) {
          allDefaultValuesOrdered[key] = allDefaultValues.path[key];
        });

        let numItems = Object.keys(allDefaultValuesOrdered).length;

        let counter = 0;
        $.each(allDefaultValuesOrdered, function (i) {
          if (wsLimitLevel && wsLimitLevel > numItems || wsLimitLevel && wsLimitLevel > counter || wsLimitLevel == 0) {
            if (i == 0 && wsParentInclude !== 0 && wsParentInclude !== false) {
              counter++;
              selectIDs[i] = {
                parent: this.val
              };
            }
            if (selectIDs[i]) {
              counter++;
              selectIDs[i]['children'] = this.val;
            }
            else {
              counter++;
              selectIDs[i] = {
                children: this.val
              };
            }
          }
        });
      }
      if (!$.isEmptyObject(selectIDs)) {
        getMetadataWithAjax(selectIDs);
      }
    }
    else if (wsParentID) {
      let selectIDs = {};
      if (wsParentInclude !== 0 && wsParentInclude !== false) {
        selectIDs[0] = {
          parent: widgetSettings.basic_parent_id
        };
      }
      else {
        selectIDs[0] = {
          children: widgetSettings.basic_parent_id
        };
      }
      if (!$.isEmptyObject(selectIDs)) {
        getMetadataWithAjax(selectIDs);
      }
    }
  }

  $(document).on('change', selectWrapperClass, function () {
    $lastChangedWrapper = $(this);
    let select_level = $lastChangedWrapper.children('select').attr('data-location-selector-count');
    let numItems = $(formWrapperClasses + ' ' + selectWrapperClass).length;
    // Remove all other select lists.
    $lastChangedWrapper.nextAll(selectWrapperClass).remove();
    let selectedID = $lastChangedWrapper.children(selectClass).children('option:selected').val();
    if (selectedID !== undefined && selectedID !== 'All') {
      let selectIDs = {};
      selectIDs[0] = {
        children: selectedID
      };
      // Check if a level must be checked.
      if (wsLimitLevel && wsLimitLevel > numItems || wsLimitLevel && wsLimitLevel > select_level || wsLimitLevel == 0) {
        getMetadataWithAjax(selectIDs);
      }
      setValue($lastChangedWrapper, selectedID);
    }
    else if (selectedID === 'All') {
      setValue($lastChangedWrapper, selectedID);
    }

  });

  Drupal.behaviors.location_selector = {
    attach: function (context, settings) {

      $(formWrapperClasses, context).once('location_selector').each(function () {

        // Refill the value because of ajax calls.
        widgetDefaultValues = drupalSettings.location_selector.form_element_default_values;

        // Get all IDs in one single array object.
        getDefaultIDs();

        // Because on views exposed filter with ajax, it can happen, that
        // this method fires multiple.
        if (formType === 'ls--views-exposed-form') {
          let tagName = $(context).prop('tagName');
          if (tagName && tagName != 'FORM') {
            return false;
          }
        }
        createInitialList();
      });
    }
  };


})(jQuery, Drupal, drupalSettings);
