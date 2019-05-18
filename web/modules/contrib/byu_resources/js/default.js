(function($) {
  $('byu-feature-card').attr('without-logo', '');

  $('.views-row').each(function(rowIndex) {
    let nthRow = rowIndex + 1;
    $('.row-' + nthRow + ' .views-col').each(function(colIndex) {
      let nthColumn = colIndex + 1;
      let featureRight = [];

      $('.row-' + nthRow + ' .col-' + nthColumn + ' div[slot="feature-right"]').each(function(index, value) {
        featureRight.push(value.innerHTML);
      }).remove();

      let html = '<div slot="feature-right">';
      featureRight.forEach(function(item, index) {
        html += '<div>' + featureRight[index] + '</div>';
      });
      html += '</div>';

      $('.row-' + nthRow + ' .col-' + nthColumn + ' byu-feature-card').append(html);
    });
  });
})(jQuery);