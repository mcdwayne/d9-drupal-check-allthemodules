(function ($) {
  Drupal.behaviors.accessibility_testswarm = {
    
    guideline : [],
    
    tests     : {},
    
    alreadyRun : false,
    
    failedItems : { },
    
    attach: function (context) {
    	if(this.alreadyRun) {
    		return;
    	}
    	this.alreadyRun = true;
    	var that = this;

    	that.guideline = [];
    	$.ajax({ url : Drupal.settings.basePath + 'js/accessibility/tests.json',
  				 					async : false,
  				 					dataType : 'json',
  				 					success : function(data) {
				  				 	  that.guideline = data.guideline;
                      that.tests = data.tests;
				  				  }
				  				 });
  		$('body').quail({ accessibilityTests : that.tests,
														guideline : that.guideline,
														jsonPath : Drupal.settings.basePath + Drupal.settings.accessibility_testswarm.quail_path + '/resources',
														complete : function(event) {
															$.each(event.results, function(testName, items) {
                                if(items.length) {
                                  for(var i = 0; i < items.length; i++) {
                                    if((items[i].attr('id') && items[i].attr('id').search('qunit-') === 0) || items[i].hasClass('-a11y-testswarm')) {
                                      items.splice(i, 1);
                                      i--;
                                    }
                                  }
                                }
                                test(that.tests[testName].readableName, function() {
													        expect(1);
													        if(items.length) {
													        	that.failedItems[testName] = items;
													        }
													        equal(0, items.length, testName);
													      });
															});
													}});
    }
    
  };
  
  Drupal.behaviors.testswarm.attach = function() {
    var currentTest;
    var mySettings = drupalSettings.testswarm;

    $.extend(QUnit.config, {
      reorder: false, // Never ever re-order tests!
      altertitle: false, // Don't change the title
      autostart: false
    });

    var logger = {log: {}, info: {}, tests: []};
    var currentModule = 'default';

    QUnit.moduleStart = function(module) {
      currentModule = module.name;
      if (!logger.log[currentModule]) {
        logger.log[currentModule] = {};
      }
    };

    QUnit.testStart = function(test) {
      currentTest = test.name;
    };

    QUnit.testDone = function(test) {
      logger.tests.push(test);
    };

    QUnit.done = function(data) {
      logger.info = data;
      logger.caller = mySettings.caller;
      logger.theme = mySettings.theme;
      logger.token = mySettings.token;
      logger.karma = mySettings.karma;
      logger.module = mySettings.module;
      logger.description = mySettings.description;
      $.each(logger.log.default, function(index, log) {
				$.each(log, function(logIndex, item) {
					if(item.result) {
						item.message = Drupal.t('No errors found');
					}
					else {
						item.accessibility_testswarm = [];
						$.each(Drupal.behaviors.accessibility_testswarm.failedItems[item.message], function(e, element) {
              var theme = (element.parents('.-a11y-testswarm').length)
                          ? Drupal.settings.accessibility_testswarm_theme[element.parents('.-a11y-testswarm').first().data('theme-key')]
                          : '<none>';
							item.accessibility_testswarm.push({ element : $('<div>').append(element.clone().empty()).html(),
                                                  theme : theme});
						});
					}
				});
			});
			// Write back to server
      var url = Drupal.url('testswarm-test-done');
      jQuery.ajax({
        type: "POST",
        url: url,
        timeout: 10000,
        data: logger,
        error: function(response) {
          window.alert(Drupal.t('Ajax error at @url', { '@url': url }));
        },
        success: function(){
          window.setTimeout(function() {
            if (!mySettings.debug || mySettings.debug !== 'on') {
              if (mySettings.destination) {
                window.location = mySettings.destination;
              }
              else {
                window.location = '/testswarm-browser-tests';
              }
            }
          }, 500);

        }
      });
    };
    QUnit.log = function(data) {
      if (!logger.log[currentModule]) {
        logger.log[currentModule] = {};
      }
      if (!logger.log[currentModule][currentTest]) {
        logger.log[currentModule][currentTest] = [];
      }
      logger.log[currentModule][currentTest].push(data);
    };
  };
})(jQuery);