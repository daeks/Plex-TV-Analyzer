<?php
  if (!file_exists('config.php')) {
    die('config.php is missing - please rename config.example.php and modify its values first.');
  }
  include_once('tvclass.php');
?>
<!DOCTYPE html>
<html>
<head>
<title>Missing Television Episodes</title>
<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter-2.30.7.min.js"></script>
<script type="text/javascript" src="js/jquery.treetable-3.2.0.min.js"></script>
<script type="text/javascript" src="js/select2-4.0.5.min.js"></script>
<script type="text/javascript" src="js/maximize-select2-height.min.js"></script>
<link rel="stylesheet" href="css/tv.css" type="text/css" />
<link rel="stylesheet" href="css/select2-4.0.5.min.css" type="text/css" />
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
 
  $(document).ready(function() {
    $('.basic-single').select2({ dropdownAutoWidth : true, dropdownCssClass: "select" }).maximizeSelect2Height();
    
    $("body").on('keyup', ".select2,.select2-dropdown", function (e) {
      var KEYS = { UP: 38, DOWN: 40 };
      var $sel2 = $(this).closest(".select2");
      if ($sel2.length == 0) {
        $sel2 = $(".select2.select2-container--open");
      }

      var $sel = $sel2.data("element")
      if ($sel.length) {
        var newValue

        if (e.keyCode === KEYS.DOWN && !e.altKey) {
          newValue = $sel.find('option:selected').nextAll(":enabled").first().val();
        } else if (e.keyCode === KEYS.UP) {
          newValue = $sel.find('option:selected').prevAll(":enabled").first().val();
        }

        if (newValue != undefined) {
          $sel.val(newValue);
          $sel.trigger('change');
        }
      }
    });
    
  });
</script>
</head>
<body style="background-image:url(./img/background.jpg);background-repeat:repeat-x;z-index:0">
<?php 
  echo '<br><br><br>';
  echo '<div id="filepath"><div style="float:left;padding-right:0px;padding-bottom:12px;"></div><div style="float:left;"><select class="basic-single" id="showSelector" onchange="SubmitForm(\'Missing\');">';
  $section = '';
  foreach(TVAnalyzer::GetUserShows() as $key => $value) {
    if ($section != $value['section']) {
      if ($secion != '') {
        echo '</optgroup>';
      }
      echo '<optgroup label="'.$value['section'].'">';
      $section = $value['section'];
    }
    echo '<option value="'.$key.'">'.$value['title'];
    if ($value['amount'] != '') {
      echo ' @ '.$value['amount'].$value['status'];
    }
    echo '</option>';
  }
      
  echo '</select>';
  echo '</div><div style="float:left; nowrap;">';
  echo '&nbsp;<input class="button" value="Show Missing" type="button" name="getshows" id="getshows" onClick="SubmitForm(\'Missing\'); return false;" />';
  echo '&nbsp;|&nbsp;<input class="button" value="Show All" type="button" name="getshows" id="getshows" onClick="SubmitForm(\'All\'); return false;" />';
  echo '&nbsp;|&nbsp;<input class="button" value="Ignore Show" type="button" name="ignoreshow" disabled id="ignoreshow" onClick="IgnoreForm(\'All\'); return false;" />';
  echo '</div><br><br>';
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