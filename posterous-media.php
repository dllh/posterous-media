<?php

/*
Plugin Name: Posterous Importer Media Shortcode
Plugin URI: http://wordpress.org/extend/plugins/posterous-importer/
Description: Insert media links/shortcodes for video and audio imported from Posterous.
Author: Daryl L. L. Houston (dllh)
Author URI: http://automattic.com/
Version: 0.01
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

function posterous_video_shortcode( $file_url, $post_content ) {
	$output = $post_content;
	// Video is pretty tricky, so just provide a download link.
	$output .= '<a href="' . esc_url( $file_url ) . '">Download the Movie</a>';
	return $output;
}
add_filter( 'posterous_video_shortcode', 'posterous_video_shortcode', 10, 2 );

function posterous_audio_shortcode( $file_url, $post_content ) {
	$output = $post_content;
	// This works with the Jetpack plugin installed.
	$output .= '[audio ' . esc_url( $file_url ) . ']';
	return $output;
}
add_filter( 'posterous_audio_shortcode', 'posterous_audio_shortcode', 10, 2 );

function posterous_update_post_with_shortcodes( $post, $attachment_id, $url, $media_types ) {
        $audio_video_updated = false;
	$gallery_update = false;

	$video_mime_types = array(
		'video/mpeg',
		'video/mp4',
		'video/ogg',
		'video/quicktime',
	);

	$audio_mime_types = array(
		'audio/mp3',
		'audio/mpeg',
		'audio/ogg',
		'audio/vorbis',
	);

	$local_url = wp_get_attachment_url( $attachment_id );
	$file_info = wp_check_filetype_and_ext( $local_url, $local_url );
        
	if ( in_array( $file_info['type'], $video_mime_types ) ) {
		$post->post_content = apply_filters( 'posterous_video_shortcode', $local_url, $post->post_content );
                $audio_video_updated = true;                
	}

	if ( in_array( $file_info['type'], $audio_mime_types ) ) {
		$post->post_content = apply_filters( 'posterous_audio_shortcode', $local_url, $post->post_content );
            	$audio_video_updated = true;
        }

	$re = '!<div class=(\'|")p_see_full_gallery(\'|")>.+</div>!Us';
	$post_content_before_replacement = $post->post_content;
	$post->post_content = preg_replace_callback( $re, create_function( '$matches', 'return "";' ), $post->post_content );
	if ( $post_content_before_replacement != $post->post_content ) {
		$gallery_updated = true;
	}

        if ( $audio_video_updated ) {
		// Check for some garbage that Poterous adds to link back to the assets on their server and strip it out.
		// This is probably a little bit brittle (ie, if they change the markup out from under us, it could break),
		// but I think it beats importing lame markup pointing to a service content is being migrated away from.
		$re = '!<div.*class=(\'|")[^(\'|")]*p_embed[^>]+>.+<div.*class=(\'|")[^(\'|")]*p_embed_description(\'|")>.+</div>.+</div>!iUs';
		$post->post_content = preg_replace_callback( $re, create_function( '$matches', 'return "";' ), $post->post_content );
        }

	if ( $gallery_updated || $audio_video_updated ) {
		// Now update the post.
		wp_update_post( $post );
	}
}

add_action( 'posterous_process_attachment_post_update', 'posterous_update_post_with_shortcodes', 10, 4 );
