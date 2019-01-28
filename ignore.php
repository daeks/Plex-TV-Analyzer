<?php
  include_once('tvclass.php');
  
  if(!empty($_POST['data'])) {
    $data = explode('@', $_POST['show_id']);
    $show_id = intval($data[1]);
    if ($_POST['status'] == 'Ignored') {
      if (file_exists('cache/ignore/'.$show_id)) {
        unlink('cache/ignore/'.$show_id);
      }
    } else {
      $show_part = explode('@', $_POST['show']);
      $show_name = trim($show_part[0]);
      file_put_contents('cache/ignore/'.$show_id, $show_name);
    }
  }
?>