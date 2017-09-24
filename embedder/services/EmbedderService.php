<?php

namespace Craft;

class EmbedderService extends BaseApplicationComponent
{
    /**
     * Creates the video embed code.
     *
     * @param string $video_url The video's URL.
     * @param array $params The video parameters.
     * @param string $output
     * @return array|mixed|\Twig_Markup
     */
    public function embed($video_url, $params = [], $output = "simple")
    {
        //is this a YouTube URL?
        $isYouTube  = strpos($video_url, 'youtube.com/') !== false || strpos($video_url, 'youtu.be/') !== false;
        $isVimeo    = strpos($video_url, 'vimeo.com/') !== false;
        $isWistia   = strpos($video_url, 'wistia.com/') !== false;
        $isViddler  = strpos($video_url, 'viddler.com/') !== false;

        $cache_refresh_minutes = 10080; // in minutes (default is 1 week)
        $is_cache_expired = false;

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
        foreach ($plugin_vars as $var)
        {
            $video_data[$var] = false;
        }

        // automatically handle scheme if https
        $is_https = false;
        if (isset($params['force_https']) && $params['force_https'] || parse_url($video_url, PHP_URL_SCHEME) == 'https')
        {
            $is_https = true;
        }

        // if it's not YouTube, Vimeo, Wistia, or Viddler bail
        if ($isYouTube)
        {
            $url = "http://www.youtube.com/oembed?format=xml&iframe=1" . ($is_https ? '&scheme=https' : '') . "&url=";
        }
        else if ($isVimeo)
        {
            $url = "http" . ($is_https ? 's' : '') . "://vimeo.com/api/oembed.xml?url=";
        }
        else if ($isWistia)
        {
            $url = "http://app.wistia.com/embed/oembed.xml?url=";
        }
        else if ($isViddler)
        {
            $url = "http://www.viddler.com/oembed/?format=xml&url=";
        }
        else
        {
            return $output == "simple" ? '' : $video_data;
        }
        $url .= urlencode($video_url);

        // set the semi-ubiquitous parameters
        $max_width = isset($params['max_width']) ? "&maxwidth=" . $params['max_width'] : "";
        $max_height = isset($params['max_height']) ? "&maxheight=" . $params['max_height'] : "";
        if (empty($max_height)) // correct for a bug in YouTube response if only maxheight is set and the video is over 612px wide
        {
            $max_height = "&maxheight=" . $max_width;
        }
        $wmode_param = isset($params['wmode']) ? "&wmode=" . $params['wmode'] : "";
        $url .= $max_width . $max_height . $wmode_param;

        // cache can be disabled by setting 0 as the cache_minutes param
        if (isset($params['cache_minutes']) && $params['cache_minutes'] !== false && is_numeric($params['cache_minutes']))
        {
            $cache_refresh_minutes = $params['cache_minutes'];
        }

        // optional provider prefixed parameters
        $providerExtraParams = [];
        if ($isVimeo)
        {
            $providerExtraParams = $this->getPrefixedParams($params, 'vimeo_');
        }
        else if ($isWistia)
        {
            $providerExtraParams = $this->getPrefixedParams($params, 'wistia_');

            // handle legacy shortcuts
            if (isset($providerExtraParams['type']))
            {
                $providerExtraParams['embedType'] = $providerExtraParams['type'];
                unset($providerExtraParams['type']);
            }
            if (isset($providerExtraParams['foam']))
            {
                $providerExtraParams['videoFoam'] = $providerExtraParams['foam'];
                unset($providerExtraParams['foam']);
            }
        }
        else if ($isViddler)
        {
            $providerExtraParams = $this->getPrefixedParams($params, 'viddler_');
        }
        if (!empty($providerExtraParams))
        {
            $url .= '&' . $this->makeUrlKeyValuePairsString($providerExtraParams);
        }

        // checking if url has been cached
        $cached_url = craft()->fileCache->get($url);

        if (!$cache_refresh_minutes || $is_cache_expired || !$cached_url)
        {
            // create the info and header variables
            list($video_info, $video_header) = $this->getVideoInfo($url);

            // write the data to cache if caching hasn't been disabled
            if ($cache_refresh_minutes)
            {
                craft()->fileCache->set($url, $video_info, $cache_refresh_minutes);
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
        if ($video_info === false)
        {
            return $output == "Video not found" ? '' : $video_data;
        }

        // inject wmode transparent if required
        $wmode = isset($params['wmode']) ? $params['wmode'] : "";
        if ($wmode === 'transparent' || $wmode === 'opaque' || $wmode === 'window')
        {
            $param_str = '<param name="wmode" value="'.$wmode.'"></param>';
            $embed_str = ' wmode="' . $wmode . '" ';

            // determine whether we are dealing with iframe or embed and handle accordingly
            if (strpos($video_info->html, "<iframe") === false)
            {
                $param_pos = strpos($video_info->html, "<embed");
                $video_info->html = substr($video_info->html, 0, $param_pos) . $param_str . substr($video_info->html, $param_pos);
                $param_pos = strpos($video_info->html, "<embed") + 6;
                $video_info->html = substr($video_info->html, 0, $param_pos) . $embed_str . substr($video_info->html, $param_pos);
            }
            else
            {
                // determine whether to add question mark to query string
                preg_match('/<iframe.*?src="(.*?)".*?<\/iframe>/i', $video_info->html, $matches);
                $append_query_marker = (strpos($matches[1], '?') !== false ? '' : '?');

                $video_info->html = preg_replace('/<iframe(.*?)src="(.*?)"(.*?)<\/iframe>/i', '<iframe$1src="$2' . $append_query_marker . '&wmode=' . $wmode . '"$3</iframe>', $video_info->html);
            }
        }

        // add in the YouTube-specific params
        if ($isYouTube)
        {
            $youTubeParams = $this->getPrefixedParams($params, 'youtube_');
            if (!empty($youTubeParams))
            {
                //handle any YouTube-specific param updates
                if (isset($youTubeParams['playlist']))
                {
                    // if the playlist is set to a url and not an id, then try to update it
                    // regex from https://stackoverflow.com/a/26660288/1136822
                    $value = $youTubeParams['playlist'];
                    if (preg_match("#([\/|\?|&]vi?[\/|=]|youtu\.be\/|embed\/)(\w+)#", $value, $matches))
                    {
                        $youTubeParams['playlist'] = $matches[2];
                    }
                }

                //work the params into the embed URL
                preg_match('/.*?src="(.*?)".*?/', $video_info->html, $matches);
                if (!empty($matches[1]))
                {
                    $video_info->html = str_replace($matches[1], $matches[1] . '&' . $this->makeUrlKeyValuePairsString($youTubeParams), $video_info->html);
                }
            }
        }

        // add the vimeo_player_id or id param value to the iFrame HTML if set
        $id = '';
        if (!empty($params['vimeo_player_id']))
        {
            $id = $params['vimeo_player_id'];
        }
        else if (!empty($params['id']))
        {
            $id = $params['id'];
        }
        if (!empty($id))
        {
            $video_info->html = preg_replace('/<iframe/i', '<iframe id="' . $id . '"', $video_info->html);
        }

        // add the class to the iFrame HTML if set
        if (!empty($params['class']))
        {
            $video_info->html = preg_replace('/<iframe/i', '<iframe class="' . $params['class'] . '"', $video_info->html);
        }

        // add the attributes string to the iFrame HTML if set
        if (!empty($params['attributes']))
        {
            $video_info->html = preg_replace('/<iframe/i', '<iframe ' . $params['attributes'], $video_info->html);
        }

        // set the encode html to output properly in Twig
        $charset = craft()->templates->getTwig()->getCharset();
        $twig_html = new \Twig_Markup($video_info->html, $charset);
        //$video_info->html = $twig_html;

        // actually setting thumbnails at a reasonably consistent size, as well as getting higher-res images
        if ($isYouTube)
        {
            $video_info->highres_url = str_replace('hqdefault', 'maxresdefault', $video_info->thumbnail_url);
            $video_info->medres_url = $video_info->thumbnail_url;
            $video_info->thumbnail_url = str_replace('hqdefault', 'mqdefault', $video_info->thumbnail_url);
        }
        else if ($isVimeo)
        {
            $video_info->highres_url = preg_replace('/_(.*?)\./', '_1280.', $video_info->thumbnail_url);
            $video_info->medres_url = preg_replace('/_(.*?)\./', '_640.', $video_info->thumbnail_url);
            $video_info->thumbnail_url = preg_replace('/_(.*?)\./', '_295.', $video_info->thumbnail_url);
        }
        else if ($isWistia)
        {
            $video_info->highres_url = str_replace('?image_crop_resized=100x60', '', $video_info->thumbnail_url);
            $video_info->medres_url = str_replace('?image_crop_resized=100x60', '?image_crop_resized=640x400', $video_info->thumbnail_url);
            $video_info->thumbnail_url = str_replace('?image_crop_resized=100x60', '?image_crop_resized=240x135', $video_info->thumbnail_url);
        }
        else if ($isViddler)
        {
            $video_info->highres_url = $video_info->thumbnail_url;
            $video_info->medres_url = $video_info->thumbnail_url;
            $video_info->thumbnail_url = str_replace('thumbnail_2', 'thumbnail_1', $video_info->thumbnail_url);
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

        // replace the embed code with the Twig object
        $video_data['embed_code'] = $twig_html;

        return $video_data;

    }

    /**
     * Request the video info via cURL or file_get_contents.
     *
     * @param string $vid_url The video URL.
     * @return array An array containing the video info (or false) and the response code (or false).
     */
    public function getVideoInfo($vid_url)
    {
        // do we have curl?
        if (function_exists('curl_init'))
        {
            $curl = curl_init();

            // cURL options
            $options = array(
                CURLOPT_URL => $vid_url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false //no ssl verification
            );

            curl_setopt_array($curl, $options);

            $video_info = curl_exec($curl);
            $video_header = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // close the request
            curl_close($curl);

        } // do we have fopen?
        elseif (ini_get('allow_url_fopen') === true)
        {
            $video_header = ($video_info = file_get_contents($vid_url)) ? '200' : true;
        }
        else
        {
            $video_header = $video_info = false;
        }

        return array($video_info, $video_header);
    }

    /**
     * Gets all of the values from the params array that start with the
     * specified prefix.
     *
     * @param array $params The array of params to check.
     * @param string $prefix The prefix that keys should start with in order to be returned.
     * @return array The array of (unprefixed) key => value pairs that matched the specified prefix.
     */
    private function getPrefixedParams($params = [], $prefix = '')
    {
        $prefixedParams = [];

        if (empty($prefix) || empty($params))
        {
            return $prefixedParams;
        }

        foreach ($params as $key => $value)
        {
            // if this param doesn't start with the prefix then continue the loop
            if (strpos($key, $prefix) !== 0)
            {
                continue;
            }

            // get the text after the prefix as the key name or continue
            $paramKey = substr($key, strlen($prefix));
            if (empty($paramKey))
            {
                continue;
            }

            $prefixedParams[$paramKey] = $value;
        }

        return $prefixedParams;
    }

    /**
     * Converts an array of key => value pairs to a URL param string.
     *
     * @param array $pairs An array of key => value pairs
     * @return string The resulting string. Ex: key=value&key2=value2
     */
    private function makeUrlKeyValuePairsString($pairs = [])
    {
        $chunks = [];

        if (!empty($pairs) && is_array($pairs))
        {
            foreach ($pairs as $key => $value)
            {
                $chunks[] = $key . '=' . $value;
            }
        }

        return implode('&', $chunks);
    }
}