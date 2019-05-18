(function($, BUE) {
'use strict';

/**
 * @file
 * Defines bueditor popup object.
 */

/**
 * Popup constructor.
 */
BUE.Popup = function(title, content, opt) {
  this.construct(title, content, opt);
};

/**
 * Extend the prototype with event manager and state manager.
 */
var Popup = BUE.extendProto(BUE.Popup.prototype, 'event', 'state');

/**
 * Constructs the popup.
 */
Popup.construct = function(title, content, opt) {
  var Popup = this, type = opt && opt.type;
  // Set default options
  Popup.autoFocus = true;
  if (type === 'dialog') {
    Popup.withOverlay = true;
  }
  else if (type === 'quick') {
    Popup.noHeader = true;
    Popup.autoClose = true;
  }
  BUE.extend(Popup, opt);
  Popup.no = ++BUE.counter;
  Popup.id = 'bue-popup-' + Popup.no;
  BUE.popups[Popup.id] = Popup;
  Popup.events = {};
  Popup.createEl();
  Popup.setTitle(title);
  Popup.setContent(content);
};

/**
 * Destroys the popup.
 */
Popup.destroy = function() {
  var Popup = this;
  Popup.remove();
  $(Popup.el).remove();
  Popup.unbind();
  Popup.el = Popup.titleEl = Popup.contentEl = Popup.overlayEl = null;
  delete BUE.popups[Popup.id];
};

/**
 * Creates the popup element.
 */
Popup.createEl = function() {
  var el, closeEl, titleEl, contentEl, headEl, name, cname, type, id = this.id, tid = id + '-title', cid = id + '-content';
  el = this.el = BUE.createEl('<div class="bue-popup" role="dialog" tabindex="0"><div class="bue-popup-head"></div><div class="bue-popup-body"></div></div>');
  el.id = id;
  el.setAttribute('aria-labelledby', tid);
  el.setAttribute('aria-describedby', cid);
  el.onkeydown = BUE.ePopupKeydown;
  el.buePid = id;
  // Set additional classes
  if (type = this.type) {
    el.className += ' type--' + type;
  }
  if (name = this.name) {
    el.className += ' name--' + name;
  }
  if (cname = this.cname) {
    el.className += ' ' + cname;
  }
  // Head
  headEl = el.firstChild;
  headEl.onmousedown = BUE.ePopupHeadMousedown;
  if (this.noHeader) {
    headEl.style.display = 'none';
  }
  // Close
  closeEl = BUE.createEl('<a href="#" class="bue-popup-close" role="button"></a>');
  closeEl.onclick = BUE.ePopupCloseClick;
  closeEl.title = BUE.t('Close');
  headEl.appendChild(closeEl);
  // Title
  titleEl = this.titleEl = BUE.createEl('<div class="bue-popup-title"></div>');
  titleEl.id = tid;
  headEl.appendChild(titleEl);
  // Content
  contentEl = this.contentEl = BUE.createEl('<div class="bue-popup-content"></div>');
  contentEl.id = cid;
  el.children[1].appendChild(contentEl);
};

/**
 * Opens the popup.
 */
Popup.open = function(pos) {
  var E, form, Popup = this, el = Popup.el;
  if (!Popup.on) {
    // Add to DOM if not added yet
    if (!el.parentElement) {
      document.body.appendChild(el);
    };
    // Set z-index.(Also triggers repaint allowing css transitions work for the first time)
    el.style.zIndex = BUE.maxZ(el) + 2;
    // Set overlay
    if (Popup.withOverlay) {
      Popup.addOverlay();
    }
    // Open
    Popup.setState('on');
    Popup.setPosition(pos);
    // Auto focus
    if (Popup.autoFocus) {
      Popup.focus();
    }
    // Auto close
    if (Popup.autoClose) {
      Popup.setAutoClose();
    }
    // Trigger open handlers
    Popup.trigger('open');
  }
  return Popup;
};

/**
 * Closes the popup.
 */
Popup.close = function() {
  var Popup = this;
  if (Popup.on) {
    if (Popup.autoClose) {
      Popup.resetAutoClose();
    }
    Popup.unsetState('on');
    Popup.removeOverlay();
    Popup.restoreFocus();
    Popup.trigger('close');
  }
  return Popup;
};

/**
 * Sets popup title.
 */
Popup.setTitle = function(title) {
  if (title != null) $(this.titleEl).html(title);
};

/**
 * Sets popup content.
 */
Popup.setContent = function(content) {
  if (content != null) $(this.contentEl).html(content);
};

/**
 * Sets popup css.
 */
Popup.setCss = function(css) {
  $(this.el).css(css);
};

/**
 * Sets popup position.
 */
Popup.setPosition = function(pos) {
  // Set custom position if provided
  if (pos) {
    this.setCss(pos);
  }
  // Set initial position with respect to editor UI
  else if (!this.el.style.top) {
    if (this.Editor) {
      this.setCss(this.Editor.defaultPopupPosition(this));
    }
  }
};

/**
 * Sets focus on the first input or link element inside the popup content.
 */
Popup.focus = function() {
  var E, form = this.getForm(),
  // Get first form element or first link or popup element
  el = form && $(form.elements).filter(':visible')[0] || $('a', this.contentEl).filter(':visible')[0] || this.el;
  // Store the last active element before focusing.
  this.restoreFocusEl = document.activeElement;
  BUE.focusEl(el);
  // Prevent editor restoring the focus on button click
  if (E = this.Editor) {
    E.preventFocus = true;
  }
};

/**
 * Restores focus to previous state.
 */
Popup.restoreFocus = function() {
  var E, Popup, el;
  if (el = this.restoreFocusEl) {
    this.restoreFocusEl = null;
    Popup = BUE.popupOf(el);
    E = this.Editor;
    // Focus on editor if the previous element is not related to another open popup.
    if (E && !(Popup && Popup.on)) {
      E.focus();
    }
    else {
      BUE.focusEl(el);
    }
  }
};

/**
 * Sets auto closing on document mousedown.
 */
Popup.setAutoClose = function() {
  $(document).bind('mousedown.buepopupac' + this.no, {pid: this.id}, BUE.ePopupDocMousedown);
};

/**
 * Resets auto closing on document mousedown.
 */
Popup.resetAutoClose = function() {
  $(document).unbind('.buepopupac' + this.no);
};

/**
 * Returns the first form element inside the popup content.
 */
Popup.getForm = function() {
  return $('form', this.contentEl)[0];
};

/**
 * Adds document overlay under the popup.
 */
Popup.addOverlay = function() {
  var parent, el = this.el, overlayEl = this.overlayEl;
  if (!overlayEl) {
    overlayEl = this.overlayEl = BUE.createEl('<div class="bue-popup-overlay"></div>');
    overlayEl.onmousedown = BUE.ePopupOverlayMousedown;
  }
  if (parent = el.parentNode) {
    overlayEl.style.zIndex = (el.style.zIndex*1 || 1) - 1;
    parent.insertBefore(overlayEl, el);
  }
};

/**
 * Removes popup overlay.
 */
Popup.removeOverlay = function() {
  var overlayEl = this.overlayEl;
  if (overlayEl) {
    BUE.removeEl(overlayEl);
  }
};

/**
 * Removes popup from its editor.
 */
Popup.remove = function() {
  this.close();
  var E = this.Editor;
  if (E) E.removePopup(this);
};







/**
 * Keydown event of a popup.
 */
BUE.ePopupKeydown = function(event) {
  var e = event || window.event;
  // Close on Esc
  if (e.keyCode == 27) {
    BUE.popupOf(this).close();
    return false;
  }
};

/**
 * Click event of a popup close button.
 */
BUE.ePopupCloseClick = function(event) {
  BUE.popupOf(this).close();
  return false;
};

/**
 * Mousedown event of popup head.
 */
BUE.ePopupHeadMousedown = function(event) {
  var e = $.event.fix(event || window.event), el = BUE.popupOf(this).el, data = $(el).offset();
  data.el = el;
  data.X = e.pageX;
  data.Y = e.pageY; 
  $(document).bind('mousemove', data, BUE.ePopupHeadDrag).bind('mouseup', BUE.ePopupHeadDrop);
  return false;
};

/**
 * Drag event of popup head.
 */
BUE.ePopupHeadDrag = function(e) {
  var data = e.data;
  $(data.el).css({left: data.left + e.pageX - data.X, top: data.top + e.pageY - data.Y});
  return false;
};

/**
 * Drop event of popup head.
 */
BUE.ePopupHeadDrop = function(e) {
  $(document).unbind('mousemove', BUE.ePopupHeadDrag).unbind('mouseup', BUE.ePopupHeadDrop);
};

/**
 * Mousedown event of popup overlay.
 */
BUE.ePopupOverlayMousedown = function(event) {
  // Prevent stealing focus.
  return false;
};

/**
 * Mousedown event of document for autoclosing a popup.
 */
BUE.ePopupDocMousedown = function(e) {
  var Popup = BUE.getPopup(e.data.pid);
  // Close if the target is outside the popup.
  if (Popup && Popup !== BUE.popupOf(e.target)) {
    Popup.close();
  }
};

/**
 * Retrieves the popup object from an element.
 */
BUE.popupOf = function(el) {
  el = $(el).closest('.bue-popup')[0];
  return el ? BUE.getPopup(el.buePid) : false;
};

/**
 * Returns maximum z-index value among visible children of document body.
 */
BUE.maxZ = function(skipEl, parentEl) {
  var i, el, Z, maxZ = 0, els = (parentEl || document.body).children;
  for (i = 0; el = els[i]; i++) {
    if (el.offsetWidth && el !== skipEl) {
      Z = $(el).css('z-index') * 1;
      if (Z && Z > maxZ) {
        maxZ = Z
      }
    }
  }
  return maxZ;
};

})(jQuery, BUE);