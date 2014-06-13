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
	    return '0.9.0';
	}

	public function getDeveloper()
	{
	    return 'Burst Creative';
	}

	public function getDeveloperUrl()
	{
	    return 'http://burstcreative.com';
	}

	public function hasCpSection()
	{
		return false;
	}
}
