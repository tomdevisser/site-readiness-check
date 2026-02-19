<?php
/**
 * Dashboard widget for Site Readiness Check.
 *
 * @package Site_Readiness_Check
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_dashboard_setup', 'src_register_dashboard_widget' );
add_action( 'admin_enqueue_scripts', 'src_enqueue_dashboard_styles' );

/**
 * Enqueue the dashboard widget stylesheet on the dashboard page.
 *
 * @param string $hook_suffix The current admin page hook suffix.
 */
function src_enqueue_dashboard_styles( $hook_suffix ) {
	if ( 'index.php' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'src-dashboard',
		SRC_PLUGIN_URI . 'assets/dashboard.css',
		array(),
		SRC_VERSION
	);
}

/**
 * Register the dashboard widget.
 */
function src_register_dashboard_widget() {
	wp_add_dashboard_widget(
		'src_site_readiness',
		__( 'Site Readiness Check', 'src' ),
		'src_render_dashboard_widget'
	);
}

/**
 * Render the dashboard widget content.
 */
function src_render_dashboard_widget() {
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

	$readiness_url = admin_url( 'tools.php?page=site-readiness-check' );
	?>
	<div class="site-readiness-widget">
		<div class="site-readiness-widget-title-section site-readiness-progress-wrapper hide-if-no-js <?php echo esc_attr( $progress_class ); ?>">
			<div class="site-readiness-progress">
				<svg aria-hidden="true" focusable="false" width="100%" height="100%" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg">
					<circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
					<circle id="bar" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0" style="stroke-dashoffset: <?php echo esc_attr( $stroke_offset ); ?>px;"></circle>
				</svg>
			</div>
			<div class="site-readiness-progress-label"><?php echo esc_html( $progress_label ); ?></div>
		</div>

		<div class="site-readiness-details">
			<?php
			if ( 0 === $total_checks ) {
				?>
				<p>
					<?php
					printf(
						'<a href="%s">%s</a>',
						esc_url( admin_url( 'options-general.php?page=src-settings' ) ),
						esc_html__( 'Configure checks in the settings.', 'src' )
					);
					?>
				</p>
				<?php
			} elseif ( $all_clear ) {
				?>
				<p><?php esc_html_e( 'All checks have passed. Your site is ready to go!', 'src' ); ?></p>
				<p>
					<?php
					printf(
						/* translators: %s: link to Site Readiness screen */
						esc_html__( 'Look at the passed checks on the %s.', 'src' ),
						'<a href="' . esc_url( $readiness_url ) . '">' . esc_html__( 'Site Readiness screen', 'src' ) . '</a>'
					);
					?>
				</p>
				<?php
			} else {
				?>
				<p>
					<?php
					if ( $has_critical && count( $recommended ) > 0 ) {
						printf(
							/* translators: 1: number of critical issues, 2: number of recommended improvements */
							esc_html__( 'Your site has %1$s and %2$s that need attention.', 'src' ),
							'<strong>' . sprintf(
								/* translators: %d: number of critical issues */
								esc_html( _n( '%d critical issue', '%d critical issues', count( $critical ), 'src' ) ),
								count( $critical )
							) . '</strong>',
							'<strong>' . sprintf(
								/* translators: %d: number of recommendations */
								esc_html( _n( '%d recommendation', '%d recommendations', count( $recommended ), 'src' ) ),
								count( $recommended )
							) . '</strong>'
						);
					} elseif ( $has_critical ) {
						printf(
							/* translators: %s: number of critical issues */
							esc_html__( 'Your site has %s that should be resolved before going live.', 'src' ),
							'<strong>' . sprintf(
								/* translators: %d: number of critical issues */
								esc_html( _n( '%d critical issue', '%d critical issues', count( $critical ), 'src' ) ),
								count( $critical )
							) . '</strong>'
						);
					} else {
						printf(
							/* translators: %s: number of recommendations */
							esc_html__( 'Your site is looking good, but there are %s to improve it further.', 'src' ),
							'<strong>' . sprintf(
								/* translators: %d: number of recommendations */
								esc_html( _n( '%d recommendation', '%d recommendations', count( $recommended ), 'src' ) ),
								count( $recommended )
							) . '</strong>'
						);
					}
					?>
				</p>
				<p>
					<?php
					printf(
						/* translators: 1: number of items, 2: link to Site Readiness screen */
						esc_html__( 'Take a look at the %1$s on the %2$s.', 'src' ),
						'<strong>' . sprintf(
							/* translators: %d: number of items */
							esc_html( _n( '%d item', '%d items', $failed_checks, 'src' ) ),
							$failed_checks
						) . '</strong>',
						'<a href="' . esc_url( $readiness_url ) . '">' . esc_html__( 'Site Readiness screen', 'src' ) . '</a>'
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
