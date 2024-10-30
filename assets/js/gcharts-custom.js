google.charts.load('current', { packages: ['corechart', 'bar'], callback: drawLinktChart });
// google.charts.load("current", { packages: ["corechart", "bar"] });
// google.charts.setOnLoadCallback(drawLinktChart);

function drawLinktChart() {
  const chartData = document.getElementById("chart_div");
  if (!chartData) return;

  const chartId = chartData.dataset.postid;
  const chartShow = chartData.dataset.graphshow;

  jQuery.ajax({
    type: "POST",
    url: ajax_object.ajax_url,
    data: {
      action: "linkt_get_post_clicks_meta_byid",
      post_id: chartId,
      graph_show: chartShow,
    },
    success: function (result) {
      const chartDiv = document.getElementById("chart_div");
      const resultArray = Object.entries(JSON.parse(result));
      resultArray.unshift([
        ajax_object.graphtext.date,
        ajax_object.graphtext.clicks,
      ]);

      var data = google.visualization.arrayToDataTable(resultArray);
      console.log(data);

      switch (chartShow) {
        case "linkt_7days":
          graphTitle = ajax_object.graphtext.setting_7days;
          break;
        case "linkt_2month":
          graphTitle = ajax_object.graphtext.setting_2month;
          break;
        default:
          graphTitle = ajax_object.graphtext.setting_currentmonth;
      }

      var options = { title: graphTitle };

      // Instantiate and draw the chart.
      var chart = new google.visualization.ColumnChart(chartDiv);
      chart.draw(data, options);
    },
    error: function () {
      // console.log( "No Posts retrieved" );
    },
  });
}
