<?php

namespace jdsdev\embedder\variables;

use jdsdev\embedder\Embedder;

class EmbedderVariable
{
    function set($name = '', $value = '', $expire = '', $path = '', $domain = '', $secure = '', $httponly = '')
    {
        return 'set success';
    }

    function get($name)
    {
        return 'get success!' . $name;
    }

    public function embed($url, $params = [])
    {
        return Embedder::$plugin->embedder->embed($url, $params, 'simple');
    }

    public function url($url, $params = [])
    {
        return Embedder::$plugin->embedder->embed($url, $params, 'full');
    }
}
