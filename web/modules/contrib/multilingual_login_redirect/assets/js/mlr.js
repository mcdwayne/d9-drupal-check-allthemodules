/**
 * @file
 * Created by alessandro on 19/04/17.
 */

(function ($) {
  'use strict';
  // Events.
  $(document).on('click', '.new_exc_button', function (e) {
    e.preventDefault();
    addExceptionInForm(this);
  });

  $(document).on('click', '.mlr-exception-row .button--save-exception', function (e) {
    e.preventDefault();
    $('#edit-submit').click();
  });

  $(document).on('click', '.mlr-exception-row-pre .mlr-delete-row', function (e) {
    e.preventDefault();
    clearExceptionFromForm(this);
  });

  $(document).on('click', '.mlr-exception-row .mlr-delete-row', function (e) {
    e.preventDefault();
    $('.mlr-exception-row').remove();
    $('#edit-submit, .mlr-delete-row').show();
    $('.mlr-exception-row-pre input[type="text"]').removeAttr('disabled');
    $(this).parent().remove();
  });

  $(document).on('change', '.role-select', function () {
    var selectID = this.id;
    var selectLang = $(this).attr('lang');
    var selectRole = $('#' + selectID).val();
    setMlrRedictFieldNameAttr(
      selectID,
      'mlr_destination__' + selectLang + '__' + selectRole,
      'edit-mlr-destination-' + selectLang + '-' + selectRole
    );
  });

  // Functions.
  function addExceptionInForm(selectedButton) {
    var thisButton = $(selectedButton);
    var thisLanguage = thisButton.attr('language');
    var thisSelectId = thisLanguage + '_roles';
    var roles = JSON.parse(drupalSettings.multilingual_login_redirect.js.roles);
    var exceptionHtml = '';

    if (roles) {
      var optionsCount = 0;
      var roleTextBefore = 'If User role is ';

      exceptionHtml += '<div class="mlr-exception-row">' + roleTextBefore + '<select id="' + thisSelectId + '" class="role-select" lang="' + thisLanguage + '">';
      $.each(roles, function (index, role) {
        var thisRoleEditField = '#edit-mlr-destination-' + thisLanguage + '-' + role;
        if (!$(thisRoleEditField).length > 0 || !$(thisRoleEditField).is(':visible')) {
          exceptionHtml += '<option value="' + role + '">' + role + '</option>';
          optionsCount++;
        }
      });

      var roleTextAfter = ' and language is <b>' + thisLanguage + '</b> redirect to ';
      var uriInput = '<input type="text" data-drupal-selector="" name="" size="60" maxlength="128" />';
      exceptionHtml += '</select>' + roleTextAfter + uriInput + '<a class="mlr-delete-row" href="#">[ Delete ]</a><button class="button button--primary button--save-exception">Save</button></button></div>';

      if (!$('#' + thisSelectId).length > 0) {
        if (optionsCount > 0) {
          $('#edit-submit, .mlr-delete-row').hide();
          $('.mlr-exception-row').remove();
          $('.mlr-exception-row-pre input[type="text"]').attr('disabled', 'disabled');
          thisButton.before(exceptionHtml);
          var thisRole = $('#' + thisSelectId).val();
          setMlrRedictFieldNameAttr(
            thisSelectId,
            'mlr_destination__' + thisLanguage + '__' + thisRole,
            'edit-mlr-destination-' + thisLanguage + '-' + thisRole
          );
        }
        else {
          thisButton.parent().find('.mlr-error').remove();
          thisButton.before('<div class="mlr-exception-row mlr-error"><p>All the roles are assigned, it\'s not possible to create new rows</p></div>');
        }
      }
      else {
        thisButton.parent().find('.mlr-error').remove();
        thisButton.before('<div class="mlr-exception-row mlr-error"><p>Save this redirect before you can add a new one</p></div>');
      }
    }
  }

  function setMlrRedictFieldNameAttr(fieldName, nameValue, drupalSelector) {
    var fieldId = '#' + fieldName;
    $(fieldId).nextAll('input[type="text"]').filter(':first').attr({
      'name': nameValue,
      'data-drupal-selector': drupalSelector,
      'id': drupalSelector
    });
  }

  function clearExceptionFromForm(removeButton) {
    var btnContainer = $(removeButton).parent();
    btnContainer.find('input[type="text"]').attr('value', '');
    btnContainer.fadeOut();
    $(removeButton).parent().nextAll('.mlr-exception-row').filter(':first').remove();
  }

})(jQuery);
