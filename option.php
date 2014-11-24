<?php
/**
 * Option API
 *
 * @package WordPress
 */

/**
 * Retrieve option value based on name of option.
 *
 * If the option does not exist or does not have a value, then the return value
 * will be false. This is useful to check whether you need to install an option
 * and is commonly used during installation of plugin options and to test
 * whether upgrading is required.
 *
 * If the option was serialized then it will be unserialized when it is returned.
 *
 * @since 1.5.0
 * @package WordPress
 * @subpackage Option
 * @uses apply_filters() Calls 'pre_option_$option' before checking the option.
 * 	Any value other than false will "short-circuit" the retrieval of the option
 *	and return the returned value. You should not try to override special options,
 * 	but you will not be prevented from doing so.
 * @uses apply_filters() Calls 'option_$option', after checking the option, with
 * 	the option value.
 *
 * @param string $option Name of option to retrieve. Expected to not be SQL-escaped.
 * @param mixed $default Optional. Default value to return if the option does not exist.
 * @return mixed Value set for the option.
 */
function get_option( $option, $default = false ) {
  global $option_cache;
  if (!isset($option_cache)) { $option_cache = array(); }
  if (array_key_exists( $option, $option_cache)) {
    $value = $option_cache[$option];
  } else {
	  $value = get_option_without_cache($option, $default);
    $option_cache[$option] = $value;
  }
  return $value;
}

function get_option_without_cache( $option, $default = false ) {
	global $wpdb;

	$option = trim( $option );
	if ( empty( $option ) )
		return false;

	// Allow plugins to short-circuit options.
	$pre = apply_filters( 'pre_option_' . $option, false );
	if ( false !== $pre )
		return $pre;

	if ( defined( 'WP_SETUP_CONFIG' ) )
		return false;

	if ( ! defined( 'WP_INSTALLING' ) ) {
		// prevent non-existent options from triggering multiple queries
		$notoptions = wp_cache_get( 'notoptions', 'options' );
		if ( isset( $notoptions[$option] ) )
			return apply_filters( 'default_option_' . $option, $default );

		$alloptions = wp_load_alloptions();

		if ( isset( $alloptions[$option] ) ) {
			$value = $alloptions[$option];
		} else {
			$value = wp_cache_get( $option, 'options' );

			if ( false === $value ) {
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

				// Has to be get_row instead of get_var because of funkiness with 0, false, null values
				if ( is_object( $row ) ) {
					$value = $row->option_value;
					wp_cache_add( $option, $value, 'options' );
				} else { // option does not exist, so we must cache its non-existence
					$notoptions[$option] = true;
					wp_cache_set( 'notoptions', $notoptions, 'options' );
					return apply_filters( 'default_option_' . $option, $default );
				}
			}
		}
	} else {
		$suppress = $wpdb->suppress_errors();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
		$wpdb->suppress_errors( $suppress );
		if ( is_object( $row ) )
			$value = $row->option_value;
		else
			return apply_filters( 'default_option_' . $option, $default );
	}

	// If home is not set use siteurl.
	if ( 'home' == $option && '' == $value )
		return get_option( 'siteurl' );

	if ( in_array( $option, array('siteurl', 'home', 'category_base', 'tag_base') ) )
		$value = untrailingslashit( $value );

	return apply_filters( 'option_' . $option, maybe_unserialize( $value ) );
}
