<?php
  include_once('tvclass.php');
  
  if(!empty($_POST['show_id'])) {
    $show_id = intval($_POST['show_id']);
    $show_part = explode('@', $_POST['show']);
    $show_name = trim($show_part[0]);
    $shows = TVAnalyzer::AnalyzeShow($show_name,$show_id);
    if(!is_array($shows)) {
      echo $shows;
      exit();
    }
    foreach($shows as $show) {
      echo '<tr><td colspan="3" class="show">Show '.$_POST['option'].' - '.$show->ShowName.' ('.$show_id.') - Status: <span id="status">'.$show->Status.'</span></td> </tr>';
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
      
      unlink('cache/'.$show_id);
      unlink('cache/temp/'.$show_id);
      unlink('cache/finished/'.$show_id);
      unlink('cache/ended/'.$show_id);
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
    }
  }

?>