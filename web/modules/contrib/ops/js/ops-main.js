/**
 * @file
 * File contains functions used to search and highlight text.
 */

(function ($) {
  'use strict';

  function inner_highlight(node, pat) {
    var i;
    var skip;
    var pos;
    var middlebit;
    var middleclone;
    var spannode;
    skip = 0;
    if (node.nodeType === 3) {
      pos = node.data.toUpperCase().indexOf(pat);
      if (pos >= 0) {
        spannode = document.createElement('span');
        spannode.className = 'ops-highlight';
        middlebit = node.splitText(pos);
        middlebit.splitText(pat.length);
        middleclone = middlebit.cloneNode(true);
        spannode.appendChild(middleclone);
        middlebit.parentNode.replaceChild(spannode, middlebit);
        skip = 1;
      }
    }
    else if (node.nodeType === 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
      for (i = 0; i < node.childNodes.length; ++i) {
        i += inner_highlight(node.childNodes[i], pat);
      }
    }
    return skip;
  }

  function new_normalize(node) {
    var i;
    var child;
    var combined_text;
    var children;
    var nodeCount;
    var new_node;
    for (i = 0, children = node.childNodes, nodeCount = children.length; i < nodeCount; i++) {
      child = children[i];
      if (child.nodeType === 1) {
        new_normalize(child);
        continue;
      }
      if (child.nodeType !== 3) {
        continue;
      }
      var next = child.nextSibling;
      if (next === null || next.nodeType !== 3) {
        continue;
      }
      combined_text = child.nodeValue + next.nodeValue;
      new_node = node.ownerDocument.createTextNode(combined_text);
      node.insertBefore(new_node, child);
      node.removeChild(child);
      node.removeChild(next);
      i--;
      nodeCount--;
    }
  }

  jQuery.fn.highlight = function (pat) {
    return this.each(function () {
      inner_highlight(this, pat.toUpperCase());
    });
  };

  jQuery.fn.remove_highlight = function () {
    return this.find('span.ops-highlight').each(function () {
      var thisParent = this.parentNode;
      thisParent.replaceChild(this.firstChild, this);
      new_normalize(thisParent);
    }).end();
  };
})();
