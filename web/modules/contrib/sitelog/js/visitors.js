/**
 * @file
 * Visitors choropleth.
 */

(function (Drupal, d3, drupalSettings) {
  Drupal.behaviors.sitelogVisitors = {
    attach: function (context, settings) {

      // define choropleth dimensions
      const svg = d3.select("svg");
      const width = +svg.attr("width");
      const height = +svg.attr("height");

      // define path
      const path = d3.geoPath();

      // define iso2 country codes
      const iso2 = ["AD", "AE", "AF", "AG", "AI", "AL", "AM", "AO", "AQ", "AR", "AS", "AT", "AU", "AW", "AX", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BL", "BM", "BN", "BO", "BQ", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA", "CC", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CU", "CV", "CW", "CX", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG", "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM", "FO", "FR", "GA", "GB", "GD", "GE", "GF", "GG", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT", "GU", "GW", "GY", "HK", "HM", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IM", "IN", "IO", "IQ", "IR", "IS", "IT", "JE", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KP", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA", "MC", "MD", "ME", "MF", "MG", "MH", "MK", "ML", "MM", "MN", "MO", "MP", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC", "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW", "PY", "QA", "RE", "RO", "RS", "RU", "RW", "SA", "SB", "SC", "SD", "SE", "SG", "SH", "SI", "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "SS", "ST", "SV", "SX", "SY", "SZ", "TC", "TD", "TF", "TG", "TH", "TJ", "TK", "TL", "TM", "TN", "TO", "TR", "TT", "TV", "TW", "TZ", "UA", "UG", "UM", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VI", "VN", "VU", "WF", "WS", "YE", "YT", "ZA", "ZM", "ZW"];

      // json parse data
      let data = JSON.parse(drupalSettings.sitelog.visitors.data);

      // add no data countries
      for (i = 0; i < iso2.length; i++) {
        if(!data.hasOwnProperty(iso2[i])) {
          data[iso2[i]] = 0;
        }
      }

      // min / max / step
      const values = Object.keys(data).map(key => +data[key]);
      const min = d3.min(values);
      const max = d3.max(values);
      const step = max / 10;

      // define scale
      const x = d3.scaleLinear()
        .domain([min, max])
        .rangeRound([0, 300]);

      // define color scheme
      const color = d3.scaleThreshold()
        .domain(d3.range(min + step, max, step))
        .range(d3.schemeBlues[9]);

      // append legend
      const g = svg.append("g")
        .attr("class", "sitelog-keys")
        .attr("transform", "translate(10, 328.815)");

      // append keys
      g.selectAll("rect")
        .data(color.range().map(d => {
          d = color.invertExtent(d);
          if (d[0] == null) d[0] = x.domain()[0];
          if (d[1] == null) d[1] = x.domain()[1];
          return d;
        }))
        .enter()
        .append("rect")
        .attr("height", 8)
        .attr("x", d => {
          return x(d[0]);
        })
        .attr("width", d => {
          return x(d[1]) - x(d[0]);
        })
        .attr("fill", d => {
          return color(d[0]);
        });
      g.append("text")
        .attr("class", "sitelog-keys-caption")
        .attr("x", x.range()[0])
        .attr("y", -10)
        .text("Visitors");
      g.call(d3.axisBottom(x)
        .tickSize(13)
        .tickFormat(x => {
          return x < 10 ? d3.format("d")(x) : d3.format(".2s")(x);
        })
        .tickValues(color.domain()))
        .select(".domain")
        .remove();

      // evaluate deferred tasks
      d3.queue()
        .defer(d3.json, drupalSettings.sitelog.visitors.geometry)
        .await(ready);

      // append choropleth
      function ready(error, world) {
        if (error) throw error;
        svg.append("g")
          .selectAll("path")
          .data(topojson.feature(world, world.objects.countries).features)
          .enter()
          .append("path")
          .attr("class", "sitelog-country")
          .attr("fill", d => {
            return data[d.id] ? color(d.visitors = data[d.id]) : "#f5f5f5";
          })
          .attr("d", path)
          .append("title")
          .text(d => {
            return d.visitors;
          });
      }

    }
  };
})(Drupal, d3, drupalSettings);
