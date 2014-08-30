(function() {
  jQuery(document).ready(function($) {
    return $.post(WPMW.ajaxurl, {
      action: "ctas_fetch_all"
    }, function(response) {
      var cta, ctas, _i, _len, _results;
      ctas = JSON.parse(response);
      _results = [];
      for (_i = 0, _len = ctas.length; _i < _len; _i++) {
        cta = ctas[_i];
        cta.data.loader = WPMW.loader;
        cta.data.id = cta.id;
        _results.push(CTA.push(["widget", cta.data]));
      }
      return _results;
    });
  });

}).call(this);
