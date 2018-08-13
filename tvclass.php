<?php
  include_once('config.php');
  
  class TVAnalyzer {
  
    private static $TVDBSearchURL = 'http://www.thetvdb.com/api/GetSeries.php?seriesname=';
    private static $TVDBLookURL = 'http://www.thetvdb.com/api/3B54A58ACFAF62FA/series/%s/all/%s.xml';
    private static $TVDBCacheSeconds = 3600;
    
    public static function AnalyzeShow($show_name, $show_id, $minimal_season = 1) {
      if (!is_int($show_id)) {
        return 'null';
      } else {
        $refshows = array();
        $show = TVAnalyzer::GetUserShowEpisodes($show_name,$show_id);
        $refshows[] = TVAnalyzer::GetTVDBShowEpisodes($show, $minimal_season);
        
        return $refshows;
      }
    }

    public static function GetUserShows() {
      $shows = array();
      $sectionUrl = Config::$PlexURL.'/library/sections';
      $xml = simplexml_load_string(TVAnalyzer::GetUrlSource($sectionUrl.'?X-Plex-Token='.Config::$PlexTOKEN));
      foreach ($xml->Directory as $sec) {
        if ((string) $sec['type'] == 'show' && !in_array($sec['title'], Config::$PlexIGNORE)) {
          $secXML = simplexml_load_string(TVAnalyzer::GetUrlSource($sectionUrl.'/'.$sec['key'].'/all?X-Plex-Token='.Config::$PlexTOKEN));
          foreach ($secXML->Directory as $sho) {
            $showme = true;
            if (!isset($_GET['mode'])) {
              $showme = !file_exists('cache/finished/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey']));
            } else {
              switch ($_GET['mode']) {
                case 'all':
                  break;
                case 'finished':
                  $showme = file_exists('cache/finished/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey']));
                  break;
                case 'ignored':
                  $showme = file_exists('cache/ignore/'.strval($sho['ratingKey']));
                  break;
                case 'continuing':
                  $showme = file_exists('cache/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey'])) || file_exists('cache/temp/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey']));
                  break;
                case 'incomplete':
                  $showme = file_exists('cache/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey'])) || file_exists('cache/ended/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey']));
                  break;
                case 'completed':
                  $showme = file_exists('cache/finished/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey'])) || file_exists('cache/temp/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey']));
                  break;
                default:
                  $showme = !file_exists('cache/finished/'.strval($sho['ratingKey'])) && !file_exists('cache/ignore/'.strval($sho['ratingKey']));
              }
            }
            if ($showme) {
              $status = 'Continuing & Complete';
              $amount = '';
              $sortkey = sprintf('%02d', intval($sec['key'])).'@'.$sec['title'].'@3';;
              if (!file_exists('cache/temp/'.strval($sho['ratingKey']))) {
                $status ='New Show (NO SYNC)';
                $sortkey = sprintf('%02d', intval($sec['key'])).'@'.$sec['title'].'@0';
              }
              if (file_exists('cache/ended/'.strval($sho['ratingKey']))) {
                $status ='Ended & Incomplete';
                $sortkey = sprintf('%02d', intval($sec['key'])).'@'.$sec['title'].'@2';
                $amount = file_get_contents('cache/ended/'.strval($sho['ratingKey']));
              }
              if (file_exists('cache/'.strval($sho['ratingKey']))) {
                $status = 'Continuing & Incomplete';
                $sortkey = sprintf('%02d', intval($sec['key'])).'@'.$sec['title'].'@1';
                $amount = file_get_contents('cache/'.strval($sho['ratingKey']));
              }
              if (file_exists('cache/ignore/'.strval($sho['ratingKey']))) {
                $status ='Ignored';
                $sortkey = sprintf('%02d', intval($sec['key'])).'@'.$sec['title'].'@5';
              }
              if (file_exists('cache/finished/'.strval($sho['ratingKey']))) {
                $status = 'Finished (Ended & Complete)';
                $sortkey = sprintf('%02d', intval($sec['key'])).'@'.$sec['title'].'@4';
                $amount = file_get_contents('cache/finished/'.strval($sho['ratingKey']));
              }
              $title = (strval($sho['titleSort']) != '' ? strval($sho['titleSort']) : strval($sho['title']));
              $shows[strval($sho['ratingKey'])] = array('show_id' => strval($sho['ratingKey']),
                                                'title' => $title,
                                                'status' => $status,
                                                'amount' => $amount,
                                                'section' => $sec['title'],
                                                'sortkey' => $sortkey.'@'.$title
                                                );
            }
          }
        }
      }
      
      usort($shows, function($a,$b){ return strcmp(strtoupper($a["sortkey"]), strtoupper($b["sortkey"]));} );
      return $shows;
    }

    private static function GetUserShowEpisodes($show_name, $show_id) {
      $show = new Show();
      $show->ShowName = $show_name;
      $show->Episodes = array();
      $showXML = simplexml_load_string(TVAnalyzer::GetUrlSource(Config::$PlexURL.'/library/metadata/'.$show_id.'/allLeaves?X-Plex-Token='.Config::$PlexTOKEN, false));
      foreach ($showXML->Video as $epi) {
        $newepisode = new Episode();
        $newepisode->EpisodeNumber = intval($epi['index']);
        $newepisode->SeasonNumber = intval($epi['parentIndex']);
        if (!TVAnalyzer::ContainsEpisode($newepisode, $show)) {
          $show->Episodes[] = $newepisode;
        }
      }
      return $show;
    }

    private static function GetTVDBShowEpisodes($original_show, $minimal_season = 1) {
      $fixed_name = urlencode($original_show->ShowName);
      $show_url = TVAnalyzer::$TVDBSearchURL.$fixed_name.'&language='.Config::$PlexLANGUAGE;
      $xml = simplexml_load_string(TVAnalyzer::GetUrlSource($show_url));

      $series_id = (string) $xml->Series[0]->seriesid;
      $lookup_url = sprintf(TVAnalyzer::$TVDBLookURL, $series_id, Config::$PlexLANGUAGE);
      $xml = simplexml_load_string(TVAnalyzer::GetUrlSource($lookup_url));

      $show = new Show();
      $show->ShowName = strval($xml->Series->SeriesName);
      $show->TVDBId = intval($xml->Series->id);
      $show->Status = strval($xml->Series->Status);

      $original_show->TVDBId = $show->TVDBId;

      $show->Episodes = array();
      foreach ($xml->Episode as $episode) {
        if (intval($episode->SeasonNumber) < $minimal_season) {
          continue;
        } else {
          $newepisode = new Episode();
          $newepisode->EpisodeName = strval($episode->EpisodeName);
          $newepisode->EpisodeNumber = intval($episode->EpisodeNumber);
          $newepisode->SeasonNumber = intval($episode->SeasonNumber);
          if (!TVAnalyzer::ContainsEpisode($newepisode, $show)) {
            $newepisode->Missing = !TVAnalyzer::ContainsEpisode($newepisode, $original_show);
            $show->Episodes[] = $newepisode;
          }
        }
      }
      return $show;
    }

    private static function ContainsEpisode($episode, $show) {
      foreach ($show->Episodes as $ep) {
        if ($ep->EpisodeNumber === $episode->EpisodeNumber && $ep->SeasonNumber === $episode->SeasonNumber) {
          return true;
        }
      }
      return false;
    }

    private static function GetUrlSource($url, $cache = true) {
      $response = '';
      
      if (!is_dir('cache/tvdb')) {
          mkdir('cache/tvdb', 0777, true);
        }
      if (file_exists('cache/tvdb/'.md5($url)) && $cache) {
        if (time() - filemtime('cache/tvdb/'.md5($url)) > TVAnalyzer::$TVDBCacheSeconds) {
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_HEADER, false);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
          curl_setopt($session, CURLOPT_MAXREDIRS, 3);
          $response = curl_exec($session);
          curl_close($session);
          file_put_contents('cache/tvdb/'.md5($url), $response);
        } else {
          $response = file_get_contents('cache/tvdb/'.md5($url));
        }
      } else {
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($session, CURLOPT_MAXREDIRS, 3);
        $response = curl_exec($session);
        curl_close($session);
        if ($cache) {
          file_put_contents('cache/tvdb/'.md5($url), $response);
        }
      }
      return $response;
    }

  }
  
  class Episode {
    public $EpisodeNumber;
    public $SeasonNumber;
    public $EpisodeName;
    public $Missing;
    public $ShowName;
  }
  
  class Show {
    public $ShowName;
    public $TVDBId;
    public $Episodes;
  }

?>