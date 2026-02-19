<?php
/**
 * Check evaluation logic for Site Readiness Check.
 *
 * @package Site_Readiness_Check
 */

defined( 'ABSPATH' ) || exit;

/**
 * Evaluate all configured checks and return results.
 *
 * @return array[] {
 *     @type string $label    Check label.
 *     @type string $severity 'critical' or 'recommended'.
 *     @type bool   $passed   Whether the check passed.
 *     @type mixed  $expected The expected value.
 *     @type mixed  $actual   The actual value found.
 * }
 */
function src_evaluate_checks() {
	$checks  = get_option( 'src_checks', array() );
	$results = array();

	foreach ( $checks as $check ) {
		$actual = src_get_actual_value( $check );

		if ( 'plugin' === $check['type'] ) {
			$passed = $actual === $check['value'];
		} else {
			$passed = src_compare_values( $actual, $check['value'], $check['value_type'] );
		}

		$results[] = array(
			'label'    => $check['label'],
			'severity' => $check['severity'],
			'passed'   => $passed,
			'expected' => $check['value'],
			'actual'   => $actual,
			'type'     => $check['type'],
			'name'     => $check['name'],
		);
	}

	return $results;
}

/**
 * Get the actual value for a check from the database or PHP constants.
 *
 * @param array $check Check configuration.
 * @return mixed The actual value, or null if not found.
 */
function src_get_actual_value( $check ) {
	if ( 'option' === $check['type'] ) {
		return get_option( $check['name'], null );
	}

	if ( 'constant' === $check['type'] ) {
		return defined( $check['name'] ) ? constant( $check['name'] ) : null;
	}

	if ( 'plugin' === $check['type'] ) {
		return src_get_plugin_status( $check['name'] );
	}

	return null;
}

/**
 * Get the status of a plugin by its folder slug.
 *
 * @param string $slug Plugin folder slug (e.g. 'woocommerce').
 * @return string 'active', 'inactive', or 'not-installed'.
 */
function src_get_plugin_status( $slug ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$all_plugins = get_plugins();
	$plugin_file = '';

	foreach ( $all_plugins as $file => $data ) {
		if ( strpos( $file, $slug . '/' ) === 0 || $file === $slug . '.php' ) {
			$plugin_file = $file;
			break;
		}
	}

	if ( empty( $plugin_file ) ) {
		return 'not-installed';
	}

	return is_plugin_active( $plugin_file ) ? 'active' : 'inactive';
}

/**
 * Compare an actual value against an expected value using the specified type.
 *
 * @param mixed  $actual     The actual value.
 * @param string $expected   The expected value (always stored as string).
 * @param string $value_type The comparison type: 'string', 'integer', or 'boolean'.
 * @return bool Whether the values match.
 */
function src_compare_values( $actual, $expected, $value_type ) {
	if ( null === $actual ) {
		return false;
	}

	switch ( $value_type ) {
		case 'boolean':
			return (bool) (int) $expected === (bool) $actual;

		case 'integer':
			return (int) $actual === (int) $expected;

		default:
			return (string) $actual === (string) $expected;
	}
}
