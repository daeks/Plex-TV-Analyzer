<?php 	
include_once('tvclass.php');
if(!empty($_POST['show_id'])) {
	$show_id = intval($_POST['show_id']);
	$show_part = explode('@', $_POST['show']);
	$show_name = trim($show_part[0]);
	file_put_contents('cache/ignore/'.$show_id, $show_name);
}

        	
        	
?>