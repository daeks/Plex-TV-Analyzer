<?php
  if (!file_exists('config.php')) {
    die('config.php is missing - please rename config.example.php and modify its values first.');
  }
?>
<!DOCTYPE html>
<html>
<head>
<title>Missing Television Episodes</title>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="js/jquery.treeTable.min.js"></script>
<link rel="stylesheet" href="css/tv.css" type="text/css" />
<script type="text/javascript">

  function SubmitForm(option) {
    var show_id = $('#showSelector').val();
    var show = $('#showSelector :selected').text();
    $("#resultpart").html('<tr><td /><td><img src="img/ajax-loader.gif" alt="loading..." /></td><td /></tr>');
    
    $.post('results.php', { show_id: show_id, show: show, option: option},
      function(data){
        $('#resultpart').html(data);
        var status = $('#status').html();
        if (status == 'Ended') {
          $('#ignoreshow').removeAttr('disabled');
        } else {
          $('#ignoreshow').attr('disabled','disabled');
        }
      }
    );
    return false;
  }

  function IgnoreForm(option) {
    if (confirm("Do you really want to ignore this show?")) {
      var show_id = $('#showSelector').val();
      var show = $('#showSelector :selected').text();
      $.post('ignore.php', { show_id: show_id, show: show, option: option},
        function(data){
          $('#ignoreshow').attr('disabled','disabled');
        }
      );
    }
    return false;
  }
 
</script>
</head>
<body style="background-image:url(./img/background.jpg);background-repeat:repeat-x;z-index:0">
<?php
  include_once('tvclass.php');
  
  echo '<br><br><br>';
  echo '<div id="filepath"><div style="float:left;padding-right:10px;padding-bottom:12px;">Show: </div><div style="float:left;"><select id="showSelector" onchange="SubmitForm(\'Missing\');">';
  foreach(TVAnalyzer::GetUserShows() as $key => $value) {
    echo '<option value="'.$value.'">'.$key.'</option>';
  }
      
  echo '</select>';
  echo '</div><div style="float:left; nowrap;">';
  echo '&nbsp;<input value="Show Missing" type="button" name="getshows" id="getshows" onClick="SubmitForm(\'Missing\'); return false;" />';
  echo '&nbsp;|&nbsp;<input value="Show All" type="button" name="getshows" id="getshows" onClick="SubmitForm(\'All\'); return false;" />';
  echo '&nbsp;|&nbsp;<input value="Ignore Show" type="button" name="ignoreshow" disabled id="ignoreshow" onClick="IgnoreForm(\'All\'); return false;" />';
  echo '</div>';
?>
<table cellspacing="0" id="tvtable"> 
  <thead>
    <tr> 
      <th>Name</th> <th>Episode Name</th><th>Available</th> 
    </tr> 
  </thead>
  <tbody id="resultpart"> </tbody> 
</table>
</body>
</html>