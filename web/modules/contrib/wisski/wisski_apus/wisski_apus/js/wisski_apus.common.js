


WissKI = WissKI || {};
WissKI.apus = WissKI.apus || {};
WissKI.apus.jQuery = WissKI.apus.jQuery || jQuery;

WissKI.apus.Annotation = function(id, type) {
  
  var _bodies = [];
  var _targets = [];
  
  // provenance, responsibility, certainty,... todo
  var _metadata = {};

  var _jq = WissKI.apus.jQuery;
  
  // we currently have
  // 'content': the annotation encodes the content of the target, e.g. referred entities, stated relation/triples
  // 'note': the annotation makes statements about the target, e.g. textual notes, or similar-to relations
  this.type = type;
  this.id = id;


  function getBodies() {
    return this._bodies;
  }

  function addBody(body) {
    if (body == null || typeof body != 'object') return false;
    for (i in this._bodies) {
      if (this._bodies[i] == body) return false;
    }
    this._bodies.push(body);
    _jq(this).trigger('addBody', body);
    return body;
  }

  function removeBody(body) {
    if (body == null || typeof body != 'object') return false;
    for (i in this._bodies) {
      if (this._bodies[i] == body) 
      this._bodies.splice(i,1);
      _jq(this).trigger('removeBody', body);
    }
    return body;
  }

  function addTarget(target) {
    if (target == null || typeof target != 'object') return false;
    for (i in this._targets) {
      if (this._targets[i] == target) return false;
    }
    this._targets.push(target);
    _jq(this).trigger('addTarget', target);
    return target;
  }

  function removeTarget(target) {
    if (target == null || typeof target != 'object') return false;
    for (i in this._targets) {
      if (this._targets[i] == target) 
      this._targets.splice(i,1);
      _jq(this).trigger('removeTarget', target);
    }
    return target;
  }

  function showDialog(args) {
    var target = args.target;


    

    
  }

};



WissKI.apus.Body = function(type, data) {
  
  this.type = type;
  this.data = data;

}



WissKI.apus.Target = function(type, container) {
  
  this.type = type;
  this.container = container;

}









  

  



