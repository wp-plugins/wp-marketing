(function() {
  this.WPMW || (this.WPMW = {});

  jQuery(document).ready(function($) {
    WPMW.findAndPush = function(ids) {
      var data;
      if (ids == null) {
        ids = false;
      }
      data = {
        action: "ctas_fetch_all",
        "public": true
      };
      return $.post(WPMW.ajaxurl, data, function(response) {
        var cta, ctas, _i, _len, _results;
        ctas = JSON.parse(response);
        _results = [];
        for (_i = 0, _len = ctas.length; _i < _len; _i++) {
          cta = ctas[_i];
          cta.data.loader = WPMW.loader;
          cta.data.id = cta.id;
          if (cta.data.style === "inline" && (typeof cta.data.container === "undefined" || cta.data.container === "")) {
            cta.data.container = ".wpm_container_" + cta.data.cache_key;
          }
          _results.push(CTA.push(["widget", cta.data]));
        }
        return _results;
      });
    };
    return WPMW.findAndPush();
  });

}).call(this);
