(function($, Drupal, drupalSettings) {
  Drupal.behaviors.ElbowRoom = {
    attach: function() {
      // Get the form... add a link above
      var link = this.createLink();
      link.addEventListener('click', this.clickLink);
      // Add the link to the form.
      var wrapper = document.createElement('div');
      wrapper.setAttribute('class', 'elbow-room-link-wrapper');
      wrapper.appendChild(link);
      $('form.node-form').once().prepend(wrapper);
      if (drupalSettings.elbow_room.default) {
        link.click();
      }
    },
    /**
     * Create a link element to add to the node form.
     * @returns {HTMLAnchorElement}
     */
    createLink: function() {
      var link = document.createElement('a');
      link.innerHTML = 'Hide sidebar';
      link.setAttribute('href', '#');
      link.setAttribute('class', 'elbow-room-action');
      return link;
    },
    /**
     * Callback function for the click event on the link.
     * @param e
     */
    clickLink: function(e) {
      if ($(this).hasClass('has-elbow-room')) {
        $(this).removeClass('has-elbow-room').html('Hide sidebar');
        // $('.layout-region-node-secondary').show();
        $('form.node-form div.layout-node-form').removeClass('elbow-room');
      }
      else {
        $(this).addClass('has-elbow-room').html('Show sidebar');
        // $('.layout-region-node-secondary').hide();
        $('form.node-form div.layout-node-form').addClass('elbow-room');
      }
      e.preventDefault();
    }
  }
})(jQuery, Drupal, drupalSettings);
