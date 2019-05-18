(function($, Chart) {
  $(document).ready(() => {
    $('canvas[id^="mychart-"]').each((index, mychart) => {
      const ctx = mychart.getContext("2d");
      const type = $(mychart).data("type");
      const data = $(mychart).data("data");
      const options = $(mychart).data("options");

      const myChart = new Chart(ctx, {
        type,
        data,
        options
      });
    });
  });
})(jQuery, Chart);
