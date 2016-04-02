/**
 * Created by minimus on 18.02.2015.
 */

if (!Array.prototype.find) {
  Array.prototype.find = function (predicate) {
    if (this == null) {
      throw new TypeError('Array.prototype.find called on null or undefined');
    }
    if (typeof predicate !== 'function') {
      throw new TypeError('predicate must be a function');
    }
    var list = Object(this);
    var length = list.length >>> 0;
    var thisArg = arguments[1];
    var value;

    for (var i = 0; i < length; i++) {
      value = list[i];
      if (predicate.call(thisArg, value, i, list)) {
        return value;
      }
    }
    return undefined;
  };
}

if (!Array.prototype.filter) {
  Array.prototype.filter = function(fun/*, thisArg*/) {
    'use strict';

    if (this === void 0 || this === null) {
      throw new TypeError();
    }

    var t = Object(this);
    var len = t.length >>> 0;
    if (typeof fun !== 'function') {
      throw new TypeError();
    }

    var res = [];
    var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
    for (var i = 0; i < len; i++) {
      if (i in t) {
        var val = t[i];

        // ПРИМЕЧАНИЕ: Технически, здесь должен быть Object.defineProperty на
        //             следующий индекс, поскольку push может зависеть от
        //             свойств на Object.prototype и Array.prototype.
        //             Но этот метод новый и коллизии должны быть редкими,
        //             так что используем более совместимую альтернативу.
        if (fun.call(thisArg, val, i, t)) {
          res.push(val);
        }
      }
    }

    return res;
  };
}


function Selectors() {
  var sid = null;
  var page = null;
  var pids = {};
  var ids = [];
  var service = false;

  Object.defineProperty(this, 'sid', {
    get: function () {
      var d = [];
      for (var val in pids) {
        d.push(pids[val].join(','));
      }
      if (ids.length > 0) d.push(ids.join(','));
      return d.join(',');
    },
    set: function (value) {
      sid = value;
      ids = (value == '') ? [] : value.split(',');
      page = null;
      pids = {};
      service = false;
    }
  });

  Object.defineProperty(this, 'ids', {
    get: function () {
      var i, d = ids.slice(0), v;
      for (var val in pids) {
        v = this.pids[val];
        if (v.length > 0) {
          for (i = 0; i < v.length; i++) d.push(v[i]);
        }
      }
      return d;
    }
  });

  Object.defineProperty(this, 'page', {
    get: function () {
      return page;
    },
    set: function (value) {
      page = value;
      if ('undefined' == typeof pids[page]) pids[page] = [];
    }
  });

  Object.defineProperty(this, 'pids', {
    get: function () {
      return pids;
    },
    set: function (value) {
      if (value == -1) pids[page] = [];
      else {
        if (-1 == pids[page].indexOf(value)) {
          pids[page].push(value);
          var index = ids.indexOf(value);
          if (index > -1) ids.splice(index, 1);
        }
      }
    }
  });

  Object.defineProperty(this, 'service', {
    get: function () {
      return service;
    },
    set: function (value) {
      service = value
    }
  });

  this.removePid = function (value) {
    var index = pids[page].indexOf(value);
    if (index > -1) pids[page].splice(index, 1);
  }
}