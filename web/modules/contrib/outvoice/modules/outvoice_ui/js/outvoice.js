(function ($, Drupal, drupalSettings) {

  'use strict';

  $(document).ready(function () {

    let domain = 'https://api.outvoice.com';
    let clientID = '08a3b847-025c-40a9-bfc8-4ed891286f99';
    let logo = drupalSettings.outvoice_ui.logo;

    const ovSignIn = '<div class="outvoice-options-title">\n' +
        '<div class="outvoice-title"><img class="outvoice-logo" src="' + logo + '"></div>\n' +
        '</div>\n' +
        '<div class="outvoice-options-body">\n' +
        '<div class="outvoice-body-wrapper"></div>\n' +
        '<div>\n' +
        '<label class="ov-form" for="outvoice-user">Email:</label>\n' +
        '<input class="ov-form" type="text" name="outvoice-user">\n' +
        '<label  class="ov-form" for="outvoice-pass">Pass:</label>\n' +
        '<input class="ov-form ov-password" type="password" name="outvoice-pass">\n' +
        '</div>\n' +
        '<div>\n' +
        '<a class="button ov-form ov-login">Log In</a>\n' +
        '</div>\n' +
        '</div>';

    const ovContent = '<div class="outvoice-body-wrapper"></div>' +
        '<div class="outvoice-contrib-container">' +
        '<div><a class="outvoice-add-contributor">add a contributor</a>' +
        '</div>' +
        '<div class="outvoice-contrib-wrapper">' +
        '<div id="outvoice-contrib">' +
        '<div class="outvoice-options-row freelancer">' +
        '<select name="outvoice-contributor" id="ov-combobox"></select>' +
        '</div>' +
        '<div class="outvoice-options-row payment">' +
        '<select name="outvoice-currency"><option value="USD">USD $</option></select>' +
        '<span class="outvoice-currency-symbol"></span><input class="ov-amount" type="text" size="9" name="outvoice-amount">' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<div><a class="outvoice-add-contributor-1">add contributor</a>' +
        '</div>' +

        '<div class="outvoice-contrib-wrapper">' +
        '<div id="outvoice-contrib-1">' +
        '<hr>' +
        '<div class="outvoice-options-row freelancer">' +
        '<select name="outvoice-contributor-1" id="ov-combobox-1"></select>' +
        '</div>' +
        '<div class="outvoice-options-row payment">' +
        '<select name="outvoice-currency-1"><option value="USD">USD $</option></select>' +
        '<span class="outvoice-currency-symbol"></span><input class="ov-amount" type="text" size="9" name="outvoice-amount-1">' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="outvoice-cancel">' +
        '<a class="outvoice-no-contributor">X</a>' +
        '</div>' +
        '</div>';

    let status = $("input[name='outvoice_access_token']").val();
    if (status.length > 0) {
      // logged in
      $(ovContent).appendTo($('.outvoice-options'));
      ovContributorData();
    }
    else {
      // not logged in
      $(ovSignIn).appendTo($('.outvoice-options'));
      ovInit();
    }

    $('.ov-login').click(function () {
      ovLogin();
    });

    // set values on form submission
    $('#edit-submit').click(function () {
      let amt = $("input[name='outvoice-amount']").val();
      $("input[name='outvoice_amount']").val(amt);
      let amt1 = $("input[name='outvoice-amount-1']").val();
      $("input[name='outvoice_amount_1']").val(amt1);
      let contributor = $("select[name='outvoice-contributor']").val();
      $("input[name='outvoice_contributor']").val(contributor);
      let contributor1 = $("select[name='outvoice-contributor-1']").val();
      $("input[name='outvoice_contributor_1']").val(contributor1);
    });

    function ovContributorData() {
      // get values from form
      let amt = $("input[name='outvoice_amount']").val();
      let amt1 = $("input[name='outvoice_amount_1']").val();
      let contributor = $("input[name='outvoice_contributor']").val();
      let contributor1 = $("input[name='outvoice_contributor_1']").val();
      let accessToken = $("input[name='outvoice_access_token']").val();
      let contributorList = [];
      let contributorList1 = [];
      // refresh contributor lists
      $.getJSON({
        url: domain + '/api/v1.0/list-freelancers',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('Authorization', 'Bearer ' + accessToken);
        }
      }).done(function (data) {
        let count = 0;
        $.each(data, function (index, item) {
          $.each(item, function (key, contrib) {
            contributorList[count] = {
              value: key,
              text: contrib
            };
            contributorList1[count] = {
              value: key,
              text: contrib
            };
            if (amt.length > 0 && key === contributor) {
              contributorList[count]['selected'] = true;
            }
            if (amt1.length > 0 && key === contributor1) {
              contributorList1[count]['selected'] = true;
            }
            count++;
          });
        });
        // if first contributor
        if (amt.length > 0) {
          $('#ov-combobox').ovcombobox({fullMatch: true, showDropDown: false, data: contributorList});
          $("input[name='outvoice-amount']").val(amt);
          $("input[name='outvoice-amount-1']").val(amt1);
        }
        else {
          $('#ov-combobox').ovcombobox({fullMatch: true, showDropDown: false, empty: true});
          $('.ovcombobox-display').attr('placeholder', '-- select contributor --');
        }
        // if second contributor
        if (amt1.length > 0) {
          $('#ov-combobox-1').ovcombobox({fullMatch: true, showDropDown: false, data: contributorList1});
          $('#outvoice-contrib-1').show();
          $('.outvoice-add-contributor-1').hide();
        }
        else {
          // no second contributor
          $('.outvoice-add-contributor-1').click(function () {
            $('#ov-combobox-1').ovcombobox({fullMatch: true, showDropDown: false, empty: true, data: contributorList1});
            $('#ov-combobox-1 .ovcombobox-display').attr('placeholder', '-- select contributor --');
            $('#outvoice-contrib-1').show();
            $(this).hide();
          });
        }
        $('.outvoice-add-contributor').click(function () {
          $('#outvoice-contrib').show();
          $('.outvoice-add-contributor-1').show();
          $(this).hide();
          $('.outvoice-no-contributor').show();
        });
        $('.outvoice-no-contributor').click(function () {
          $('#outvoice-contrib').hide();
          $('#outvoice-contrib-1').hide();
          $('.outvoice-add-contributor').show();
          $('.outvoice-add-contributor-1').hide();
          $(this).hide();
          $('#edit-submit').off();
          ovClearFields();
        });
        ovNumeric();
        $('.outvoice-body-wrapper').removeClass('loading');

      }).fail(function (data) {
        alert('There has been an error. Please log in to Outvoice again.');
      });
    }

    function ovInit() {
      $('#ov-combobox').ovcombobox({fullMatch: true, showDropDown: false, empty: true});
      $('.ovcombobox-display').attr('placeholder', '-- select contributor --');
      $('.outvoice-add-contributor').click(function () {
        $('#outvoice-contrib').show();
        $('.outvoice-add-contributor-1').show();
        $(this).hide();
        $('.outvoice-no-contributor').show();
      });
      $('.outvoice-add-contributor-1').click(function () {
        $('#ov-combobox-1').ovcombobox({fullMatch: true, showDropDown: false, empty: true});
        $('.ovcombobox-display').attr('placeholder', '-- select contributor --');
        $('#outvoice-contrib-1').show();
        $(this).hide();
      });
      $('.outvoice-no-contributor').click(function () {
        $('#outvoice-contrib').hide();
        $('#outvoice-contrib-1').hide();
        $('.outvoice-add-contributor').show();
        $('.outvoice-add-contributor-1').hide();
        $(this).hide();
        $('#edit-submit').off();
        ovClearFields();
      });
      ovNumeric();
    }

    function ovNumeric() {
      $('.ov-amount').keydown(function (e) {
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            // Allow: Ctrl+A, Command+A
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
            // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
          // let it happen, don't do anything
          return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
          e.preventDefault();
        }
      });
    }

    function getToken(domain, clientID, username, password) {
      let tokenData = {
        client_id: clientID,
        grant_type: 'password',
        username: username,
        password: password
      };
      let accessToken = 'Login failed';
      $.ajax({
        url: domain + '/oauth/token',
        type: 'POST',
        data: $.param(tokenData)
      }).done(function (token) {
        accessToken = token.access_token;
        $("input[name='outvoice_access_token']").val(accessToken);
        let refreshToken = token.refresh_token;
        $("input[name='outvoice_refresh_token']").val(refreshToken);
        $('.outvoice-options-body').empty();
        $(ovContent).appendTo($('.outvoice-options-body'));
        contributorList(accessToken, domain, true);
        $("input[name='ov-user']").val(username);
        $("input[name='ov-pass']").val(password);
      }).fail(function () {
        $('.outvoice-body-wrapper').removeClass('loading');
        alert('Login Failed');
      });
      return accessToken;
    }

    function contributorList(token, domain) {
      let output = '';
      $.getJSON({
        url: domain + '/api/v1.0/list-freelancers',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('Authorization', 'Bearer ' + token);
        }
      }).done(function (data) {
        $.each(data, function (index, item) {
          $.each(item, function (key, value) {
            $('#ov-combobox, #ov-combobox-1').append(
                $('<option></option>')
                    .text(value)
                    .val(key)
            );
          });
        });
        ovInit();
        $('.outvoice-body-wrapper').removeClass('loading');
      }).fail(function (data) {
        alert('There has been an error. Please log in to Outvoice again.');
      });
      return output;
    }

    function ovClearFields() {
      $('.ovcombobox-value').val('');
      $('.ovcombobox-display').val('');
      $('.ov-amount').val('');
      $("input[name='outvoice_amount']").val('');
      $("input[name='outvoice_amount_1']").val('');
      $("input[name='outvoice_contributor']").val('');
      $("input[name='outvoice_contributor_1']").val('');
    }

    function ovLogin() {
      $('.outvoice-body-wrapper').addClass('loading');
      let username = $("input[name='outvoice-user']").val();
      let password = $("input[name='outvoice-pass']").val();
      getToken(domain, clientID, username, password);
    }

  });

})(jQuery, Drupal, drupalSettings);
