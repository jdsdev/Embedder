# Embedder

Embedder is a plugin for Craft CMS based on [Antenna](https://github.com/vector/Antenna) by Vector Media Group that will generate the exact, most up-to-date YouTube, Vimeo, Wistia, or Viddler embed code available. It also gives you access to the video’s title, its author, the author’s YouTube/Vimeo URL, and a thumbnail. All you have to do is pass it a single URL.

You can also output various pieces of metadata about the video.

## Requirements

This plugin requires Craft 3 or later.

> For the Craft 2 version, see the [v1 branch](https://github.com/jdsdev/Embedder/tree/v1)

## Installation

To install the plugin, follow these instructions.

1.  Open your terminal and go to your Craft project:

        cd /path/to/project

2.  Then tell Composer to load the plugin:

        composer require jdsdev/craft-embedder

3.  In the Control Panel, go to Settings → Plugins and click the “Install” button for Embedder.

---

## Simple Usage

If used as a single tag (embedder.embed), it returns the HTML embed/object code for the video.

```twig
{{ craft.embedder.embed (entry.embedderVideo, {max_width:500, max_height:800}) }}
```

## Full Usage and Variables

If used by setting the video URL, you get access to several variables.

```twig
{% set video = craft.embedder.url(entry.embedderVideo, {max_width:500, max_height:800}) %}

{{ video.embed_code }}
<ul>
    <li>title : {{ video.video_title }}</li>
    <li>description : {{ video.video_description }}</li>
    <li>thumbnail : <img src="{{ video.video_thumbnail }}"></li>
</ul>
```

There are three image sizes available for videos: `video_thumbnail`, `video_mediumres`, and `video_highres`. They are not consistent across services but they should fall into rough size brackets. `video_thumbnail` is going to be between 100-200px wide; `video_mediumres` will be around 400-500px wide; and `video_highres` will be at least the full size of your uploaded video and could be as wide as 1280px.

## Parameters

### Dimensions

Set the `max_width` and/or `max_height` for whatever size your website requires. The video will be resized to be within those dimensions, and will stay at the correct proportions.

- `max_width: 500` - Can be any number. Left unspecified by default.
- `max_height: 800` - Can be any number. Left unspecified by default.

### Force HTTPS

Embedder will automatically enforce HTTPS if the provided video URL has a protocol of https:// and is supported by the video service. Alternatively, you can also attempt to force the particular service to return the HTTPS resource by adding the parameter:

- `force_https: true`

### YouTube

If you're using YouTube, you can use any of the [supported embed parameters](https://developers.google.com/youtube/player_parameters#Parameters). Simply prefix the parameters with `youtube_`. Here are some common parameters:

- `youtube_rel: 0` - Show related videos at the end of the video. Can be `0` or `1` (default).
- `youtube_showinfo: 0` - Show the video title overlay. Can be `0` or `1` (default).
- `youtube_controls: 0` - Show the video player controls. Can be `0` or `1` (default).
- `youtube_autoplay: 1` - Automatically start playback of the video. Can be `0` (default) or `1`.
- `youtube_enablejsapi: 1` - Enable the YouTube IFrame or JavaScript APIs. Can be `0` (default) or `1`.

### Vimeo

If you're using Vimeo, you can use any of the [supported embed parameters](https://github.com/vimeo/player.js#embed-options). Simply prefix the parameters with `vimeo_`. Here are some of the common parameters:

- `vimeo_byline: 0` - Shows the byline on the video. Can be `0` or `1` (default).
- `vimeo_title: 0` - Shows the title on the video. Can be `0` or `1` (default).
- `vimeo_portrait: 0` - Shows the user's avatar on the video. Can be `0` or `1` (default).
- `vimeo_loop: 1` - Loops the video playback. Can be `0` (default) or `1`.
- `vimeo_autoplay: 1` - Automatically start playback of the video. Can be `0` (default) or `1`.
- `vimeo_color: 'ff0000'` - Sets the theme color for the Vimeo player. Can be any hexidecimal color value (without the hash). Defaults to `'00adef'`.

You can also use the following Vimeo parameter:

- `vimeo_player_id: 'myVideoPlayer'` - Sets an ID on the player, which is useful if you want to control multiple videos on the same page in a different way.

The following extra variable is available when using Vimeo:

- `{{ video_description }}` - The description of the video, as set in Vimeo

### Viddler

If you're using Viddler, you get access to two more parameters:

- `viddler_type: 'simple'` - Specifies the player type. Can be `'simple'` or `'player'` (default).
- `viddler_ratio: 'widescreen'` - Aspect ratio. Can be `'widescreen'`, `'fullscreen'`, or left unspecified for automatically determined aspect ratio.

### Wistia

If you're using Wistia, you get access to two more parameters:

- `wistia_type` - Sets the supported embed type.
- `wistia_foam: true` - Makes the embedded video responsive using Wistia's Video Foam feature.

### HTML Output Control

You can also also control your output with the following parameters:

- `id: 'myId'` - Gives the iFrame an `id=` attribute with the specified value.
- `class: 'video player'` - Gives the iFrame a `class=` attribute with the specified value.
- `attributes: 'data-video data-player'` - Gives the iFrame the specified HTML attribute(s).

### wmode (deprecated with most providers)

The optional `wmode` parameter can be used if you're experiencing issues positioning HTML content in front of the embedded media. It accepts values of `transparent`, `opaque` and `window`.

---

## Contributions

- [Aaron Waldon](https://github.com/aaronwaldon) / @aaronwaldon - Reworked the logic to allow any provider parameters to be used. Added HTML output control parameters and updated the documentation.

- [Jonathan Sarmiento](https://github.com/jdsdev) - Updated the plugin for Craft 3.
