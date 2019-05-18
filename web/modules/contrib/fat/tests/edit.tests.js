/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, _, Drupal, window, document, undefined) {
  "use strict";

  var entity;

  /**
   * Start in-place editing.
   */
  function delayedSetup () {
    var quickEditToggle = entity && document.querySelectorAll('.contextual .quick-edit a', entity)[0];
    (quickEditToggle && quickEditToggle.click());
  }
  /**
   * Cancel out of in-place editing.
   */
  function delayedTeardown () {
    var cancel = document.querySelectorAll('#edit-entity-toolbar .edit-toolbar-entity .action-cancel')[0];
    (cancel && cancel.click());
  }

  // Tests.
  Drupal.tests.edit = {
    getInfo: function() {
      return {
        name: 'Edit module tests',
        description: 'Tests the in-place editing capabilities of Edit module. This test should be run on the path /node/1.',
        group: 'Core'
      };
    },
    setup: function () {
      entity = document.querySelectorAll('[data-edit-entity]')[0];
    },
    teardown: function () {},
    tests: {
      inPlaceEditLaunchAndClose: function () {
        return function() {
          QUnit.expect(0);
          // Edit setup is a highly asynchronous process, so the tests need to
          // wait sufficient time to ensure the pieces are all in place.
          QUnit.asyncTest( "inPlaceEditLaunchAndClose asynchronous test", function () {
            QUnit.expect(2);
            // The setup takes a few moments to complete, but we must also wait
            // the entities to get processed and the contextual links to be
            // inserted.
            setTimeout(function() {
              // Set up the test.
              delayedSetup();
            }, 2000);
            // Run the enabing tests half a second after the setup.
            setTimeout(function () {
              // Assert that the in-place editing of the node is active.
              QUnit.ok(entity.classList.contains('edit-entity-active'), Drupal.t('The first edtiable entity on the page is active.'));
              // Teardown the test.
              delayedTeardown();
            }, 2500);
            // Run the disabled tests half a second after the teardown.
            setTimeout(function () {
              // Assert that the in-place editing of the node is no longer
              // active.
              QUnit.ok(!entity.classList.contains('edit-entity-active'), Drupal.t('The first edtiable entity on the page is no longer active.'));
              QUnit.start();
            }, 3000);
          });
        };
      },
      fieldBodyEditAndSave: function () {
        return function () {
          QUnit.expect(0);
          // Edit setup is a highly asynchronous process, so the tests need to
          // wait sufficient time to ensure the pieces are all in place.
          QUnit.asyncTest( "fieldBodyEditAndSave asynchronous test", function () {
            QUnit.expect(2);
            var newHTML = '<p>Why make book covers so nice if we are not meant to judge them? ' + (new Date()).toString() + '</p>';
            var serverHTML, editable, originalHTML;
            // First get the body contents of node/1 from the server
            $.ajax({
                url: Drupal.url(location.pathname.slice(1)),
                dataType: 'json'
              })
              .done(function (data) {
                serverHTML = data.nodes[location.pathname.slice(6,7)].body['#items'][0].value.trim();
                // Now that the baseline is established, start making changes.
                // The setup takes a few moments to complete, but we must also
                // wait for the entities to get processed and the contextual
                // links to be inserted.
                setTimeout(function() {
                  delayedSetup();
                }, 2000);
                // Run the enabing tests a second after the setup.
                setTimeout(function () {
                  // Make some assertions.
                  // Change the body field.
                  editable = document.querySelectorAll('.field-name-body.edit-editable', entity)[0];
                  editable.click();
                  // Get the original text of the field.
                  originalHTML = editable.innerHTML.trim();
                  // Assert that the exisiting body HTML is equal to the value
                  // stored on the server.
                  QUnit.ok(originalHTML === serverHTML, Drupal.t('The HTML in the body field is equivalent to the HTML stored on the server for this field.'));
                  // Change the text. This is gonna fail in some browsers for whom
                  // innerText is read-only. Works in Chrome as a start.
                  editable.innerHTML = newHTML;
                }, 2500);
                //
                setTimeout(function () {
                  // Simulate a keydown event.
                  var simulation = $(editable).simulate('keydown', {keyCode: '65'});
                }, 3000);
                setTimeout(function () {
                  // Save the changes.
                  var toolbar = document.getElementById('edit-entity-toolbar');
                  $('.action-save[aria-hidden="false"]', toolbar).trigger('click');
                }, 3500);
                // Get the title of the save entity from the server and compare it
                // to what is on the page.
                setTimeout(function () {
                  $.ajax({
                      url: Drupal.url(location.pathname.slice(1)),
                      dataType: 'json'
                    })
                    .done(function (data) {
                      serverHTML = data.nodes[location.pathname.slice(6,7)].body['#items'][0].value.trim();
                      QUnit.ok(newHTML === serverHTML, Drupal.t('The body field update was saved to the server.'));
                      // Teardown the test.
                      delayedTeardown();
                      QUnit.start();
                    });
                }, 8000);
              });
          });
        };
      },
      fieldTagsEditAndSave: function () {
        return function () {
          QUnit.expect(0);
          QUnit.asyncTest( "fieldTagsEditAndSave asynchronous test", function () {
            QUnit.expect(2);
            var testContext = this;
            var newTags = [(new Date()).getTime().toString(), ((new Date()).getTime() + 300000).toString()];
            var editable, originalTags;

            function runTestSteps (serverTags) {
              // Now that the baseline is established, start making changes.
              // The setup takes a few moments to complete, but we must also wait
              // the entities to get processed and the contextual links to be
              // inserted.
              setTimeout(function() {
                delayedSetup();
              }, 2000);
              // Run the enabing tests a second after the setup.
              setTimeout(function () {
                // Make some assertions.
                // Change the tags field.
                editable = document.querySelectorAll('.field-name-field-tags.edit-editable', entity)[0];
                editable.click();
              }, 2500);
              setTimeout(function () {
                originalTags = $(editable).find('.links li a').map(function (index, element) {
                  return element.innerText.trim();
                });
                // Assert that the exisiting body HTML is equal to the value
                // stored on the server.
                QUnit.ok(_.difference(originalTags, serverTags).length === 0, Drupal.t('The list of tags in the client is equivalent to the list of tags stored on the server for this field.'));
                // Change the text. This is gonna fail in some browsers for whom
                // innerText is read-only. Works in Chrome as a start.
                $(editable).prev().find('.field-name-field-tags').find('[name^="field_tags"]').val(newTags.join(', '));
              }, 5000);
              //
              setTimeout(function () {
                // Simulate a keydown event.
                var simulation = $(editable).prev().find('.field-name-field-tags').find('[name^="field_tags"]').simulate('keyup', {keyCode: '65'});
              }, 5500);
              setTimeout(function () {
                // Save the changes.
                var toolbar = document.getElementById('edit-entity-toolbar');
                $('.action-save[aria-hidden="false"]', toolbar).trigger('click');
              }, 6000);
              // Get the tags of the saved entity from the server and compare
              // them to what is on the page.
              setTimeout(function () {
                getTagInfo(function (serverTags) {
                  QUnit.ok(_.difference(newTags, serverTags).length === 0, Drupal.t('The tag field update was saved to the server.'));
                  // Teardown the test.
                  delayedTeardown();
                  QUnit.start();
                });
              }, 12000);
            }
            // First get the tag contents of node/1 from the server
            function getTagInfo (callback) {
              var that = this;
              var callsOutstanding;
              var tags = [];
              $.ajax({
                  url: Drupal.url(location.pathname.slice(1)),
                  dataType: 'json'
                })
                .done(function (data) {
                  // Then get the entities for the tags associated with this node.
                  var tagEntityRefs = data.nodes[location.pathname.slice(6,7)].field_tags['#items'];
                  // When callsOutstanding equals zero, we can start the tests.
                  callsOutstanding = tagEntityRefs.length;
                  // Get the data for each tag.
                  for (var i = 0, il = tagEntityRefs.length; i < il; i++) {
                    $.ajax({
                      url: '/entity/taxonomy_term/' + tagEntityRefs[i].tid,
                      contentType: 'application/hal+json',
                      dataType: 'json'
                    })
                    .success(function (data) {
                      callsOutstanding--;
                      // Store the entity info.
                      tags.push(data.name[0].value.trim());
                      // When all the ajax calls have returned, invoke the
                      // callback, if it is defined.
                      if (callsOutstanding === 0) {
                        callsOutstanding = null;
                        (callback && callback.call(testContext, tags));
                      }
                    });
                  }
                });
            }

            // Start by getting the tag info. Once all the tags are loaded, the
            // testing will start.
            getTagInfo(runTestSteps);
          });
        };
      }
    }
  };
}(jQuery, _, Drupal, this, this.document));
