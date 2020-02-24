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
<script type="text/javascript" src="js/tv.js"></script>
<link rel="stylesheet" href="css/tv.css" type="text/css" />
<link rel="stylesheet" href="css/select2-4.0.5.min.css" type="text/css" />
<script type="text/javascript">

  function SubmitForm(option, minimal) {
    $('#getmissing').removeAttr('disabled');
    $('#getshows').removeAttr('disabled');
    $('#getspecial').removeAttr('disabled');
    var data = $('#showSelector').val();
    var show = $('#showSelector :selected').text();
    $("#resultpart").html('<tr><td /><td><img src="img/ajax-loader.gif" alt="loading..." /></td><td /></tr>');
    
    $.post('results.php', { data: data, show: show, option: option, minimal: minimal},
      function(data){
        $('#resultpart').html(data);
        var status = $('#status').html();
        if (status == 'Ended') {
          $('#ignoreshow').val('Ignore');
          $('#ignoreshow').removeAttr('disabled');
        } else {
          if (status == 'Ignored') {
            $('#ignoreshow').removeAttr('disabled');
            $('#ignoreshow').val('Unignore');
          } else {
            $('#ignoreshow').val('Ignore');
            $('#ignoreshow').attr('disabled','disabled');
          }
        }
      }
    );
    return false;
  }

  function IgnoreForm() {
    if (confirm("Do you really want to toggle this show?")) {
      var data = $('#showSelector').val();
      var show = $('#showSelector :selected').text();
      var status = $('#status').html();
      $.post('ignore.php', { data: data, show: show, status: status },
        function(data){
          $('#ignoreshow').attr('disabled','disabled');
        }
      );
    }
    return false;
  }
 
  $(document).ready(function() {
    $('.basic-single').select2({ placeholder: "Select a show", allowClear: true, dropdownAutoWidth : true, dropdownCssClass: "select" }).maximizeSelect2Height();
    
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
<button onclick="scrolltop()" id="topbtn">Top</button>
<?php 
  echo '<br><br><br>';
  echo '<div id="filepath"><div style="float:left;padding-right:0px;padding-bottom:12px;"></div><div style="float:left;"><select class="basic-single" id="showSelector" onchange="SubmitForm(\'Missing\', 1);"><option></option>';
  $section = '';
  $status = '';
  foreach(TVAnalyzer::GetUserShows() as $key => $value) {
    if ($section != $value['section']) {
      if ($section != '') {
        echo '</optgroup>';
      }
      echo '<optgroup label="'.$value['section'].'">';
      $section = $value['section'];
      $status = '';
    }
    if ($status != '') {
      if ($status != $value['status']) {
        echo '<option disabled>-- '.$value['status'].' ';
        for ($i = 0;$i<(118-strlen($value['status'])-4);$i++) {
          echo '-';
        }
        echo '</option>';
        $status = $value['status'];
      }
    } else {
      echo '<option disabled>-- '.$value['status'].' ';
      for ($i = 0;$i<(118-strlen($value['status'])-4);$i++) {
        echo '-';
      }
      echo '</option>';
      $status = $value['status'];
    }
    echo '<option value="'.$value['section'].'@'.$value['show_id'].'@'.$value['guide_id'].'">'.$value['title'];
    if ($value['amount'] != '') {
      echo ' @ '.$value['amount'].' ('.substr($value['status'], 0, 1).')';
    }
    echo '</option>';
  }
      
  echo '</select>';
  echo '</div><div style="float:right; nowrap;">';
  echo '&nbsp;<input disabled class="button" value="Show Missing" type="button" name="getmissing" id="getmissing" onClick="SubmitForm(\'Missing\', 1); return false;" />';
  echo '&nbsp;<input disabled class="button" value="Show All" type="button" name="getshows" id="getshows" onClick="SubmitForm(\'All\', 1); return false;" />';
  echo '&nbsp;|&nbsp;<input disabled class="button" value="Special" type="button" name="getspecial" id="getspecial" onClick="SubmitForm(\'Special\', 0); return false;" />';
  echo '&nbsp;<input disabled class="button" value="Ignore" type="button" name="ignoreshow" id="ignoreshow" onClick="IgnoreForm(); return false;" />';
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
<div style="float:right; nowrap; font-size: 12px;">
  <br>Display Mode: <a href="./">Default</a> | <a href="?mode=all">All</a> | <a href="?mode=continuing">Continuing</a> | <a href="?mode=incomplete">Incomplete</a> | <a href="?mode=completed">Complete</a> | <a href="?mode=finished">Finished</a> | <a href="?mode=ignored">Ignored</a>
</div>
</body>
</html>