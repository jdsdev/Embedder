Embedder
========

Embedder is a plugin for Craft CMS based on Antenna by Vector Media Group (https://github.com/vector/Antenna) that will generate the exact, most up-to-date YouTube, Vimeo, Wistia, or Viddler embed code available. It also gives you access to the video’s title, its author, the author’s YouTube/Vimeo URL, and a thumbnail. All you have to do is pass it a single URL.

You can also output various pieces of metadata about the video.

Simple Usage
------
```twig
{{ craft.embedder.embed (entry.embedderVideo, {max_width:500, max_height:800}) }}
```

Full Usage
------
```twig
{% set video = craft.embedder.url(entry.embedderVideo, {max_width:500, max_height:800}) %}

{{ video.embed_code }}
<ul>
    <li>title : {{ video.video_title }}</li>
    <li>description : {{ video.video_description }}</li>
    <li>thumbnail : <img src="{{ video.video_thumbnail }}"></li>
</ul>
```

Set the max\_width and/or max\_height for whatever size your website requires. The video will be resized to be within those dimensions, and will stay at the correct proportions.

The optional wmode parameter can be used if you're experiencing issues positioning HTML content in front of the embedded media. It accepts values of transparent, opaque and window.

If used as a single tag, it returns the HTML embed/object code for the video. If used as a pair, you get access to the 5 variables above and can use them in conditionals.

There are three image sizes available for videos: ```{video_thumbnail}```, ```{video_mediumres}```, and ```{video_highres}```. They are not consistent across services but they should fall into rough size brackets. ```{video_thumbnail}``` is going to be between 100-200px wide; ```{video_mediumres}``` will be around 400-500px wide; and ```{video_highres}``` will be at least the full size of your uploaded video and could be as wide as 1280px.

Embedder will automatically enforce HTTPS if the provided video URL has a protocol of https:// and is supported by the video service. Alternatively, you can also attempt to force the particular service to return the HTTPS resource by adding the parameter:

- force_https='true'

If you're using YouTube, you get access to one more parameter:

- youtube_rel='0/1' -- Show related videos at end of video. Defaults to 1.

If you're using Vimeo, you get access to four more parameters and one more variable:

- vimeo_byline='true/false' -- Shows the byline on the video. Defaults to true.
- vimeo_title='true/false' -- Shows the title on the video. Defaults to true.
- vimeo_portrait='true/false' -- Shows the user's avatar on the video. Defaults to true.
- vimeo_autoplay='true/false' -- Automatically start playback of the video. Defaults to false.
- vimeo_api='true/false' -- Adds 'api=1' to the vimeo embed url to allow JavaScript API usage. Defaults to false.
- {video_description} -- The description of the video, as set in Vimeo

If you're using Viddler, you get access to two more parameters:

- viddler_type='simple/player' -- Specifies the player type. Defaults to player.
- viddler_ratio='widescreen/fullscreen' -- Aspect ratio will be automatically determined if not set.

Warranty/License
------
There's no warranty of any kind. If you find a bug, please tell me and I may try to fix it. It's provided completely as-is; if something breaks, you lose data, or something else bad happens, the author(s) and owner(s) of this plugin are in no way responsible.

This plugin is owned by Pinkston Digital (http://pinkstondigital.com). You can modify it and use it for your own personal or commercial projects, but you can't redistribute it.
