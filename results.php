<?php
  include_once('tvclass.php');
  
  if(!empty($_POST['data'])) {
    $data = explode('@', $_POST['data']);
    $show_id = intval($data[1]);
    $guide_id = intval($data[2]);
    $show_part = explode('@', $_POST['show']);
    $show_name = trim($show_part[0]);
    $minimal_season = intval($_POST['minimal']);
    $shows = TVAnalyzer::AnalyzeShow($show_name,$show_id,$guide_id,$minimal_season);
    if(!is_array($shows)) {
      echo $shows;
      exit();
    }
    foreach($shows as $show) {
      if (is_object($show)) {
        if (file_exists('cache/ignore/'.$show_id)) {
          $show->Status = 'Ignored';
        }
        
        $foldername = $show->ShowName.' ('.$show->Year.')';
        $foldername = mb_ereg_replace("([:])", ' -', $foldername);
        $foldername = mb_ereg_replace("([/\\*?\"<>|!])", '', $foldername);
        $folderstatus = '';
        if (isset(Config::$PlexFOLDER)) {
          if (array_key_exists($data[0], Config::$PlexFOLDER)) {
            if (!is_dir(Config::$PlexFOLDER[$data[0]].DIRECTORY_SEPARATOR.$foldername)) {
              $folderstatus = ' - <font color="red"><b>NO SYNC</b></red>';
            } else {
              $folderstatus = ' - <font color="green">SYNC OK</red>';
            }
          }
        }
        
        echo '<img src="'.TVAnalyzer::getMedia($show->Poster).'" style="position:absolute; width: 256px; height: 384px; margin-left: -270px"/>';
        echo '<tr><td colspan="3" class="show"><b><u>Show '.$_POST['option'].'</b></u> - ID: <b>'.$guide_id.'</b> - Status: <b><span id="status">'.$show->Status.' ('.$show->Cache.')</span></b><span style="float: right"><b><span id="folder">'.$foldername.'</span></b>'.$folderstatus.'</span> </td></tr>';
        if(is_array($show->Episodes)) {
          $season = $show->Episodes[0]->SeasonNumber;
          echo '<tr><td colspan="3" class="season">Season '.$season.'</td></tr>';
        }
        $total = 0;
        $local = 0;
        $newest = '';
        foreach($show->Episodes as $epi) {
          $total += 1;
          if($epi->SeasonNumber != $season) {
            $season = $epi->SeasonNumber;
            echo '<tr><td colspan="3" class="season">Season '.$season.'</td></tr>';
          }
          if(!$epi->Missing) {
            $local += 1;
            $newest = ' - S'.sprintf('%02d', intval($epi->SeasonNumber)).'E'.sprintf('%02d', intval($epi->EpisodeNumber));
          }
          if ($_POST['option'] == 'All') {
            echo '<tr'; 
            if($epi->Missing) echo ' style="background-color:#600000;"';
            echo '><td class="episode" style="white-space: nowrap;">Episode '.$epi->EpisodeNumber.'</td>';
            echo '<td style="white-space: nowrap;">'.$epi->EpisodeName.'</td>';
            echo '<td>';
            echo $epi->Missing ? 'Missing!' : 'Yes';
            echo '</td>';
            echo '</tr>';
          } else {
            if($epi->Missing) {
              echo '<tr style="background-color:#600000;">';
              echo '<td class="episode" style="white-space: nowrap;">Episode '.$epi->EpisodeNumber.'</td>';
              echo '<td style="white-space: nowrap;">'.$epi->EpisodeName.'</td>';
              echo '<td>';
              echo $epi->Missing ? 'Missing!' : 'Yes';
              echo '</td>';
              echo '</tr>';
            }
          }
        }
        
        if (file_exists('cache/'.$show_id)) { unlink('cache/'.$show_id); }
        if (file_exists('cache/temp/'.$show_id)) { unlink('cache/temp/'.$show_id); }
        if (file_exists('cache/finished/'.$show_id)) { unlink('cache/finished/'.$show_id); }
        if (file_exists('cache/ended/'.$show_id)) { unlink('cache/ended/'.$show_id); }
        if ($total == $local) {
          if ($show->Status == 'Ended') {
            if (!is_dir('cache/finished')) {
              mkdir('cache/finished', 0777, true);
            }
            file_put_contents('cache/finished/'.$show_id, '');
          } else {
            if (!is_dir('cache/temp')) {
              mkdir('cache/temp', 0777, true);
            }
            file_put_contents('cache/temp/'.$show_id, $local.'/'.$total.$newest);
          }
        } else {
          if ($show->Status == 'Ended') {
            if (!is_dir('cache/ended')) {
              mkdir('cache/ended', 0777, true);
            }
            file_put_contents('cache/ended/'.$show_id, $local.'/'.$total);
          } else {
            if (!is_dir('cache')) {
              mkdir('cache', 0777, true);
            }
            file_put_contents('cache/'.$show_id, $local.'/'.$total.$newest);
          }
        }
      } else {
      
      }
    }
  }

?>