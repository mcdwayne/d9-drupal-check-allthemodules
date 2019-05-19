(function ($, Drupal) {
  'use strict';

  var alter_variable = function (button) {

    /**
     * private methods
     */

    // Get Current Config
    var get_current_config = function () {
      var config = $(button)
          .closest('.js-variables-editor-wrapper')
          .find('textarea')
          .text();
      return JSON.parse(config);
    };

    // Variable Key
    var key = function () {
      return $(button).closest('tr').attr('name');
    };

    // New value for variable key
    var new_value = function () {
      var value = $(button).closest('tr').find('input').val();
      return $.trim(value);
    };

    var original_config_value = function () {
      return $(button).closest('tr').find('td.config').attr('original');
    };

    var set_new_config = function (new_config) {
      $(button)
        .closest('.js-variables-editor-wrapper')
        .find('textarea')
        .text(new_config);
    };

    // Update configuration
    this.update = function (data = false) {
      var config = get_current_config();
      var config_to_string = JSON.stringify(config);
      var n_value = '';
      if (data) {
        n_value = data;
      }
      else {
        n_value = new_value();
      }

      // call markup object
      var markup = new alter_markup(button);

      // call private method to retrive currente config
      config = get_current_config();

      // check current value if empty
      if ((config_to_string === '{"":""}') || (config_to_string === '[]')) {

        // set key and new value
        config = {[key()]: n_value};
      }
      else {
        if (n_value !== '') {
          config[key()] = n_value;
        }
        else {
          delete config[key()];
        }
      }

      // Make unsaved value, compare original value with now value
      if (n_value === original_config_value()) {
        markup.reset_unsaved_value();
      }
      else {
        markup.make_unsaved_value();
      }

      // Fix empty object with fake value
      var new_config_to_string = JSON.stringify(config);
      if (config_to_string === '{}') {
        new_config_to_string = '{"":""}';
      }

      // update current config
      set_new_config(new_config_to_string);

      // remove markup input with text
      markup.place_new_config_value(n_value);
    };

    this.reset = function () {
      var data = original_config_value();
      this.update(data);
    };

    this.remove = function () {
      var data = '';
      this.update(data);
    };
  };

  var alter_markup = function (button) {

    var table_td_wrapper = function () {
      return $(button). closest('tr').find('.config');
    };

    this.place_new_config_value = function (new_value) {

      var td = table_td_wrapper();
      td.empty().html(makeValue(new_value));
      $(button).removeClass('action-start');
    };

    this.add_input = function () {
      var td = table_td_wrapper();
      var current_value = td.text();
      var html = '<input type="text" value="' + current_value + '" class="form-text"><i class="js-button sm-icon-cancel cancel"></i> <i class="js-button sm-icon-save save " ></i> ';
      td.empty().html(html);
    };

    this.make_unsaved_value = function () {
      var td = table_td_wrapper();
      td.addClass('unsaved');
    };
    this.reset_unsaved_value = function () {
      var td = table_td_wrapper();
      td.removeClass('unsaved');
    };
  };

  function isHexaColor(sNum) {
    sNum = sNum.replace('#', '');
    return (typeof sNum === 'string') && (sNum.length === 6) && (!isNaN(parseInt(sNum, 16)));
  }

  function makeValue(value) {
    if (isHexaColor(value)) {
      return '<ul class="sm-list-inline"> <li><div style="width:15px; height:15px; border: 1px solid  #cccc; background-color:' + value + '; margin-right: 10px;"></div> </li><li><span>' + value + '</span></li></ul>';
    }
    return value;
  }

  function edit_config(button) {

    // Action: submit - cacel.
    if (!$(button).closest('tr').find('i').hasClass('.js-button')) {

      var markup = new alter_markup(button);
      markup.add_input();

      var row_name = $(button).closest('tr').attr('name');
      $("tr[name='" + row_name + "'] .js-button.cancel").click(function () {
        var js_button_reset = new alter_variable(button);
        js_button_reset.reset();
      });


      $("tr[name='" + row_name + "'] .js-button.save").click(function () {
        var js_button_save = new alter_variable(button);
        js_button_save.update();
      });
    }
  }

  function delete_config(button) {
    var js_button_remove = new alter_variable(button);
    js_button_remove.remove();
  }

  // Make markup with new value and append to body
  function add_new_variables(table_body) {
    var id = 'id-'+ (+ new Date());
    var markup = '<tr name="@icon-font-path" id="' + id + '">';
    markup += '      <td><i class="sm-icon-file"></i></td>';
    markup += '      <td class="name">@<input type="text"></td>';
    markup += '      <td class="file"></td>';
    markup += '      <td class="config"><input type="text"</td>';
    markup += '      <td><i class="js-button sm-icon-save" ></i></td>';
    markup += '      <td><i class="js-button sm-icon-cancel"></i></td>';
    markup +=  '</tr>';

    $(table_body).append(markup);
    $('html, body').animate({
      scrollTop: $("#" + id).offset().top
    }, 2000);
  }

  Drupal.behaviors.var_edit = {
    attach: function (context, settings) {

      $('.js-button', context).once('gia-sai').each(function () {
        $(this).click(function () {
          if (!$(this).hasClass('action-start')) {
            $(this).addClass('action-start');
            var action = $(this).attr('action');
            var file = $(this).closest('.js-variables-editor-wrapper').attr('file');

            switch (action) {
              case 'create':
                break;
              case 'add':
                 var variable_name = $(this).closest('tr').attr('name');
                 var table_body = $(this).closest('.js-live-editor').find('tbody');
                 add_new_variables(table_body);
                break;

              case 'edit':

                edit_config($(this));

                break;

              case 'delete':

                delete_config($(this));

                break;
            }
          }
        });
      });
    }
  };
})(jQuery, Drupal);
