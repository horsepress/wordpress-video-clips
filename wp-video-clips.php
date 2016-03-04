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
 
 
if(!class_exists('WP_Plugin_Template')) {
	class WP_video_clips_plugin {
		
		public function __construct()
		{
			add_action( 'init', array($this,'create_post_type_clip') );
			add_action( 'init' ,array($this, 'add_tags_to_attachments') );
			add_action( 'init' ,array($this, 'add_categories_to_attachments') ); 
			add_action('wp_enqueue_scripts', array($this, 'video_tag_enqueue_scripts') );
			add_shortcode( 'clip', array($this, 'video_tag_clip_handler' ) );
			add_shortcode( 'vid', array($this, 'video_tag_video_handler' ) );
		}
		 
		public function create_post_type_clip() {
			register_post_type( 'videoclip',
				array(
					'labels' => array(	'name' => __( 'Clips' ), 'singular_name' => __( 'Clip' )	),
					'public' => true,
					'has_archive' => true,
					'taxonomies' => array('category','post_tag'), 
					'supports' => array('title','custom-fields','comments'), // 'content'
					'rewrite' => array('slug' => 'clips'),
				)
			);
		}
		
		public function add_categories_to_attachments() {
			register_taxonomy_for_object_type( 'category', 'attachment' );
		}

		// apply tags to attachments
		public function add_tags_to_attachments() {
			register_taxonomy_for_object_type( 'post_tag', 'attachment' );
			//register_taxonomy_for_object_type( 'post_tag', 'clip' );
		}
		
		public function video_tag_enqueue_scripts() {
			if (!is_admin()) {
				$plugin_url = plugins_url('', __FILE__);
				
				//load JS and CSS
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

		//these 
		protected $video_tag_last_video_id;
		protected $video_tag_last_video_url;

		private function  video_tag_TimeToSec($time) {
			$sec = 0;
			foreach (array_reverse(explode(':', $time)) as $k => $v) $sec += pow(60, $k) * $v;
			return $sec;
		}
		//vid shortcode
		public function video_tag_video_handler( $atts, $content = null ) { 
			 $a = shortcode_atts( array(
				'url' => '',
			), $atts );
			$url = $atts[0]; // $a['url'] ? $a['url']:$atts[0] ;
			$id = "vid-" . uniqid();
			$this->video_tag_last_video_id = $id;
			$this->video_tag_last_video_url = $url;
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

		 
		// clip shortcode [clip ocean.mp4#t=10,20]
		public function video_tag_clip_handler( $atts, $content = null ) {

			$a = shortcode_atts( array(
				'url' => '',
				'video' => '',
				'start' => 0,
				'end' => 'false'
			), $atts );
			
			$url=$atts[0];
			
			$pattern = '/^(.*?)(#.*)?$/i';
			$urlLessFragment = preg_replace($pattern, '$1', $url);
			$urlFragment = preg_replace($pattern, '$2', $url);
			

			$video = $urlLessFragment ?  $this->video_tag_last_video_id : $this->video_tag_last_video_id;   //$vp->lastVideoID
	
			$location = $urlFragment;
			$startEndPattern = '/#t=([^,]*)(,([^,]*))?/';
			$start = preg_replace($startEndPattern, '$1', $urlFragment);
			$end = preg_replace($startEndPattern, '$3', $urlFragment);
			
			$startSec = $this->video_tag_TimeToSec($start);
			$endSec = $this->video_tag_TimeToSec($end);
			
			//put fragment in seconds... could just use starting seconds as not all browser players handle the end bit
			$urlFragmentSeconds ='#t=' . $startSec . ',' . $endSec ;
			$fullUrl = ($urlLessFragment ? $urlLessFragment : $this->video_tag_last_video_url) . $urlFragmentSeconds;    //$urlFragment
			
		$script = <<<EOD
		<a onclick="return VIDEOTAG.activateClip('$video','$location');" href="$fullUrl">[{$start}s-{$end}s]</a> 
EOD;
			if ($urlLessFragment){
			$newid = "xvid-" . uniqid();
			$script = <<<EOD
		<a onclick="return VIDEOTAG.getOrCreateVideoDiv('$newid','$urlLessFragment','$urlFragment');" href="$fullUrl">[{$start}s-{$end}s]</a> 
EOD;
			}	
			 $ret = $script . do_shortcode($content);   //var_dump($tags)

			 return $ret;
		}
		
        public static function activate(){
            // Do nothing
        } 
    

        public static function deactivate(){
            // Do nothing
        } 
	}
	
}

if(class_exists('WP_video_clips_plugin')){
	
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_video_clips_plugin', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_video_clips_plugin', 'deactivate'));

    // instantiate the plugin class
    $wpVideoClipsPlugin = new WP_video_clips_plugin();
}


?>
