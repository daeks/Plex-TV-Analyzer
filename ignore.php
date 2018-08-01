<?php
  include_once('tvclass.php');
  
  if(!empty($_POST['show_id'])) {
    if ($_POST['status'] == 'Ignored') {
      $show_id = intval($_POST['show_id']);
      if (file_exists('cache/ignore/'.$show_id)) {
        unlink('cache/ignore/'.$show_id);
      }
    } else {
      $show_id = intval($_POST['show_id']);
      $show_part = explode('@', $_POST['show']);
      $show_name = trim($show_part[0]);
      file_put_contents('cache/ignore/'.$show_id, $show_name);
    }
  }
?>