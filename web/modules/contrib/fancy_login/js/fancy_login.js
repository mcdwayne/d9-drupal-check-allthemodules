/**
 * @file
 * Sets up JavaScript functionality for the Fancy Login modals.
 */

/*global jQuery, Drupal, drupalSettings, window*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal, drupalSettings, window) {

  "use strict";

  var initialized, popupVisible, ctrlPressed, fancyLoginBox, messageContainer, formLoadDimmer;

  popupVisible = false;
  ctrlPressed = false;

  function moveMessages() {
    var messages = $("#fancy_login_login_box .messages");

    if (messages.length) {
      if (!messageContainer) {
        messageContainer = $("<div/>", {id:"fancy_login_messages_container_wrapper"}).prependTo("body");
      }

      messages.each(function () {
        $(this).appendTo(
          $("<div/>", {"class":"fancy_login_messages_container"}).appendTo(messageContainer)
        ).before(
          $("<div/>", {"class":"fancy_login_message_close_button"}).text("X")
        );
      });

      Drupal.attachBehaviors(messageContainer);
    }
  }

  function showLogin() {
    var settings = drupalSettings.fancyLogin;

    if (!popupVisible) {
      popupVisible = true;
      if (settings.hideObjects) {
        $("object, embed").css("visibility", "hidden");
      }
      $("#fancy_login_dim_screen").css({
        backgroundColor:settings.screenFadeColor,
        zIndex:settings.screenFadeZIndex,
        opacity:0,
        display:"block"
      })
      .fadeTo(settings.dimFadeSpeed, 0.8, function () {
        var eHeight, eWidth, eTopMargin, eLeftMargin;

        eHeight = fancyLoginBox.height();
        eWidth = fancyLoginBox.width();
        eTopMargin = - 1 * (eHeight / 2);
        eLeftMargin = -1 * (eWidth / 2);

        if ($("#fancy_login_close_button").css("display") === "none") {
          $("#fancy_login_close_button").css("display", "block");
        }

        fancyLoginBox.css({
          marginLeft:eLeftMargin,
          marginTop:eTopMargin,
          color:settings.loginBoxTextColor,
          backgroundColor:settings.loginBoxBackgroundColor,
          borderStyle:settings.loginBoxBorderStyle,
          borderColor:settings.loginBoxBorderColor,
          borderWidth:settings.loginBoxBorderWidth,
          zIndex:(settings.screenFadeZIndex + 1)
        }).fadeIn(settings.boxFadeSpeed).find(".form-text:first").focus().select();
      });
    }
  }

  function hideLogin() {
    var settings = drupalSettings.fancyLogin;

    if (popupVisible) {
      popupVisible = false;
      $("#fancy_login_login_box").fadeOut(settings.boxFadeSpeed, function () {
        $("#fancy_login_dim_screen").fadeOut(settings.dimFadeSpeed, function () {
          if (settings.hideObjects) {
            $("object, embed").css("visibility", "visible");
          }
        });
        $(window).focus();
      });
    }
  }

  function popupCloseListener(context) {
    $(context).find("#fancy_login_dim_screen, #fancy_login_close_button").once("fancy-login-close-listener").each(function () {
      $(this).click(function (e) {
        e.preventDefault();
        hideLogin();
      });
    });
  }

  function statusMessageCloseListener(context) {
    $(context).find(".fancy_login_message_close_button").once("status-message-close-listener").each(function () {
      $(this).click(function () {
        $(this).parent().fadeOut(250, function () {
          $(this).remove();
        });
      });
    });
  }

  function loginLinkListener(context) {
    var settings = drupalSettings.fancyLogin;

    $(context).find("a[href*='" + settings.loginPath + "']:not(.fancy_login_disable), .fancy_login_show_popup:not(.fancy_login_disable)").once("login-link-listener").each(function () {
      $(this).click(function (e) {
        e.preventDefault();
        showLogin();
      });
    });
  }

  function init() {
    if (!initialized) {
      initialized = true;
      fancyLoginBox = $("#fancy_login_login_box");
      $(window.document).keyup(function (e) {
        if (e.keyCode === 17) {
          ctrlPressed = false;
        }
        else if (e.keyCode === 27) {
          hideLogin();
        }
      });
      $(window.document).keydown(function (e) {
        if (e.keyCode === 17) {
          ctrlPressed = true;
        }

        if (ctrlPressed === true && e.keyCode === 190) {
          ctrlPressed = false;

          if (popupVisible) {
            hideLogin();
          }
          else {
            showLogin();
          }
        }
      });
    }
  }

  function popupTextfieldListener() {
    fancyLoginBox.find(".form-text").once("fancy-login-popup-textfield-listener").each(function () {
      $(this).keydown(function (event) {
        if (event.keyCode === 13) {
          $(this).parent().siblings(".form-actions:first").children(".form-submit:first").mousedown();
        }
      });
    });
  }

  function loadForm(type) {
    formLoadDimmer = $("<div/>", {"id":"form_load_dimmer"}).appendTo(fancyLoginBox).fadeTo(250, 0.8);
    $.ajax({
      url:"/fancy_login/ajax/" + type,
      success:function (data) {
        var ajaxObject = Drupal.ajax({
          url: "",
          base: false,
          element: false,
          progress: false
        });

        // Then, simulate an AJAX response having arrived, and let the Ajax
        // system handle it.
        ajaxObject.success(data, "success");
      }
    });
  }

  function insertForm(newForm) {
    var wrapper, oldContents, newContents, oldHeight, newHeight, newMargin;

    wrapper = fancyLoginBox.find("#fancy_login_user_login_block_wrapper");
    oldContents = wrapper.contents();
    oldHeight = wrapper.css("height");
    newContents = $("<div/>").html(newForm).contents();
    $("#fancy_login_user_login_block_wrapper").html(newForm);
    newHeight = wrapper.css("height");
    newMargin = fancyLoginBox.outerHeight() / -2;
    $("#fancy_login_user_login_block_wrapper").html("");
    wrapper.css("height", oldHeight);

    oldContents.fadeOut(250, function () {
      $(this).remove();
      fancyLoginBox.animate({
        marginTop:newMargin
      },
      {
        duration:250
      });

      wrapper.animate({
        height:newHeight
      },
      {
        duration:250,
        complete:function () {
          newContents.appendTo(wrapper).fadeIn(250, function () {
            wrapper.css("height", "auto");
            formLoadDimmer.fadeOut(250, function () {
              $(this).remove();
            });
          });
          Drupal.attachBehaviors(newContents);
        }
      });
    });
  }

  function linkListeners() {
    var anchors = fancyLoginBox.find("a");

    anchors.filter("[href*='user/password']").once("fancy-login-password-link").each(function () {
      $(this).click(function (e) {
        e.preventDefault();
        loadForm("password");
      });
    });

    anchors.filter("[href*='user/login']").once("fancy-login-login-link").each(function () {
      $(this).click(function (e) {
        e.preventDefault();
        loadForm("login");
      });
    });
  }

  Drupal.behaviors.fancyLogin = {
    attach:function (context) {
      if (window.XMLHttpRequest) {
        init();
        loginLinkListener(context);
        popupTextfieldListener();
        popupCloseListener(context);
        moveMessages();
        statusMessageCloseListener(context);
        linkListeners();
      }

      Drupal.AjaxCommands.prototype.fancyLoginRefreshPage = function (ajax, response) {
        // For jSlint compatibility:
        ajax = ajax;

        if (response.closePopup) {
          hideLogin();
        }
        window.location.reload();
      };

      Drupal.AjaxCommands.prototype.fancyLoginRedirect = function (ajax, response) {
        // For jSlint compatibility:
        ajax = ajax;

        if (response.closePopup) {
          hideLogin();
        }

        if (response.destination.length) {
          window.location = response.destination;
        }
        else {
          window.location = "/user";
        }
      };

      Drupal.AjaxCommands.prototype.fancyLoginClosePopup = function () {
        hideLogin();
      };

      Drupal.AjaxCommands.prototype.fancyLoginLoadFormCommand = function (ajax, response) {
        ajax = ajax;

        insertForm(response.form);
      };
    },
    detach:function (context) {
      $(context).find("#fancy_login_dim_screen, #fancy_login_close_button").unbind("click");
      $(context).find(".fancy_login_message_close_button").unbind("click");
      $(context).find(".fancy_login_message_close_button").unbind("click");
      $(context).find("a[href*='" + drupalSettings.fancyLogin.loginPath + "']:not(.fancy_login_disable), .fancy_login_show_popup:not(.fancy_login_disable)").unbind("click");
    }
  };

}(jQuery, Drupal, drupalSettings, window));
