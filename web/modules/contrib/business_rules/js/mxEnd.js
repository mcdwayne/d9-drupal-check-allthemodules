var graph = new Graph(document.getElementById('business_rules_workflow_graph'));
graph.resizeContainer = true;
graph.setEnabled(false);

function showFlowchart(data) {
  'use strict';

  var xmlDoc = mxUtils.parseXml(data);
  var codec = new mxCodec(xmlDoc);
  codec.decode(xmlDoc.documentElement, graph.getModel());
}

if (document.getElementById('graph_definition').value) {
  showFlowchart(document.getElementById('graph_definition').value);
}
