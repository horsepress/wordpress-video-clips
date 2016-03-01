<?php
/**
 * Plugin Name: Video clips
 * Plugin URI: 
 * Description: Allows markup of video excerpts, and linking to them
 * Version: 0.0.2
 * Author: 
 * Author URI: 
 * License: GPL2
 */

//required scripts
function video_tag_enqueue_scripts() {
    if (!is_admin()) {
        $plugin_url = plugins_url('', __FILE__);
        
		wp_register_script('videojs', $plugin_url . '/video.min.js');
        wp_enqueue_script('videojs');
		
        wp_register_script('videojshotkeys', $plugin_url . '/js/videojs.hotkeys.js');
        wp_enqueue_script('videojshotkeys');        
		wp_register_script('videojsabloop', $plugin_url . '/js/videojs-abloop.js');
        wp_enqueue_script('videojsabloop');
		wp_register_script('videojsyoutube', $plugin_url . '/js/videojs-youtube.js');
        wp_enqueue_script('videojsyoutube');
		wp_register_script('videotaghead', $plugin_url . '/js/video-tag-head.js');
        wp_enqueue_script('videotaghead');
        wp_register_style('videojs', $plugin_url . '/css/video-js.css');
        wp_enqueue_style('videojs');        
		wp_register_style('videotag', $plugin_url . '/css/video-tag.css');
        wp_enqueue_style('videotag');
    }
}
add_action('wp_enqueue_scripts', 'video_tag_enqueue_scripts');

$video_tag_last_video_id = '';

//vid shortcode
function video_tag_video_handler( $atts, $content = null ) { 
	global $video_tag_last_video_id;
	 $a = shortcode_atts( array(
        'url' => '',
    ), $atts );
	$url = $atts[0]; // $a['url'] ? $a['url']:$atts[0] ;
	$id = "vid-" . uniqid();
	$video_tag_last_video_id = $id;
	$script = <<<EOD
<video controls id="$id" class="video-js vjs-default-skin" data-setup='{
	"fluid": true
	,"playbackRates": [0.1, 0.2, 0.5, 1, 2, 5]
	,"controls":true
	,"preload":"metadata"
	,"plugins": {
		"abLoopPlugin" : {}
	}
}'>
	<source src="$url" type="video/mp4" />
</video>
<script>
	videojs('$id').ready(function() {
		this.hotkeys(VIDEOTAG.hotkeyOptions);
	});
</script>
EOD;
	
	$ret = $script . do_shortcode($content);
	return  $ret;
}
 add_shortcode( 'vid', 'video_tag_video_handler' );
 
// clip shortcode [clip ocean.mp4#t=10,20]
function video_tag_clip_handler( $atts, $content = null ) {
	global $video_tag_last_video_id;
	//global $lastVideoURL;
	//global $lastVideoID;
    $a = shortcode_atts( array(
        'url' => '',
        'video' => '',
        'start' => 0,
        'end' => 'false'
        //'attr_2' => 'attribute 2 default',
        // ...etc
    ), $atts );
	//static $test = 0;
	
	$url=$atts[0];
	
	$pattern = '/^(.*?)(#.*)?$/i';
	$urlLessFragment = preg_replace($pattern, '$1', $url);
	$urlFragment = preg_replace($pattern, '$2', $url);
	

	//$vp = $GLOBALS['easy_video_player'];
    $video = $urlLessFragment ?  $video_tag_last_video_id : $video_tag_last_video_id;   //$vp->lastVideoID
	//$url =  $a['url'] ? $a['url'] : $vp->lastVideoURL ;   //$vp->lastVideoURL
    
    /*$tags = array();
    foreach (array_keys($atts) as $key) {
        if(is_int($key)) {
            array_push($tags,$atts[$key]); //
        }
    }*/

	$location = $urlFragment;
	$startEndPattern = '/#t=([^,]*)(,([^,]*))?/';
	$start = preg_replace($startEndPattern, '$1', $urlFragment);
	$end = preg_replace($startEndPattern, '$3', $urlFragment);
	
$script = <<<EOD
<a onclick="return VIDEOTAG.activateClip('$video','$location');" href="$url">[{$start}s-{$end}s]</a> 
EOD;
	if ($urlLessFragment){
	$newid = "xvid-" . uniqid();
	$script = <<<EOD
<a onclick="return VIDEOTAG.getOrCreateVideoDiv('$newid','$urlLessFragment','$urlFragment');" href="$url">[{$start}s-{$end}s]</a> 
EOD;
	}	
	 $ret = $script . do_shortcode($content);   //var_dump($tags)
	 //$ret = $ret . var_dump($GLOBALS['easy_video_player']);
	 //$ret = $ret . $vp->lastVideoID;
	 return $ret;
}

add_shortcode( 'clip', 'video_tag_clip_handler' );

?>
