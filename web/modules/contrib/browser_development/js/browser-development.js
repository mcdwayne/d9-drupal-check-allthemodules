(function ($) {
  Drupal.behaviors.browser_development = {
    attach: function (context, settings) {

      'use strict';
      /**
       * Global vars
       */
      var idGettingFrontEndChange;
      var ignoreIdString = '#browser-development-panel, #browser-development-panel *, ' +
        '#toolbar-administration, #toolbar-administration *, ' +
        '.browser-development-menu, .browser-development-menu * ' +
        '.coffee-form-wrapper, .coffee-form-wrapper *' +
        '.local-tasks-block, .local-tasks-block *'+
        '#site-feedback, #site-feedback *'+
        '.ui-widget-content , .ui-widget-content  *';


      /**
       * Finds ids in DOM so that a link can be added
       */
      $('*[id]', context).once('browser_development').not($(ignoreIdString)).each(function () {
        var idVar = $(this).attr('id');
        $('#' + idVar).prepend(
          '<div class="browser-development-menu-wrapper">' +
          '<ul  class="browser-development-menu" >' +
          '<li>' +
          '<a href="#" class="browser-development-menu-a" title="' + idVar + '" id="' + idVar + '">' +
          '<i class="browser-development-cog fa fa-cog"></i>' +
          '</a>' +
          '</li>' +
          '</ul>' +
          '</div>'
        );
      });


      /**
       * Displays the drop down box for the gears
       */
      //  $('#block-browserdevelopmentblock').toggle();
      $('.browser-development-menu-a', context).click(function (e) {
        e.preventDefault();
        var idVar = $(this).attr('id');

        $('.browser-development-bind-menu-id a').attr('title', idVar).attr('id', 'bd-' + idVar);
        //$('#block-browserdevelopmentblock').toggle();
        $('.help-2').addClass('open');
        $('.help-1').removeClass('open');

      });
      /**
       *  Set side panel height
       */
      var browserHeight = $(document).height();
      browserHeight = browserHeight + 100;
      $('#browser-development-panel').css({
        "height": browserHeight
      });


      /**
       *  Hides all the gears
       */


      //-- to display edit gears
      //$('#toolbar-bar', context).append('<div class="toolbar-tab"><a href="" id="edit-display-gears" class="toolbar-item">Edit design</a></div>');
      $('#edit-display-gears', context).click(function (e) {
        e.preventDefault();

        if ($('#browser-development-panel').hasClass('open')) {

          $('.browser-development-bind-menu-id').removeClass('open');
          $('.toolbar-oriented .toolbar-tray').removeClass('open');
         // $('#block-browserdevelopmentblock').removeClass('open');
          $('#browser-development-panel').removeClass('open');
          $('.browser-development-menu').removeClass('open');
          $('.toolbar-oriented').removeClass('open');
          $('.toolbar-bar').removeClass('open');
          $('.help-1').removeClass('open');
          $('.help-2').removeClass('open');

          setWebsiteNoDesignPosition();

        }
        else {

          $('.browser-development-bind-menu-id').addClass('open');
          $('.toolbar-oriented .toolbar-tray').addClass('open');
          //$('#block-browserdevelopmentblock').addClass('open');
          $('#browser-development-panel').addClass('open');
          $('.browser-development-menu').addClass('open');
          $('.toolbar-oriented').addClass('open');
          $('.toolbar-bar').addClass('open');
          $('.help-2').removeClass('open');
          $('.help-1').addClass('open');

          setWebsiteDesignPosition();
        }
      });

      /**
       * Color box functionality for the background colors inside the id
       */
      $('.id-background-color', context).click(function (e) {
        e.preventDefault();
        initializeSpectrumObject(this);
        liveIdChanges();
        getSpectrumColorAndAddCssToTheElement('edit-background-text-field', 'background-color');
        cancelSpectrumObject();
      });


      /**
       * Color box functionality for text colors inside the id
       */
      $('.id-text-color', context).click(function (e) {
        e.preventDefault();
        initializeSpectrumObject(this);
        liveIdChanges();
        getSpectrumColorAndAddCssToTheElement('edit-background-text-field', 'color');
        cancelSpectrumObject()
      });


      /**
       * Background image box functionality and css on id
       */

      $('.id-background-image', context).click(function (e) {
        e.preventDefault();
        idGettingFrontEndChange = $(this).attr('id');
        getBackGroundImagePathAndAddCssToTheElement('edit-background-image-field', 'background-image');
        $('.browser-development-image-form').toggle();


      });
      /**
       * If menu is fixed to browser header it will push it down
       */
      function setWebsiteDesignPosition() {
        _
        $('body > header').css({
          "margin-left": "250px",
          "margin-right": "50px",
          "width": "82%"
        });
        $('body > #content-area').css({
          "margin-left": "250px",
          "margin-right": "50px",
          "width": "82%"
        });
        $('body > footer').css({
          "margin-left": "250px",
          "margin-right": "50px",
          "width": "82%"
        });
        $('header > .navbar-fixed-top').css({
          "top": "80px",
          "right": "inherit",
          "left": "inherit",
          "width": "82%"
        });
      }

      /**
       * If menu is fixed to browser header it will push it down
       */
      function setWebsiteNoDesignPosition() {
        _
        $('body > header').css({
          "margin-left": "inherit",
          "margin-right": "inherit",
          "width": "inherit"
        });
        $('body > #content-area').css({
          "margin-left": "inherit",
          "margin-right": "inherit",
          "width": "inherit"
        });
        $('body > footer').css({
          "margin-left": "inherit",
          "margin-right": "inherit",
          "width": "inherit"
        });
        $('header > .navbar-fixed-top').css({
          "top": "85px",
          "right": "0",
          "left": "0",
          "width": "inherit"
        });
      }

      /**
       * Creates instance color box object
       *
       */
      function initializeSpectrumObject(objId) {
        idGettingFrontEndChange = $(objId).attr('id');
        console.log($(objId).attr('id'));
        $(objId).parent().addClass('display-only');
        $('.browser-development-bind-menu-id li').not('.display-only').css({
          'display': 'none'
        });
        $("#browser-development-spectrum-input").spectrum({
          preferredFormat: "hex3",
          flat: true,
          showInput: true,
          showPalette: true
        });
      }


      /**
       * Cancels instance color box object
       */
      function cancelSpectrumObject() {
        $('.sp-cancel').click(function () {
          idGettingFrontEndChange = $('.sp-input').val();
          $("#browser-development-spectrum-input").spectrum('destroy').css('display', 'none');
          $('.browser-development-bind-menu-id li').css({'display': 'block'}).removeClass('display-only');
        });
      }


      /**
       *  Adds colors to ids and adds it to the textbox ready for saving
       * @param textFieldSaveId
       * @param cssElementString
       */
      function getSpectrumColorAndAddCssToTheElement(textFieldSaveId, cssElementString) {
        $('.sp-choose').click(function () {
          var stringToBeAdded = '#' + idGettingFrontEndChange.substr(3) + '{' + cssElementString + ':' + $('.sp-input').val() + ';}';
          $('#' + textFieldSaveId).val(function (index, oldText) {
            return stringToBeAdded + oldText;
          });

          $('#' + idGettingFrontEndChange.substr(3)).attr('style', cssElementString + ':' + $('.sp-input').val());
          $("#browser-development-spectrum-input").spectrum('destroy').css('display', 'none');
          $('.browser-development-bind-menu-id li').css({'display': 'block'}).removeClass('display-only');

          setWebsiteDesignPosition();
        });
      }

      /**
       *
       */
      function liveIdChanges() {
        console.log($('.sp-input').val());
      }


      /**
       * Colors
       */
      /**
       * Live id color change id
       */
      /*
       $( ".sp-input").change(function() {
       console.log('have I made it');
       idGettingFrontEndChange = $('.sp-input').val();
       $('#' + clickedIdGettingNewColor).attr('style','background-color:' + idGettingFrontEndChange);
       });
       */


      /**
       * Images
       */
      function getBackGroundImagePathAndAddCssToTheElement(textFieldSaveId) {
        var stringToBeAdded = '#' + idGettingFrontEndChange.substr(3);
        $('#' + textFieldSaveId).val(function (index, oldText) {
          return stringToBeAdded + oldText;
        });

        $("#browser-development-spectrum-input").spectrum('destroy').css('display', 'none');
        $('.browser-development-bind-menu-id li').css({'display': 'block'}).removeClass('display-only');

        setWebsiteDesignPosition();
      }
    }
  };
})(jQuery);
