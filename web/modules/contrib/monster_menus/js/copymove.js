(function ($, Drupal) {
  Drupal.MMCopyMoveAlert = function () {
    if (drupalSettings.MM.copymove.mustcheck) {
      alert(drupalSettings.MM.copymove.mustcheck);
    }
    return false;
  };

  Drupal.MMHideNameDiv = function () {
    var state;
    if ($('input[name=mode]')[0].checked) {
      $('#namediv').toggle(state = $('#edit-copy-page')[0].checked);
    }
    else {
      var x = $('input[name=move_mode]');
      $('#namediv').toggle(state = x.length == 2 && x[0].checked);
    }
    // Set the required attribute to coincide with the visibility.
    $('#edit-name,#edit-alias').trigger({type: 'state:required', value: state, trigger: true});
    return true;
  };

  Drupal.MMHideCopyDesc = function () {
    !$('#edit-copy-page')[0].checked && $('#edit-copy-nodes')[0].checked ? $('#copydiv .description').show() : $('#copydiv .description').hide();
  };

  Drupal.behaviors.MMCopyMove = {
    attach: function (context) {
      $('form.mm-ui-copymove', context).once('mm-ui-copymove').each(function () {
        $('input[name=mode]', this)
          .click(function () {
            $('#copydiv').toggle(this.value != 'move');
            $('#movediv').toggle(this.value == 'move');
            return Drupal.MMHideNameDiv();
          });
        $('input[name=copy_page]')
          .click(function () {
            if (!this.checked && (!$('#edit-copy-nodes').length || !$('#edit-copy-nodes')[0].checked)) {
              return Drupal.MMCopyMoveAlert();
            }
            var x = $('#edit-copy-subpage').attr('disabled', !this.checked).parent();
            this.checked ? x.removeClass('disabled') : x.addClass('disabled');
            Drupal.MMHideCopyDesc();
            return Drupal.MMHideNameDiv();
          });
        $('input[name=copy_nodes]')
          .click(function () {
            if (!this.checked && !$('#edit-copy-page').is(':checked')) {
              return Drupal.MMCopyMoveAlert();
            }
          });
        $('input[name=move_mode]')
          .click(Drupal.MMHideNameDiv);
        Drupal.MMHideCopyDesc();
        $('input[name=mode]:checked')
          .click();
      });
    }
  };
})(jQuery, Drupal);