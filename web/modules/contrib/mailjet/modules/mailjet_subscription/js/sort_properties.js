(function ($) {

    // Sorted field is empty by default
    var sortedFieldsArray = [];

    // Get last saved data value
    var sortFieldsValue = $('#edit-sort-fields').val();
    if (sortFieldsValue !== '') {
        // Split last saved data to an array
        sortedFieldsArray = $('#edit-sort-fields').val().split(',');
    }

    $("#mailjet-subscription-form-edit-form .form-checkboxes .form-checkbox").click(function () {
        // Value of the checkbox that just has been clicked
        var selectedProperty = $(this).val();

        // Check if the property is alredy in the sorted array
        var index = sortedFieldsArray.indexOf(selectedProperty);

        if (index === -1) {
            // Add property to the sorted array
            sortedFieldsArray.push(selectedProperty);
        } else {
            // Remove property from the sorted array
            sortedFieldsArray.splice($.inArray(selectedProperty, sortedFieldsArray), 1);
        }

        var sortedFieldsString = '';
        if (sortedFieldsArray.length > 0) {
            // Implode as string comma separated string
            sortedFieldsString = sortedFieldsArray.join(',');
        }

        // Fill the sort field
        $('#edit-sort-fields').val(sortedFieldsString);
    });

})(jQuery);