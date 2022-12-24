function testConnection() {
  let url = "/?action=database-test";
  $.get(url, (rs) => {
      $("#ajax-response").html(rs);
      $("#ajax-response").show('slow');
  });
}

function getTables() {
  let url = "/?action=show-tables";
  $.get(url, (rs) => {
      $("#ajax-response2").html(rs);
      $("#ajax-response2").show('slow');
  });
}

