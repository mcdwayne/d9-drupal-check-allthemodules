(function($, BUE) {
'use strict';

/**
 * @file
 * Defines form building methods.
 */

/**
 * Creates a managed form element.
 */
BUE.createForm = function(fields, opt) {
  var form, formObj = BUE.formHtmlObj(fields);
  if (!opt) opt = {};
  BUE.extendAttr(formObj.attributes, opt.attributes);
  form = BUE.buildCreateEl(formObj);
  form.onsubmit = BUE.eFormSubmit;
  $(form).data('options', opt);
  return form;
};

/**
 * Builds a form html object from a list of fields.
 */
BUE.formHtmlObj = function(fields) {
  var i, field, current, fieldHtml, hidden = '', action = '', rows = [];
  for (i = 0; field = fields[i]; i++) {
    fieldHtml = BUE.fieldHtml(field);
    // Group submit buttons
    if (field.isAction) {
      action += fieldHtml;
    }
    // Group hidden fields
    else if (field.type === 'hidden') {
      hidden += fieldHtml;
    }
    else {
      current = field;
      while (current.getnext) {
        if (current = fields[++i]) fieldHtml += BUE.fieldHtml(current);
        else break;
      }
      rows.push(BUE.fieldRowHtml(field, fieldHtml));
    }
  }
  if (action) action = '<div class="bue-form-actions">' + action + '</div>';
  rows = BUE.fieldRowsHtml(rows);
  return {tag: 'form', html: rows + action + hidden, attributes: {'class': 'bue-form'}};
};

/**
 * Submit event of bue form.
 */
BUE.eFormSubmit = function() {
  // Prevent default submission in all cases
  try {
    var i, el, opt, validate, submit, Popup, E, form = this;
    // Check required fields.
    for (i = 0; el = form.elements[i]; i++) {
      if (el.getAttribute('required')) {
        // Set the error and return
        if (!el.value) {
          BUE.setFieldError(el);
          BUE.focusEl(el);
          return false;
        }
        // Unset any previous errors
        BUE.unsetFieldError(el);
      }
    }
    // Run handlers
    if (opt = $(form).data('options')) {
      // Check if the form is a part of an editor popup.
      if (Popup = BUE.popupOf(form)) {
        E = Popup.Editor;
      }
      // Validate
      if (validate = opt.validate) {
        if (!validate.call(opt, form, Popup, E)) {
          return false;
        }
      }
      // Submit
      if (submit = opt.submit) {
        submit.call(opt, form, Popup, E);
      }
    }
  }
  catch (e) {
    BUE.delayError(e);
  }
  return false;
};

/**
 * Creates a dialog form.
 */
BUE.createDialogForm = function(fields, opt) {
  opt = BUE.extend({addButtons: true, submitClose: true}, opt);
  if (opt.addButtons) {
    fields.push(BUE.getSubmitField(opt.stitle));
    fields.push(BUE.getCancelField(opt.ctitle));
  }
  opt.dialogSubmit = opt.submit;
  opt.submit = BUE.submitDialogForm;
  return BUE.createForm(fields, opt);
};

/**
 * Submits a dialog form.
 */
BUE.submitDialogForm = function(form, Popup, E) {
  var opt = this;
  // Close the popup before calling submit handler.
  if (opt.submitClose) {
    Popup.close();
  }
  return opt.dialogSubmit.call(opt, form, Popup, E);
};

/**
 * Creates a tag form.
 */
BUE.createTagForm = function(tag, fields, opt) {
  // Prepare options
  opt = BUE.extend({tag: tag}, opt);
  opt.attributes = BUE.extendAttr({'class': 'bue-tag-form', 'data-tag': tag}, opt.attributes);
  opt.tagSubmit = opt.submit;
  opt.submit = BUE.submitTagForm;
  // Prepare fields
  fields = $.map(fields, BUE.processTagField);
  return BUE.createDialogForm(fields, opt);
};

/**
 * Submits a tag form.
 */
BUE.submitTagForm = function(form, Popup, E) {
  var submit, htmlObj = BUE.tagFormToHtmlObj(form);
  // Custom submit
  if (submit = this.tagSubmit) {
    return submit.call(this, htmlObj, Popup, E);
  }
  // Default submit
  E.insertHtmlObj(htmlObj);
};

/**
 * Builds and returns html object derived from field values in a tag form.
 */
BUE.tagFormToHtmlObj = function(form) {
  var i, el, value, name, htmlObj = {tag: form.getAttribute('data-tag'), attributes: {}};
  for (i = 0; el = form.elements[i]; i++) {
    if (name = el.getAttribute('data-attr-name')) {
      if (el.type !== 'checkbox' || el.checked) {
        value = el.value || el.getAttribute('data-empty-value');
        if (name === 'html') {
          htmlObj.html = value || '';
        }
        else {
          htmlObj.attributes[name] = value;
        }
      }
    }
  }
  return htmlObj;
};

/**
 * Processes a tag editor field.
 */
BUE.processTagField = function(field) {
  field = BUE.processField(field);
  // Add attribute name
  if (field.attributes['data-attr-name'] === undefined) {
    field.attributes['data-attr-name'] = field.name;
  }
  return field;
};

/**
 * Processes a form field.
 */
BUE.processField = function(field) {
  if (!field.processed) {
    var attr, type = field.type;
    // Set type
    if (!type) {
      type = field.type = 'text';
    }
    // Set attributes
    attr = {name: field.name, id: 'bue-field-' + (++BUE.counter), 'class': 'bue-field form-' + type};
    if (field.required) {
      attr.required = 'required';
      attr['class'] += ' required';
    }
    if (field.empty != null) {
      attr['data-empty-value'] = field.empty;
    }
    if (type === 'submit' || type === 'button') {
      attr['class'] += ' button';
      // Set as primary button
      if (field.primary) {
        attr['class'] += ' button--primary';
      }
      // Set as action button
      if (field.isAction === undefined) {
        field.isAction = true;
      }
    }
    field.attributes = BUE.extendAttr(attr, field.attributes);
    field.processed = true;
  }
  return field;
};

/**
 * Builds html of a form field.
 */
BUE.fieldHtml = function(field) {
  if (!field.processed) {
    field = BUE.processField(field);
  }
  var i, options, optAttr, tag = field.type, innerHTML = '', attributes = field.attributes;
  switch (tag) {
    // Select
    case 'select': 
      if (options = field.options) {
        for (i in options) {
          optAttr = {value: i};
          if (i == field.value) optAttr.selected = 'selected';
          innerHTML += BUE.html('option', options[i], optAttr);
        }
      }
      break;
    // Textarea
    case 'textarea':
      innerHTML = field.value;
      break;
    // Input
    default:
      attributes = BUE.extend({type: tag, value: field.value}, attributes);
      tag = 'input';
      break;
  }
  return (field.prefix || '') + BUE.html(tag, innerHTML, attributes) + (field.suffix || '');
};

/**
 * Builds row html of form field.
 */
BUE.fieldRowHtml = function(field, fieldHtml) {
  var attr = {'class': 'bue-field-row ' + field.type + '-row'};
  if (field.required) {
    attr['class'] += ' required-row';
  }
  if (field.title != null) {
    fieldHtml = BUE.html('label', field.title, {'for': field.attributes.id}) + fieldHtml;
  }
  return BUE.html('div', fieldHtml, attr);
};

/**
 * Builds wrapper html of tag editor field rows.
 */
BUE.fieldRowsHtml = function(rows) {
  return BUE.html('div', rows.join(''), {'class': 'bue-field-rows'});
};

/**
 * Sets field error.
 */
BUE.setFieldError = function(el) {
  $(el).addClass('error').parent().addClass('error-parent');
};

/**
 * Unsets field error.
 */
BUE.unsetFieldError = function(el) {
  $(el).removeClass('error').parent().removeClass('error-parent');
};

/**
 * Returns the default submit button.
 */
BUE.getSubmitField = function(title) {
  return {name: 'op', type: 'submit', value: title || BUE.t('OK'), primary: true};
};

/**
 * Returns the deafult cancel button.
 */
BUE.getCancelField = function(title) {
  return {name: 'cancel', type: 'button', value: title || BUE.t('Cancel'), attributes: {onclick: 'BUE.popupOf(this).close()'}};
};

/**
 * Returns browse button for an input.
 */
BUE.browseButton = function(browserName, inputName, browseType, buttonLabel) {
  return BUE.html('button', buttonLabel || BUE.t('Browse'), {
    type: 'button',
    'class': 'button bue-browse-button',
    onclick: 'return BUE.eBrowseButtonClick.apply(this, arguments);',
    'data-browser-name': browserName,
    'data-input-name': inputName,
    'data-browse-type': browseType
  });
};

/**
 * Default click handler of browse button.
 */
BUE.eBrowseButtonClick = function(event) {
  var el = this, form = el.form,
  inputEl = form.elements[el.getAttribute('data-input-name')],
  browseType = el.getAttribute('data-browse-type'),
  browser = BUE.fileBrowsers[el.getAttribute('data-browser-name')];
  if (inputEl && browser && browser.call) {
    browser.call(browser, inputEl, browseType, BUE.popupOf(form).Editor);
  }
  return false;
};


})(jQuery, BUE);