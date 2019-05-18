/*
 * @file
 * Retrieve data from form.
 */
(function ($) {
  /*
   * Implements hook_contentanalysis_data()
   * Gets the data from the custom fields to attach to the AJAX post data.
   */
  var contentanalysisexample_contentanalysis_data = function () {
    var data = [];
    data['name'] = document.getElementById('edit-contentanalysisexample-name').value;
    return data;
  }
})(jQuery);
