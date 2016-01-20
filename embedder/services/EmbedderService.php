<?php
namespace Craft;

class EmbedderService extends BaseApplicationComponent
{
    public function embed($video_url, $params, $output="simple")
    {
        $cache_name = 'embedder_urls';
        $refresh_cache = 10080;         // in mintues (default is 1 week)
        $cache_expired = FALSE;

        $plugin_vars = array(
            "title"         =>  "video_title",
            "html"          =>  "embed_code",
            "author_name"   =>  "video_author",
            "author_url"    =>  "video_author_url",
            "thumbnail_url" =>  "video_thumbnail",
            "medres_url"    =>  "video_mediumres",
            "highres_url"   =>  "video_highres",
            "description"   =>  "video_description"
        );

        $video_data = array();

        foreach ($plugin_vars as $var) {
            $video_data[$var] = false;
        }

        // set the parameters
        $max_width = (isset($params['max_width'])) ? "&maxwidth=" . $params['max_width'] : "";
        $max_height = (isset($params['max_height'])) ? "&maxheight=" . $params['max_height'] : "";
        $wmode = (isset($params['wmode'])) ? $params['wmode'] : "";
        $wmode_param = (isset($params['wmode'])) ? "&wmode=" . $params['wmode'] : "";

        // correct for a bug in YouTube response if only maxheight is set and the video is over 612px wide
        if (empty($max_height)) $max_height = "&maxheight=" . $max_width;

        // cache can be disabled by setting 0 as the cache_minutes param
        if (isset($params['cache_minutes']) && $params['cache_minutes'] !== FALSE && is_numeric($params['cache_minutes'])) {
            $this->refresh_cache = $params['cache_minutes'];
        }

        // optional YouTube parameters
        $youtube_rel = (isset($params['youtube_rel'])) ? $params['youtube_rel'] : null;
        $youtube_showinfo = (isset($params['youtube_showinfo'])) ? $params['youtube_showinfo'] : null;

        // optional Vimeo parameters
        $vimeo_byline   = (isset($params['vimeo_byline']) && $params['vimeo_byline'] == "false") ? "&byline=false" : "";
        $vimeo_title    = (isset($params['vimeo_title']) && $params['vimeo_title'] == "false") ? "&title=false" : "";
        $vimeo_autoplay = (isset($params['vimeo_autoplay']) && $params['vimeo_autoplay'] == "true") ? "&autoplay=true" : "";
        $vimeo_portrait = (isset($params['vimeo_portrait']) && $params['vimeo_portrait'] == "false") ? "&portrait=false" : "";
        $vimeo_api      = (isset($params['vimeo_api']) && $params['vimeo_api'] == "true") ? "&api=1" : "";
      
        $vimeo_color = (isset($params['vimeo_color'])) ? "&color=" . $params['vimeo_color'] : "";
        $vimeo_player_id = (isset($params['vimeo_player_id'])) ? $params['vimeo_player_id'] : "";
        $vimeo_player_id_str = (isset($params['vimeo_player_id'])) ? "&player_id=" . $params['vimeo_player_id'] : "";

        // optional Viddler parameters
        $viddler_type = (isset($params['viddler_type'])) ? "&type=" . $params['viddler_type'] : "";
        $viddler_ratio = (isset($params['viddler_ratio'])) ? "&ratio=" . $params['viddler_ratio'] : "";

        // optional Wistia parameters
        $wistia_type = (isset($params['wistia_type'])) ? "&embedType=" . $params['wistia_type'] : "";
        $wistia_foam = (isset($params['wistia_foam']) && $params['wistia_foam'] == "true") ? "&videoFoam=true" : "";

        // automatically handle scheme if https
        $is_https = false;
        if (isset($params['force_https']) && $params['force_https'] == "true" || parse_url($video_url, PHP_URL_SCHEME) == 'https') {
            $is_https = true;
        }

        // uf it's not YouTube, Vimeo, Wistia, or Viddler bail
        if (strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE) {
            $url = "http://www.youtube.com/oembed?format=xml&iframe=1" . ($is_https ? '&scheme=https' : '') . "&url=";
        } else if (strpos($video_url, "vimeo.com/") !== FALSE) {
            $url = "http" . ($is_https ? 's' : '') . "://vimeo.com/api/oembed.xml?url=";
        } else if (strpos($video_url, "wistia.com/") !== FALSE) {
            $url = "http://app.wistia.com/embed/oembed.xml?url=";
        } else if (strpos($video_url, "viddler.com/") !== FALSE) {
            $url = "http://www.viddler.com/oembed/?format=xml&url=";
        } else {
            return $video_data;
        }

        $url .= urlencode($video_url) . $max_width . $max_height . $wmode_param . $vimeo_byline . $vimeo_title . $vimeo_autoplay . $vimeo_portrait . $vimeo_api . $vimeo_player_id_str . $vimeo_color . $viddler_type . $viddler_ratio . $wistia_type . $wistia_foam;

        // checking if url has been cached
        $cached_url = craft()->fileCache->get($url);

        if (! $refresh_cache OR $cache_expired OR ! $cached_url)
        {
            // create the info and header variables
            list($video_info, $video_header) = $this->curl($url);

            // write the data to cache if caching hasn't been disabled
            if ($refresh_cache) {
                craft()->fileCache->set($url, $video_info, $refresh_cache);
            }
        }
        else
        {
            $video_info = $cached_url;
        }

        // decode the cURL data
        libxml_use_internal_errors(true);

        $video_info = simplexml_load_string($video_info);
        
        // gracefully fail if the video is not found
        if($video_info === false)
        {
            return "Video not found";
        }
            
        // inject wmode transparent if required
        if ($wmode === 'transparent' || $wmode === 'opaque' || $wmode === 'window' ) {
            $param_str = '<param name="wmode" value="' . $wmode .'"></param>';
            $embed_str = ' wmode="' . $wmode .'" ';

            // determine whether we are dealing with iframe or embed and handle accordingly
            if (strpos($video_info->html, "<iframe") === false) {
                $param_pos = strpos( $video_info->html, "<embed" );
                $video_info->html = substr($video_info->html, 0, $param_pos) . $param_str . substr($video_info->html, $param_pos);
                $param_pos = strpos( $video_info->html, "<embed" ) + 6;
                $video_info->html =  substr($video_info->html, 0, $param_pos) . $embed_str . substr($video_info->html, $param_pos);
            }
            else
            {
                // determine whether to add question mark to query string
                preg_match('/<iframe.*?src="(.*?)".*?<\/iframe>/i', $video_info->html, $matches);
                $append_query_marker = (strpos($matches[1], '?') !== false ? '' : '?');

                $video_info->html = preg_replace('/<iframe(.*?)src="(.*?)"(.*?)<\/iframe>/i', '<iframe$1src="$2' . $append_query_marker . '&wmode=' . $wmode . '"$3</iframe>', $video_info->html);
            }
        }

        // inject YouTube rel value if required
        if (!is_null($youtube_rel) && (strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE))
        {
            preg_match('/.*?src="(.*?)".*?/', $video_info->html, $matches);
            if (!empty($matches[1])) $video_info->html = str_replace($matches[1], $matches[1] . '&rel=' . $youtube_rel, $video_info->html);
        }

        // inject YouTube show info if required
        if (!is_null($youtube_showinfo) && (strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE))
        {
            preg_match('/.*?src="(.*?)".*?/', $video_info->html, $matches);
            if (!empty($matches[1])) $video_info->html = str_replace($matches[1], $matches[1] . '&showinfo=' . $youtube_showinfo, $video_info->html);
        }
      
        // add vimeo player id to iframe if set
        if ($vimeo_player_id!=="") {
            $video_info->html = preg_replace('/<iframe/i', '<iframe id="' . $vimeo_player_id . '"', $video_info->html);
        }

        // set the encode html to output properly in Twig
        $charset = craft()->templates->getTwig()->getCharset();
        $twig_html = new \Twig_Markup($video_info->html, $charset);
        //$video_info->html = $twig_html;

        // actually setting thumbnails at a reasonably consistent size, as well as getting higher-res images
        if(strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE) {
            $video_info->highres_url = str_replace('hqdefault','maxresdefault',$video_info->thumbnail_url);
            $video_info->medres_url = $video_info->thumbnail_url;
            $video_info->thumbnail_url = str_replace('hqdefault','mqdefault',$video_info->thumbnail_url);
            }
        else if (strpos($video_url, "vimeo.com/") !== FALSE) {
            $video_info->highres_url = preg_replace('/_(.*?)\./','_1280.',$video_info->thumbnail_url);
            $video_info->medres_url = preg_replace('/_(.*?)\./','_640.',$video_info->thumbnail_url);
            $video_info->thumbnail_url = preg_replace('/_(.*?)\./','_295.',$video_info->thumbnail_url);
            }
        else if (strpos($video_url, "wistia.com/") !== FALSE)
            {
            $video_info->highres_url = str_replace('?image_crop_resized=100x60','',$video_info->thumbnail_url);
            $video_info->medres_url = str_replace('?image_crop_resized=100x60','?image_crop_resized=640x400',$video_info->thumbnail_url);
            $video_info->thumbnail_url = str_replace('?image_crop_resized=100x60','?image_crop_resized=240x135',$video_info->thumbnail_url);
            }
        else if (strpos($video_url, "viddler.com/") !== FALSE)
            {
            $video_info->highres_url = $video_info->thumbnail_url;
            $video_info->medres_url = $video_info->thumbnail_url;
            $video_info->thumbnail_url = str_replace('thumbnail_2','thumbnail_1',$video_info->thumbnail_url);
            }
        
        // handle a simple output
        if ($output == "simple")
        {
            return $twig_html;
        }

        // handle full output   
        foreach ($plugin_vars as $key => $var)
        {
            if (isset($video_info->$key))
            {
                $video_data[$var] = $video_info->$key;
            }
        }

        $tagdata = $video_data;
        foreach ($video_data as $key => $value)
        {
            $tagdata = str_replace("{".$key."}", $value, $tagdata);
        }

        // replace the embed code with the Twig object
        $tagdata['embed_code'] = $twig_html;
        
        return $tagdata;

    }   

    public function curl($vid_url) {
        // do we have curl?
        if (function_exists('curl_init'))
        {
            $curl = curl_init();

            // cURL options
            $options = array(
                CURLOPT_URL =>  $vid_url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 10,
            );

            curl_setopt_array($curl, $options);

            $video_info = curl_exec($curl);
            $video_header = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // close the request
            curl_close($curl);

        }
        // do we have fopen?
        elseif (ini_get('allow_url_fopen') === TRUE)
        {
            $video_header = ($video_info = file_get_contents($vid_url)) ? '200' : TRUE;
        }
        else
        {
            $video_header = $video_info = FALSE;
        }

        return array($video_info, $video_header);
    }
}