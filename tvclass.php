<?php
  include_once('config.php');
  
  class TVAnalyzer {
  
    private static $TVDBSearchURL = 'http://www.thetvdb.com/api/GetSeries.php?seriesname=';
    private static $TVDBLookURL = 'http://www.thetvdb.com/api/3B54A58ACFAF62FA/series/%s/all/%s.xml';
    private static $TVDBMediaURL = 'https://artworks.thetvdb.com/banners/';
    private static $TVDBCacheSeconds = 60 * 60 * 24 * 30 * 1;
    
    public static function AnalyzeShow($show_name, $show_id, $guide_id, $minimal_season = 1) {
      if (!is_int($show_id)) {
        return 'null';
      } else {
        $refshows = array();
        $show = TVAnalyzer::GetUserShowEpisodes($show_name,$show_id);
        $refshows[] = TVAnalyzer::GetTVDBShowEpisodes($show, $show_id, $guide_id, $minimal_season);
        
        return $refshows;
      }
    }
    
    public static function getMedia($media_id) {
      return TVAnalyzer::$TVDBMediaURL.$media_id;
    }

    public static function GetUserShows() {
      $shows = array();
      $sectionUrl = Config::$PlexURL.'/library/sections';
      $lookup = TVAnalyzer::GetUrlSource($sectionUrl.'?X-Plex-Token='.Config::$PlexTOKEN, false);
      $xml = simplexml_load_string($lookup['data']);
      foreach ($xml->Directory as $sec) {
        if ((string) $sec['type'] == 'show' && !in_array($sec['title'], Config::$PlexIGNORE)) {
          $lookup = TVAnalyzer::GetUrlSource($sectionUrl.'/'.$sec['key'].'/all?X-Plex-Token='.Config::$PlexTOKEN, false);
          $secXML = simplexml_load_string($lookup['data']);
          foreach ($secXML->Directory as $sho) {
            preg_match('/com\.plexapp\.agents\.([a-z]*)\:\/\/([0-9]*)\?lang\=([a-z]*)/', strval($sho['guid']), $matches);
            $guide_id = $matches[2];
            if ($matches[1] == 'none') {
              $guide_id = -1;
            }
            
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
                                                'guide_id' => $guide_id,
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
      $lookup = TVAnalyzer::GetUrlSource(Config::$PlexURL.'/library/metadata/'.$show_id.'/allLeaves?X-Plex-Token='.Config::$PlexTOKEN, false);
      $showXML = simplexml_load_string($lookup['data']);
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

    private static function GetTVDBShowEpisodes($original_show, $show_id, $guide_id, $minimal_season = 1) {
      if ($guide_id == null || $guide_id <= 0) {
        $fixed_name = urlencode($original_show->ShowName);
        $show_url = TVAnalyzer::$TVDBSearchURL.$fixed_name.'&language='.Config::$PlexLANGUAGE;
        $lookup = TVAnalyzer::GetUrlSource($show_url);
        
        $lookup['data'] = str_replace('<Overview>', '<Overview><![CDATA[', $lookup['data']);
        $lookup['data'] = str_replace('</Overview>', ']]></Overview>', $lookup['data']);
        $xml = simplexml_load_string($lookup['data']);
        if (is_object($xml->Series[0])) {
          $guide_id = (string) $xml->Series[0]->seriesid;
        } else {
          $guide_id = -1;
        }
      }
      
      if ($guide_id > 0) {
        $lookup_url = sprintf(TVAnalyzer::$TVDBLookURL, $guide_id, Config::$PlexLANGUAGE);
        $lookup = TVAnalyzer::GetUrlSource($lookup_url);
        $xml = simplexml_load_string($lookup['data']);

        $show = new Show();
        if ($lookup['cache'] > 0) {
          $show->Cache = date ("Y-m-d H:i:s", $lookup['cache']);
        } else {
          $show->Cache = 'LIVE';
        }
        $show->ShowName = strval($xml->Series->SeriesName);
        $show->TVDBId = intval($xml->Series->id);
        $show->IMDBId = intval($xml->Series->IMDB_ID);
        $show->Status = strval($xml->Series->Status);
        $show->Poster = strval($xml->Series->poster);
        $show->Year = substr(strval($xml->Series->FirstAired), 0, 4);

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
            
            if ($show->Year == '') {
              $show->Year = substr(strval($episode->FirstAired), 0, 4);
            }
            
            if (!TVAnalyzer::ContainsEpisode($newepisode, $show)) {
              $newepisode->Missing = !TVAnalyzer::ContainsEpisode($newepisode, $original_show);
              $show->Episodes[] = $newepisode;
            }
          }
        }
        return $show;
      } else {
        if (file_exists('cache/temp/'.strval($show_id))) {
          unlink('cache/temp/'.strval($show_id));
        }
        return null;
      }
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
      $response = array();
      
      if ($cache) {
        if (!is_dir('cache/www')) {
          mkdir('cache/www', 0777, true);
        } else {
          TVAnalyzer::CleanUpCache();
        }
      }
      if (file_exists('cache/www/'.md5($url)) && $cache) {
        if (time() - filemtime('cache/www/'.md5($url)) > TVAnalyzer::$TVDBCacheSeconds) {
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_HEADER, false);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
          curl_setopt($session, CURLOPT_MAXREDIRS, 3);
          $response['data'] = curl_exec($session);
          curl_close($session);
          file_put_contents('cache/www/'.md5($url), $response['data']);
        } else {
          $response['data'] = file_get_contents('cache/www/'.md5($url));
        }
      } else {
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($session, CURLOPT_MAXREDIRS, 3);
        $response['data'] = curl_exec($session);
        curl_close($session);
        if ($cache) {
          file_put_contents('cache/www/'.md5($url), $response['data']);
        }
      }
      if (file_exists('cache/www/'.md5($url))) {
        $response['cache'] = filemtime('cache/www/'.md5($url));
      } else {
        $response['cache'] = -1;
      }
      return $response;
    }
    
    private static function CleanUpCache() {
      if (is_dir('cache/www')) {
        foreach (scandir('cache/www') as $file){
          if ($file != '.' && $file != '..') {
            if (time() - filemtime('cache/www/'.$file) > TVAnalyzer::$TVDBCacheSeconds + 5) {
              unlink('cache/www/'.$file);
            }
          }
        }
      }
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