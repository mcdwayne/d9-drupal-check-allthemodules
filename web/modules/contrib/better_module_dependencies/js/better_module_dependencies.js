(function ($) {

Drupal.behaviors.betterModuleDependencies = {
  attach: function(context) {
    var modules = $('form#system-modules details.package-listing table>tbody>tr');
    $('ul.item-list__comma-list li').click(function(event) {
      var moduleName = event.currentTarget.textContent;
      moduleName = moduleName.replace(' (disabled)', '');
      if (moduleName) {
        var module = null;
        modules.each(function(index) {
          if ($(this).find('td.module label').text() == moduleName) {
            module = this;
          }
        });
        if (module) {
          var scrollSize = $(module).offset().top;
          var bodyPadding = $('body').css('padding-top') || 0;
          var scrollTop = scrollSize - parseFloat(bodyPadding);
          $('html, body').animate({scrollTop:  scrollTop}, 500);
        }
        else if($(event.currentTarget).attr('machine_name')) {
          var machineName = $(event.currentTarget).attr('machine_name')
          window.open('http://drupal.org/project/' + machineName);
        }
      }
    });
  }
};

})(jQuery);
