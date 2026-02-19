<?php
/**
 * Settings page for Site Readiness Check.
 *
 * @package Site_Readiness_Check
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'src_register_settings_page' );
add_action( 'admin_init', 'src_register_settings' );
add_action( 'admin_enqueue_scripts', 'src_enqueue_settings_assets' );
add_filter( 'plugin_action_links_' . SRC_PLUGIN_BASENAME, 'src_add_settings_link' );

/**
 * Enqueue settings page assets.
 *
 * @param string $hook_suffix The current admin page hook suffix.
 */
function src_enqueue_settings_assets( $hook_suffix ) {
	if ( 'settings_page_src-settings' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'src-admin-settings',
		SRC_PLUGIN_URI . 'assets/admin-settings.css',
		array(),
		SRC_VERSION
	);

	wp_enqueue_script(
		'src-admin-settings',
		SRC_PLUGIN_URI . 'assets/admin-settings.js',
		array(),
		SRC_VERSION,
		true
	);

	wp_localize_script(
		'src-admin-settings',
		'srcSettings',
		array(
			'optionNames' => src_get_option_names(),
		)
	);
}

/**
 * Get all option names from the database, sorted alphabetically.
 *
 * @return string[]
 */
function src_get_option_names() {
	global $wpdb;

	$names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} ORDER BY option_name ASC" );

	return $names ? $names : array();
}

/**
 * Register the settings page under Settings.
 */
function src_register_settings_page() {
	add_options_page(
		__( 'Site Readiness Check', 'src' ),
		__( 'Site Readiness', 'src' ),
		'manage_options',
		'src-settings',
		'src_render_settings_page'
	);
}

/**
 * Register settings.
 */
function src_register_settings() {
	register_setting(
		'src_settings',
		'src_checks',
		array(
			'sanitize_callback' => 'src_sanitize_checks',
		)
	);
}

/**
 * Sanitize all checks.
 *
 * @param array $input Raw input.
 * @return array Sanitized input.
 */
function src_sanitize_checks( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$sanitized = array();

	foreach ( $input as $check ) {
		$type = in_array( $check['type'] ?? '', array( 'option', 'constant' ), true )
			? $check['type']
			: 'option';

		$value_type = in_array( $check['value_type'] ?? '', array( 'string', 'integer', 'boolean' ), true )
			? $check['value_type']
			: 'string';

		$severity = in_array( $check['severity'] ?? '', array( 'critical', 'recommended' ), true )
			? $check['severity']
			: 'recommended';

		$label = sanitize_text_field( $check['label'] ?? '' );
		$name  = sanitize_text_field( $check['name'] ?? '' );

		if ( empty( $name ) ) {
			continue;
		}

		$sanitized[] = array(
			'label'      => $label,
			'type'       => $type,
			'name'       => $name,
			'value'      => sanitize_text_field( $check['value'] ?? '' ),
			'value_type' => $value_type,
			'severity'   => $severity,
		);
	}

	return $sanitized;
}

/**
 * Add a Settings link to the plugin action links on the Plugins page.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function src_add_settings_link( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=src-settings' ) ),
		esc_html__( 'Settings', 'src' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}

/**
 * Render the settings page.
 */
function src_render_settings_page() {
	$checks = get_option( 'src_checks', array() );
	?>
	<div class="wrap src-settings">
		<h1><?php esc_html_e( 'Site Readiness Check', 'src' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'src_settings' ); ?>

			<h2><?php esc_html_e( 'Checks', 'src' ); ?></h2>
			<p><?php esc_html_e( 'Add checks that will be evaluated on the Site Readiness page. Each check verifies that an option or constant matches the expected value.', 'src' ); ?></p>

			<table class="src-checks-table widefat" id="src-checks-table">
				<thead>
					<tr>
						<th class="src-col-label"><?php esc_html_e( 'Label', 'src' ); ?></th>
						<th class="src-col-type"><?php esc_html_e( 'Type', 'src' ); ?></th>
						<th class="src-col-name"><?php esc_html_e( 'Name', 'src' ); ?></th>
						<th class="src-col-value"><?php esc_html_e( 'Expected value', 'src' ); ?></th>
						<th class="src-col-severity"><?php esc_html_e( 'Severity', 'src' ); ?></th>
						<th class="src-col-actions"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'src' ); ?></span></th>
					</tr>
				</thead>
				<tbody id="src-checks-body">
				<?php
				if ( ! empty( $checks ) ) {
					foreach ( $checks as $index => $check ) {
						src_render_check_row( $index, $check );
					}
				} else {
					src_render_check_row( 0, array() );
				}
				?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="6">
							<button type="button" class="button" id="src-add-check">
								<?php esc_html_e( '+ Add check', 'src' ); ?>
							</button>
						</td>
					</tr>
				</tfoot>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>

	<template id="src-check-row-template">
		<?php src_render_check_row( '__INDEX__', array() ); ?>
	</template>
	<?php
}

/**
 * Render a single check row.
 *
 * @param int|string $index Row index.
 * @param array      $check Check data.
 */
function src_render_check_row( $index, $check ) {
	$check = wp_parse_args(
		$check,
		array(
			'label'      => '',
			'type'       => 'option',
			'name'       => '',
			'value'      => '',
			'value_type' => 'string',
			'severity'   => 'recommended',
		)
	);
	?>
	<?php $is_option = 'option' === $check['type']; ?>
	<tr class="src-check-row" data-check-type="<?php echo esc_attr( $check['type'] ); ?>">
		<td class="src-col-label">
			<input
				type="text"
				name="src_checks[<?php echo esc_attr( $index ); ?>][label]"
				value="<?php echo esc_attr( $check['label'] ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Permalink structure is set', 'src' ); ?>"
				class="regular-text"
			/>
		</td>
		<td class="src-col-type">
			<select name="src_checks[<?php echo esc_attr( $index ); ?>][type]" class="src-type-select">
				<option value="option" <?php selected( $check['type'], 'option' ); ?>>
					<?php esc_html_e( 'Setting / Option', 'src' ); ?>
				</option>
				<option value="constant" <?php selected( $check['type'], 'constant' ); ?>>
					<?php esc_html_e( 'WP Config Constant', 'src' ); ?>
				</option>
			</select>
		</td>
		<td class="src-col-name">
			<select
				<?php echo $is_option ? 'name="src_checks[' . esc_attr( $index ) . '][name]"' : ''; ?>
				class="src-name-option"
			>
				<option value=""><?php esc_html_e( '— Select option —', 'src' ); ?></option>
				<?php foreach ( src_get_option_names() as $option_name ) { ?>
					<option value="<?php echo esc_attr( $option_name ); ?>" <?php selected( $check['name'], $option_name ); ?>>
						<?php echo esc_html( $option_name ); ?>
					</option>
				<?php } ?>
			</select>
			<input
				type="text"
				<?php echo ! $is_option ? 'name="src_checks[' . esc_attr( $index ) . '][name]"' : ''; ?>
				value="<?php echo esc_attr( $is_option ? '' : $check['name'] ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. WP_ENVIRONMENT_TYPE', 'src' ); ?>"
				class="regular-text src-name-constant"
			/>
		</td>
		<td class="src-col-value" data-value-type="<?php echo esc_attr( $check['value_type'] ); ?>">
			<div class="src-value-wrapper">
				<select name="src_checks[<?php echo esc_attr( $index ); ?>][value_type]" class="src-value-type-select">
					<option value="string" <?php selected( $check['value_type'], 'string' ); ?>>
						<?php esc_html_e( 'String', 'src' ); ?>
					</option>
					<option value="integer" <?php selected( $check['value_type'], 'integer' ); ?>>
						<?php esc_html_e( 'Integer', 'src' ); ?>
					</option>
					<option value="boolean" <?php selected( $check['value_type'], 'boolean' ); ?>>
						<?php esc_html_e( 'Boolean', 'src' ); ?>
					</option>
				</select>
				<input
					type="<?php echo 'integer' === $check['value_type'] ? 'number' : 'text'; ?>"
					<?php echo 'boolean' !== $check['value_type'] ? 'name="src_checks[' . esc_attr( $index ) . '][value]"' : ''; ?>
					value="<?php echo esc_attr( $check['value'] ); ?>"
					placeholder="<?php echo esc_attr( 'integer' === $check['value_type'] ? __( 'e.g. 0', 'src' ) : __( 'e.g. development', 'src' ) ); ?>"
					class="regular-text src-value-text"
				/>
				<select
					class="src-value-boolean"
					<?php echo 'boolean' === $check['value_type'] ? 'name="src_checks[' . esc_attr( $index ) . '][value]"' : ''; ?>
				>
					<option value="1" <?php selected( $check['value'], '1' ); ?>>
						<?php esc_html_e( 'True', 'src' ); ?>
					</option>
					<option value="0" <?php selected( $check['value'], '0' ); ?>>
						<?php esc_html_e( 'False', 'src' ); ?>
					</option>
				</select>
			</div>
		</td>
		<td class="src-col-severity">
			<select name="src_checks[<?php echo esc_attr( $index ); ?>][severity]">
				<option value="critical" <?php selected( $check['severity'], 'critical' ); ?>>
					<?php esc_html_e( 'Critical', 'src' ); ?>
				</option>
				<option value="recommended" <?php selected( $check['severity'], 'recommended' ); ?>>
					<?php esc_html_e( 'Recommended', 'src' ); ?>
				</option>
			</select>
		</td>
		<td class="src-col-actions">
			<button type="button" class="button-link src-remove-check" aria-label="<?php esc_attr_e( 'Remove this check', 'src' ); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</td>
	</tr>
	<?php
}
