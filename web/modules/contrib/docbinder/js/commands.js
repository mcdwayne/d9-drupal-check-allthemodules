(function($, Drupal) {
  /**
   * Add new command for adding a file to collection.
   */
  Drupal.AjaxCommands.prototype.addFile = function(ajax, response, status) {
    // Place content in files list div.
    if (response.status === 200) {
      $('#docbinder-block #docbinder-block-files').append('<li id="docbinder-file-' + response.fid + '">' + response.filename + ' <a class="use-ajax" href="/docbinder/remove/' + response.fid + '" style="color:red;">del</a></li>');
      //console.log("COMMAND", ajax, response, status);
      console.log('DocBinder: added file #' + response.fid);
    }
    else if (response.status === 304) {
      console.log('DocBinder: file #' + response.fid + ' is already in list');
    }
    else if (response.status === 500) {
      console.log('DocBinder error');
    }
  };

  /**
   * Add new command for removing a file from collection.
   */
  Drupal.AjaxCommands.prototype.removeFile = function(ajax, response, status) {
    // Place content in files list div.
    if (response.status === 200) {
      $('#docbinder-file-' + response.fid).remove();
      //console.log("COMMAND", ajax, response, status);
      console.log('DocBinder: removed file #' + response.fid);
    }
    else if (response.status === 404) {
      console.log('DocBinder: file #' + response.fid + ' not found');
    }
    else if (response.status === 500) {
      console.log('DocBinder error');
    }
    $('#docbinder-page #docbinder-page-files #docbinder-page-file-' + response.fid).slideToggle(500, function() {
      $('#docbinder-page #docbinder-page-files #docbinder-page-file-' + response.fid).remove();
    });
  };

  /**
   * Add new command for updating file count for collection.
   */
  Drupal.AjaxCommands.prototype.updateFileCount = function(ajax, response, status) {
    // Place content in files list div.
    let c = response.count;
    console.log(c);
    // $('#docbinder-file-count').html(response.count);
    if (c === 1) {
      $('#docbinder-file-count').html('is ' + response.count + ' file');
    }
    else {
      $('#docbinder-file-count').html('are ' + response.count + ' files');
    }
    // if (response.status === 200) {
    //   $('#docbinder-file-' + response.fid).remove();
    //   //console.log("COMMAND", ajax, response, status);
    //   console.log('DocBinder: removed file #' + response.fid);
    // }
    // else if (response.status === 404) {
    //   console.log('DocBinder: file #' + response.fid + ' not found');
    // }
    // else if (response.status === 500) {
    //   console.log('DocBinder error');
    // }
  };

})(jQuery, Drupal);