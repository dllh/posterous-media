<?php

/*
Plugin Name: Posterous Importer Media Shortcodes
Plugin URI: http://wordpress.org/extend/plugins/posterous-importer/
Description: Insert media links/shortcodes for video, audio, and images imported from Posterous.
Author: Daryl L. L. Houston (dllh)
Author URI: http://automattic.com/
Version: 0.01
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

// Inserts a [quicktime] shortcode for video files. Compatible with the video plugin http://wordpress.org/extend/plugins/vipers-video-quicktags/
function posterous_video_shortcode( $file_url, $post_content ) {
	$output = '[quicktime width="500" height="285"]' . esc_url( $file_url ) . "[/quicktime]\n" . $post_content;
	return $output;
}
add_filter( 'posterous_video_shortcode', 'posterous_video_shortcode', 10, 2 );

// Inserts an [audio] shortcode for audio files. Works nicely with http://jetpack.me/
function posterous_audio_shortcode( $file_url, $post_content ) {
	$output = $post_content;
	// This works with the Jetpack plugin installed.
	$output .= '[audio ' . esc_url( $file_url ) . ']';
	return $output;
}
add_filter( 'posterous_audio_shortcode', 'posterous_audio_shortcode', 10, 2 );

/**
 * A callback for preg_replace_callback that checks the matched data
 * and returns a gallery shortcode if it contains more than one image
 * or the matched code otherwise. Assumes this regex at the time 
 * of writing (defined in posterous_update_post_with_shortcodes() ):
 * $re = '!<div[^>]+class=(\'|")[^(\'|")]*p_embed[^>]+>.+</div>!is';
 */
function posterous_get_markup_for_posts_with_images( $matches ) {
	preg_match_all( '/<img/', $matches[2], $images );
	if ( count( $images[0] ) > 0 )
		return '[gallery]';

	return $matches[0];
}

// The workhorse function, fired after a media file is imported, that checks to see if the post needs to be manipulated to handle the media type properly.
function posterous_update_post_with_shortcodes( $post, $attachment_id, $url, $media_types ) {

	$audio_video_updated = false;
	$gallery_updated = false;
	$pdf_updated = false;

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

 	if ( 'pdf' == $file_info['ext'] ) {
		$post->post_content .= '[gview file="' . esc_url( $local_url ) . '"]';
		$pdf_updated = true;
        }

	// Add the [gallery] shortcode for posts that have more than one image in them.
	$re = '!<div[^>]+class=(\'|")[^(\'|")]*p_embed[^>]+>(.+)</div>!is';
	$post_content_saved = $post->post_content;
	$post->post_content = preg_replace_callback( $re, 'posterous_get_markup_for_posts_with_images', $post->post_content );
	if ( $post_content_saved != $post->post_content )
		$gallery_updated = true;

	// Make sure we don't still have any pesky "See gallery on Posterous" links in the markup.
	$re = '!<div class=(\'|")p_see_full_gallery(\'|")>.+</div>!Us';
	$post_content_before_replacement = $post->post_content;
	$post->post_content = preg_replace_callback( $re, create_function( '$matches', 'return "";' ), $post->post_content );
	if ( $post_content_before_replacement != $post->post_content ) {
		$gallery_updated = true;
	}

	// Make sure we don't still have other pesky "See on Posterous" markup for embeds in the post.
	if ( $audio_video_updated ) {
		$re = '!<div.*class=(\'|")[^(\'|")]*p_embed[^>]+>.+<div.*class=(\'|")[^(\'|")]*p_embed_description(\'|")>.+</div>.+</div>!iUs';
		$post->post_content = preg_replace_callback( $re, create_function( '$matches', 'return "";' ), $post->post_content );
	}

	// Trim height/width parameters from img tags because Posterous gets them wrong sometimes and distorts photos.
	$post_content_before_replacement = $post->post_content;
	global $allowedposttags;
	$trimmed_allowedposttags = $allowedposttags;
	unset( $trimmed_allowedposttags['img']['width'] );
	unset( $trimmed_allowedposttags['img']['height'] );

	$post->post_content = wp_kses( $post->post_content, $trimmed_allowedposttags );
	if ( $post_content_before_repacement != $post->post_content )
		$gallery_updated = true;

	// After all that, if we've actually changed the post content any, update it in the database.	
	if ( $pdf_updated || $gallery_updated || $audio_video_updated ) {
		wp_update_post( $post );
	}
}

add_action( 'posterous_process_attachment_post_update', 'posterous_update_post_with_shortcodes', 10, 4 );
