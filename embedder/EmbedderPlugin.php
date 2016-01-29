<?php
namespace Craft;

/**
 * Events plugin class
 */
class EmbedderPlugin extends BasePlugin
{
    public function getName()
    {
        return 'Embedder';
    }

    public function getVersion()
    {
        return '0.9.4';
    }

    public function getDeveloper()
    {
        return 'Pinkston Digital';
    }

    public function getDeveloperUrl()
    {
        return 'http://pinkstondigital.com';
    }

    public function hasCpSection()
    {
        return false;
    }
}
