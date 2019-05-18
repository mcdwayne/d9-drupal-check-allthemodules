(function ($) {
  Drupal.behaviors.paragraphs_wizard = {
    attach: function(context, settings) {
      $('.paragraphs-wizard-wrapper .next').click(function(){
        Drupal.behaviors.paragraphs_wizard.calculate_next_slide($(this),"next");
      });
      $('.paragraphs-wizard-wrapper .previous').click(function(){
        Drupal.behaviors.paragraphs_wizard.calculate_next_slide($(this),"prev");
      });
    },
    /**
     * Calculates next slide to show from the "wrapper_selector" element and the type ("next","prev")
     * @param wrapper_selector jQuery node
     * @param type
     */
    calculate_next_slide : function (wrapper_selector,type) {
      var wizard_wrapper = $(wrapper_selector).closest('.paragraphs-wizard-wrapper');
      var curid = $(wizard_wrapper).find('.paragraphs-wizard-element:visible').attr('data-id');
      if(type == "next") {
        var nextid = parseInt(curid) + 1;
      } else {
        var nextid = parseInt(curid) - 1;
      }
      var nextwrap = $(wrapper_selector).closest(".paragraphs-wizard-wrapper").find("div[data-id='" + nextid + "']");
      if($(nextwrap).length > 0) {
        $('.progress-indicator li').removeClass('completed');
        $(".progress-indicator li[data-id='"+nextid+"']").addClass('completed');
        //if the next wrapper exists show next content
        $(wizard_wrapper).find('.paragraphs-wizard-element').hide();
        $(nextwrap).show();
      }
      if($(nextwrap).attr('data-id') === $(wizard_wrapper).find(".paragraphswizard-lastel").attr('data-id')){
        //opening last slide
        $(wizard_wrapper).find('.next').hide();
        $(wizard_wrapper).find('.previous').show();
      } else {
        $(wizard_wrapper).find('.previous').show();
        $(wizard_wrapper).find('.next').show();
      }
      if($(nextwrap).attr('data-id') === $(wizard_wrapper).find('.paragraphswizard-firstel').attr('data-id')){
        //we are moving to the first element so hide back button
        $(wizard_wrapper).find('.previous').hide();
        $(wizard_wrapper).find('.next').show();
      }
    }
  }
}(jQuery));