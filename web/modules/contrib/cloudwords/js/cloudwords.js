(function ($, Drupal) {

Drupal.cloudwords = Drupal.cloudwords || {};

Drupal.cloudwords.updateChecked = function(view, op, ids) {
  if (ids) {
    $('.vbo-table-this-page strong').text(ids.length + ' rows');
    var string = ids.join(',');
    var token = drupalSettings.cloudwords.token;
    $.get(drupalSettings.cloudwords.ajaxUrl + '/' + view + '/' + op + '/' + string + '?token=' + token, function(data) {
      //$('.cloudwords-item-count').text(data.text);
    });
  }
}

Drupal.behaviors.cloudwords = {
  attach: function (context, settings) {
    $('.views-field-add-to-project-checkbox input').click(function(event) {
      //console.log($(this).val())
      //event.preventDefault();
      var action = ($(this).is(':checked')) ? 'add' : 'remove';
      $.post('/admin/cloudwords/ajax', {id: $(this).val(), action: action}).done(function( data ) {
        $('.cloudwords-item-count').text(data.text);
      });
    });
  }
};

})(jQuery, Drupal);
