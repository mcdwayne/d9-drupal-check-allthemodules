Drupal.autocomplete.options.select = function selectHandler(event, ui) {
  var terms = Drupal.autocomplete.splitValues(event.target.value);
  // Remove the current input.
  terms.pop();
  // Add the selected item.
  if (ui.item.value.search(',') > 0) {
    terms.push('"' + ui.item.value + '"');
  }
  else {
    terms.push(ui.item.value);
  }
  event.target.value = terms.join(', ');
  jQuery(event.target).trigger('autocomplete-select');

  // Return false to tell jQuery UI that we've filled in the value already.
  return false;
}

