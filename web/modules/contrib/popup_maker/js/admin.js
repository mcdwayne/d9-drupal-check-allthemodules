(function () {
  'use strict';
  const SGPM_ACTIVE_CLASS = 'active';
  const SGPM_TAB_SELECTOR = '.sgpm-tab';
  const SGPM_TAB_ACTIVE_SELECTOR = '.sgpm-tab.' + SGPM_ACTIVE_CLASS;
  const SGPM_PANEL_CLASS = '.sgpm-panel';
  const SGPM_PANEL_ACTIVE_SELECTOR = '.sgpm-panel.' + SGPM_ACTIVE_CLASS;

  function SGPMAdmin() {

  }

  SGPMAdmin.prototype.init = function () {
    var tabsWrap = jQuery('.sgpm-tabs');
    if (tabsWrap.length) {
      var tabs = new SGPMTabs(tabsWrap);
      tabs.init();
    }

    var rulesWrap = jQuery('#sgpm-edit-popup-panel');

    if (rulesWrap.length) {
      var rules = new SGPMRules();
      rules.init();
    }
  };

  function SGPMRules() {

  }

  SGPMRules.prototype.init = function () {
    this.wrap = jQuery('#sgpm-edit-popup-panel');
    this.rulesList = jQuery('.sgpm-display-rules');
    jQuery('.sgpm-popup-rule-value').select2();

    this.rulesList.on('click', function (e) {
      var t = jQuery(e.target);
      if (t.hasClass('sgpm-remove-rule')) {
        this.removeRule(t);
      }
      else if (t.hasClass('sgpm-add-rule')) {
        this.addRule(t);
      }
      return false;
    }.bind(this));

    this.rulesList.on('change', function (e) {
      var t = jQuery(e.target);
      if (t.hasClass('sgpm-rule-param')) {
        this.onParamChange(t);
      }
    }.bind(this));
  };

  SGPMRules.prototype.removeRule = function (el) {
    if (jQuery('.sgpm-popup-rule').length === 1) {
      alert('You cannot remove the last rule');
      return false;
    }
    el.closest('.sgpm-popup-rule').remove();

    jQuery('.sgpm-popup-rule').last().find('.sgpm-add-rule').removeClass('sgpm-hide');
  };

  SGPMRules.prototype.addRule = function (el) {
    var html = jQuery('#display-rule-row').val();
    var rules = jQuery('.sgpm-popup-rule');
    var nextId = 1 + parseInt(rules.last().data('rule-id'));

    html = html.split('{index}').join(nextId.toString());

    jQuery('.sgpm-display-rules').append(html);

    jQuery('.sgpm-popup-rule .sgpm-add-rule').addClass('sgpm-hide');

    var newRule = jQuery('.sgpm-popup-rule').last();
    newRule.find('.sgpm-add-rule').removeClass('sgpm-hide');
    newRule.find('select').attr('name');
    jQuery('.sgpm-popup-rule-value').select2();
  };

  SGPMRules.prototype.onParamChange = function (el) {
    var val = el.find('option:selected').data('sgpm-rule-param');
    var rule = el.closest('.sgpm-popup-rule');
    var target = rule.find('div[data-sgpm-rule-param="' + val + '"]');

    rule
    .find('div[data-sgpm-rule-param]:not(.sgpm-hide)')
    .addClass('sgpm-hide')
    .find('select').each(function () {
      jQuery(this).data('name', jQuery(this).attr('name')).removeAttr('name');
    });

    if (!target.length) {
      return false;
    }

    if (target.hasClass('sgpm-hide')) {
      target.removeClass('sgpm-hide');
    }

    if (!target.is('[name]')) {
      target.find('select').attr('name', target.data('name'));
    }

    jQuery('.sgpm-popup-rule-value').select2();
  };

  function SGPMTabs(elem) {
    if (!elem || !elem.length) {
      throw new Error('tabs constructor requires wrapper element');
    }
    this.container = elem;
  }

  SGPMTabs.prototype.init = function () {

    this.tabs = this.container.find(SGPM_TAB_SELECTOR);
    this.panels = this.container.find(SGPM_PANEL_CLASS);

    if (!this.tabs.length || !this.panels.length) {
      throw new Error('trying to initialize empty tabs');
    }

    this.firstTick();
    this.attachEvents();
  };

  SGPMTabs.prototype.attachEvents = function () {
    this.tabs.on('click', SGPMTabs.onTabClick.bind(this));
  };

  SGPMTabs.onTabClick = function (e) {
    var t = jQuery(e.target);
    var index = t.index();

    /* Remove active classes */
    this.container.find(SGPM_TAB_ACTIVE_SELECTOR).removeClass(SGPM_ACTIVE_CLASS);
    this.container.find(SGPM_PANEL_ACTIVE_SELECTOR).removeClass(SGPM_ACTIVE_CLASS);

    /* Add active classes */
    this.tabs.eq(index).addClass(SGPM_ACTIVE_CLASS);
    this.panels.eq(index).addClass(SGPM_ACTIVE_CLASS);
  };

  SGPMTabs.prototype.firstTick = function () {
    if (this.container.find(SGPM_TAB_ACTIVE_SELECTOR).length && this.container.find(SGPM_PANEL_ACTIVE_SELECTOR).length) {
      return;
    }

    this.tabs.removeClass(SGPM_ACTIVE_CLASS);
    this.panels.removeClass(SGPM_ACTIVE_CLASS);

    this.tabs.eq(0).addClass(SGPM_ACTIVE_CLASS);
    this.panels.eq(0).addClass(SGPM_ACTIVE_CLASS);
  };

  var sgpmAdmin = new SGPMAdmin();

  jQuery(document).ready(sgpmAdmin.init());

})();
