// This is a slightly adjusted version of the header-command plugin found at
// https://github.com/guardian/scribe-plugin-heading-command

"use strict";

var scribeHeadingPlugin = function(level, commandName) {

  return function (scribe) {
    var tag = '<h' + level + '>';
    var nodeName = 'H' + level;
    if (!commandName) {
      commandName = 'h' + level;
    }

    /**
     * Chrome: the `heading` command doesn't work. Supported by Firefox only.
     */

    var headingCommand = new scribe.api.Command('formatBlock');

    headingCommand.execute = function () {
      if (this.queryState()) {
        scribe.api.Command.prototype.execute.call(this, '<p>');
      } else {
        scribe.api.Command.prototype.execute.call(this, tag);
      }
    };

    headingCommand.queryState = function () {
      var selection = new scribe.api.Selection();
      return !! selection.getContaining(function (node) {
        return node.nodeName === nodeName;
      });
    };

    /**
     * All: Executing a heading command inside a list element corrupts the markup.
     * Disabling for now.
     */
    headingCommand.queryEnabled = function () {
      var selection = new scribe.api.Selection();
      var listNode = selection.getContaining(function (node) {
        return node.nodeName === 'OL' || node.nodeName === 'UL';
      });

      return scribe.api.Command.prototype.queryEnabled.apply(this, arguments)
        && scribe.allowsBlockElements() && ! listNode;
    };

    scribe.commands[commandName] = headingCommand;
  };
};
