<?php
namespace Craft;

class EmbedderVariable
{
    function set($name = "", $value = "", $expire = "", $path = "", $domain = "", $secure = "", $httponly = "")
    {
        return 'set success';
    }
    
    function get($name)
    {
        return 'get success!' . $name;      
    }

    public function embed($url, $params = [])
    {
        return craft()->embedder->embed($url, $params, "simple");
    }

    public function url($url, $params = [])
    {
        return craft()->embedder->embed($url, $params, "full");
    }
}