<?php
/**
 * Add Pods GF integration for a specific form
 *
 * @param string|Pods Pod name (or Pods object)
 * @param int $form_id GF Form ID
 * @param array $options Form options for integration
 */
function pods_gf( $pod, $form_id, $options = array() ) {

	require_once( PODS_GF_DIR . 'includes/Pods_GF.php' );

	return new Pods_GF( $pod, $form_id, $options );

}

/**
 * Setup Pods_GF_UI object
 *
 * @param array $options Pods_GF_UI option overrides
 *
 * @return Pods_GF_UI
 */
function pods_gf_ui( $options = array() ) {

	require_once( PODS_GF_DIR . 'includes/Pods_GF_UI.php' );

	return new Pods_GF_UI( $options );

}

/**
 * Init Pods GF UI if there's a config to run
 */
function pods_gf_ui_init() {

	/**
	 * @var $pods_gf_ui Pods_GF_UI
	 */
	global $pods_gf_ui;

	$options = array();

	$path = explode( '?', $_SERVER[ 'REQUEST_URI' ] );
	$path = explode( '#', $path[ 0 ] );
	$path = trim( $path[ 0 ], '/' );

	// Root
	if ( strlen( $path ) < 1 ) {
		$uri = '/';

		$options = apply_filters( 'pods_gf_ui_init=' . $uri, $options, $uri );
	}
	// Pages and wildcards
	else {
		$uri = '/' . $path . '/';

		$exploded_path = array_reverse( explode( '/', $path ) );
		$exploded_w = $exploded_path;
		$total = count( $exploded_path );

		foreach ( $exploded_path as $k => $exploded ) {
			if ( $k == ( $total - 1 ) ) {
				break;
			}

			$exploded_w[ $k ] = '*';

			$wildcard_uri = '/' . implode( '/', array_reverse( $exploded_w ) ) . '/';

			$options = apply_filters( 'pods_gf_ui_init=' . $wildcard_uri, $options, $uri );

			if ( !is_array( $options ) ) {
				break;
			}
		}

		if ( is_array( $options ) ) {
			$options = apply_filters( 'pods_gf_ui_init=' . $uri, $options, $uri );
		}
	}

	if ( is_array( $options ) ) {
		$options = apply_filters( 'pods_gf_ui_init', $options, $uri );
	}

	// Bail on processing
	if ( empty( $options ) ) {
		return;
	}

	$pods_gf_ui = pods_gf_ui( $options );

	// Add content handler
	add_filter( 'the_content', 'pods_gf_ui_content' );

}

/**
 * Ouput Pods GF UI if there's a config set for the page
 *
 * @param string $content
 * @param int $post_id
 *
 * @return string Content
 */
function pods_gf_ui_content( $content, $post_id = 0 ) {

	/**
	 * @var $pods_gf_ui Pods_GF_UI
	 */
	global $pods_gf_ui;

	if ( false === strpos( $content, '[pods-gf-ui' ) && is_object( $pods_gf_ui ) && !empty( $post_id ) && is_single( $post_id ) ) {
		ob_start();

		$pods_gf_ui->ui();

		$content .= "\n" . ob_get_clean();
	}

	return $content;

}

/**
 * Detect if there's a shortcode currently set in the content, if so, run it
 */
function pods_gf_ui_detect_shortcode() {

	global $pods_gf_ui;

	if ( !is_object( $pods_gf_ui ) && is_singular() ) {
		global $post;

        $form_id = (int) pods_var( 'gform_submit', 'post' );

		if ( 0 < $form_id && preg_match( '/\[pods\-gf\-ui/i', $post->post_content ) ) {
			$form_info = RGFormsModel::get_form( $form_id );

			if ( !empty( $form_info ) && $form_info->is_active ) {
				$GLOBALS[ 'pods-gf-ui-off' ] = true;

				do_shortcode( $post->post_content );

				unset( $GLOBALS[ 'pods-gf-ui-off' ] );
			}
		}
	}

}