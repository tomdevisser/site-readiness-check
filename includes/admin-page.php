<?php
/**
 * Admin page for Site Readiness Check under Tools.
 *
 * @package Site_Readiness_Check
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'src_register_admin_page' );
add_action( 'admin_enqueue_scripts', 'src_enqueue_admin_page_styles' );

/**
 * Enqueue the admin page stylesheet on the admin page.
 *
 * @param string $hook_suffix The current admin page hook suffix.
 */
function src_enqueue_admin_page_styles( $hook_suffix ) {
	if ( 'tools_page_site-readiness-check' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'src-admin-page',
		SRC_PLUGIN_URI . 'assets/admin-page.css',
		array(),
		SRC_VERSION
	);

	wp_enqueue_script(
		'src-admin-page',
		SRC_PLUGIN_URI . 'assets/admin-page.js',
		array(),
		SRC_VERSION,
		true
	);
}

/**
 * Register the admin page under Tools.
 */
function src_register_admin_page() {
	add_management_page(
		__( 'Site Readiness Check', 'src' ),
		__( 'Site Readiness', 'src' ),
		'manage_options',
		'site-readiness-check',
		'src_render_admin_page'
	);
}

/**
 * Render the admin page.
 */
function src_render_admin_page() {
	$results     = src_evaluate_checks();
	$critical    = array_filter( $results, fn( $r ) => ! $r['passed'] && 'critical' === $r['severity'] );
	$recommended = array_filter( $results, fn( $r ) => ! $r['passed'] && 'recommended' === $r['severity'] );
	$passed      = array_filter( $results, fn( $r ) => $r['passed'] );

	$total_checks  = count( $results );
	$failed_checks = count( $critical ) + count( $recommended );
	$all_clear     = 0 === $failed_checks && $total_checks > 0;
	$has_critical  = count( $critical ) > 0;

	$progress_percentage = $total_checks > 0 ? ( count( $passed ) / $total_checks ) : 1;
	$circumference       = 565.48;
	$stroke_offset       = $circumference * ( 1 - $progress_percentage );

	if ( $has_critical ) {
		$progress_class = 'red';
		$progress_label = __( 'Should be improved', 'src' );
	} elseif ( count( $recommended ) > 0 ) {
		$progress_class = 'orange';
		$progress_label = __( 'Good, with recommendations', 'src' );
	} else {
		$progress_class = 'green';
		$progress_label = __( 'Good', 'src' );
	}
	?>
	<div class="site-readiness-header">
		<div class="site-readiness-title-section">
			<h1><?php esc_html_e( 'Site Readiness Check', 'src' ); ?></h1>
		</div>
		<div class="site-readiness-title-section site-readiness-progress-wrapper hide-if-no-js <?php echo esc_attr( $progress_class ); ?>">
			<div class="site-readiness-progress">
				<svg aria-hidden="true" focusable="false" width="100%" height="100%" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg">
					<circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
					<circle id="bar" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0" style="stroke-dashoffset: <?php echo esc_attr( $stroke_offset ); ?>px;"></circle>
				</svg>
			</div>
			<div class="site-readiness-progress-label"><?php echo esc_html( $progress_label ); ?></div>
		</div>
	</div>

	<hr class="wp-header-end">

	<div class="site-readiness-body">
		<?php
		if ( 0 === $total_checks ) {
			?>
			<div class="site-status-all-clear">
				<p class="icon"><span class="dashicons dashicons-info" aria-hidden="true"></span></p>
				<p class="encouragement"><?php esc_html_e( 'No checks configured', 'src' ); ?></p>
				<p>
					<?php
					printf(
						'<a href="%s">%s</a>',
						esc_url( admin_url( 'options-general.php?page=src-settings' ) ),
						esc_html__( 'Add checks in the settings', 'src' )
					);
					?>
				</p>
			</div>
			<?php
		} elseif ( $all_clear ) {
			?>
			<div class="site-status-all-clear">
				<p class="icon"><span class="dashicons dashicons-smiley" aria-hidden="true"></span></p>
				<p class="encouragement"><?php esc_html_e( 'Great job!', 'src' ); ?></p>
				<p><?php esc_html_e( 'All checks have passed. Your site is ready to go.', 'src' ); ?></p>
			</div>
			<?php
		} else {
			?>
			<div class="site-status-has-issues">
				<h2><?php esc_html_e( 'Site Readiness Status', 'src' ); ?></h2>
				<p><?php esc_html_e( 'The site readiness check shows information about your WordPress configuration and items that need attention.', 'src' ); ?></p>

				<?php
				if ( ! empty( $critical ) ) {
					?>
					<div class="site-readiness-issues-wrapper" id="site-readiness-issues-critical">
						<h3 class="site-readiness-issue-count-title">
							<?php
							printf(
								/* translators: %d: number of critical issues */
								esc_html( _n( '%d critical issue', '%d critical issues', count( $critical ), 'src' ) ),
								count( $critical )
							);
							?>
						</h3>
						<p><?php esc_html_e( 'Critical issues are items that are essential to be fixed before you can publish your site.', 'src' ); ?></p>
						<div class="site-readiness-accordion issues">
							<?php
							foreach ( $critical as $result ) {
								src_render_check_result( $result );
							}
							?>
						</div>
					</div>
					<?php
				}

				if ( ! empty( $recommended ) ) {
					?>
					<div class="site-readiness-issues-wrapper" id="site-readiness-issues-recommended">
						<h3 class="site-readiness-issue-count-title">
							<?php
							printf(
								/* translators: %d: number of recommended improvements */
								esc_html( _n( '%d recommended improvement', '%d recommended improvements', count( $recommended ), 'src' ) ),
								count( $recommended )
							);
							?>
						</h3>
						<p><?php esc_html_e( 'Recommended items are improvements that are not essential but would benefit your site.', 'src' ); ?></p>
						<div class="site-readiness-accordion issues">
							<?php
							foreach ( $recommended as $result ) {
								src_render_check_result( $result );
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}

		if ( ! empty( $passed ) ) {
			?>
			<div class="site-readiness-passed">
				<button type="button" class="site-readiness-passed-toggle" aria-expanded="false">
					<?php
					printf(
						/* translators: %d: number of passed checks */
						esc_html( _n( '%d passed check', '%d passed checks', count( $passed ), 'src' ) ),
						count( $passed )
					);
					?>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
				<div class="site-readiness-accordion passed hidden">
					<?php
					foreach ( $passed as $result ) {
						src_render_check_result( $result );
					}
					?>
				</div>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Render a single check result row in the accordion.
 *
 * @param array $result Evaluation result for a single check.
 */
function src_render_check_result( $result ) {
	$icon_class = $result['passed'] ? 'good' : ( 'critical' === $result['severity'] ? 'critical' : 'recommended' );

	$label = ! empty( $result['label'] ) ? $result['label'] : $result['name'];

	$type_label = 'option' === $result['type']
		? __( 'Setting / Option', 'src' )
		: __( 'WP Config Constant', 'src' );

	$actual_display = null === $result['actual']
		? __( 'not set', 'src' )
		: (string) $result['actual'];

	$panel_id = 'src-panel-' . sanitize_title( $result['name'] );
	?>
	<div class="site-readiness-check-result <?php echo esc_attr( $icon_class ); ?>">
		<h4 class="site-readiness-check-heading">
			<button
				aria-expanded="false"
				class="site-readiness-check-trigger"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				type="button"
			>
				<span class="title"><?php echo esc_html( $label ); ?></span>
				<span class="badge"><?php echo esc_html( $type_label ); ?></span>
				<span class="icon"></span>
			</button>
		</h4>
		<div id="<?php echo esc_attr( $panel_id ); ?>" class="site-readiness-check-panel" hidden>
			<?php
			if ( ! $result['passed'] ) {
				?>
				<p>
					<?php
					printf(
						/* translators: 1: check name, 2: expected value, 3: actual value */
						esc_html__( '%1$s is expected to be %2$s but is currently %3$s.', 'src' ),
						'<code>' . esc_html( $result['name'] ) . '</code>',
						'<code>' . esc_html( $result['expected'] ) . '</code>',
						'<code>' . esc_html( $actual_display ) . '</code>'
					);
					?>
				</p>
				<?php
			} else {
				?>
				<p>
					<?php
					printf(
						/* translators: 1: check name, 2: actual value */
						esc_html__( '%1$s is correctly set to %2$s.', 'src' ),
						'<code>' . esc_html( $result['name'] ) . '</code>',
						'<code>' . esc_html( $actual_display ) . '</code>'
					);
					?>
				</p>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
