<?php
/**
 * Fee rules section view.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Current rules should already be available as $rules.
// Templates should already be available as $templates.
?>

<div class="cfwc-rules-section">
	
	<!-- Quick Preset Loader -->
	<div class="cfwc-preset-loader">
		<h3><?php esc_html_e( 'Quick Start with Presets', 'customs-fees-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Load presets to quickly configure common import scenarios:', 'customs-fees-for-woocommerce' ); ?></p>
		
		<select id="cfwc-preset-select">
			<option value=""><?php esc_html_e( '-- Select a preset --', 'customs-fees-for-woocommerce' ); ?></option>
			<?php if ( ! empty( $templates ) ) : ?>
				<?php foreach ( $templates as $template_id => $template ) : ?>
					<option value="<?php echo esc_attr( $template_id ); ?>">
						<?php echo esc_html( $template['name'] ); ?>
					</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		
		<button type="button" class="button button-primary cfwc-add-preset">
			<?php esc_html_e( 'Add Preset Rules', 'customs-fees-for-woocommerce' ); ?>
		</button>
		<button type="button" class="button cfwc-delete-all">
			<?php esc_html_e( 'Delete All Rules', 'customs-fees-for-woocommerce' ); ?>
		</button>
		
		<div id="cfwc-preset-description">
			<em></em>
		</div>
	</div>
	
	<hr>
	
	<!-- Rules Table -->
	<div class="cfwc-rules-header">
		<div>
			<h3><?php esc_html_e( 'All Rules', 'customs-fees-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Configure customs fee rules based on destination countries and product origins.', 'customs-fees-for-woocommerce' ); ?></p>
		</div>
		<button type="button" class="button cfwc-add-rule">
			<?php esc_html_e( 'Add New Rule', 'customs-fees-for-woocommerce' ); ?>
		</button>
	</div>
	
	<div class="cfwc-rules-table-wrapper">
		<table class="widefat fixed striped cfwc-rules-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Label', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Destination', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Origin Country', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Type', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Rate/Amount', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'customs-fees-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody id="cfwc-rules-tbody">
				<?php if ( ! empty( $rules ) ) : ?>
					<?php foreach ( $rules as $index => $rule ) : ?>
						<tr>
							<td><?php echo esc_html( $rule['label'] ?? '' ); ?></td>
							<td><?php echo esc_html( WC()->countries->countries[ $rule['country'] ] ?? $rule['country'] ); ?></td>
							<td>
								<?php 
								$origin = $rule['origin_country'] ?? '';
								if ( empty( $origin ) ) {
									esc_html_e( 'All Origins', 'customs-fees-for-woocommerce' );
								} elseif ( 'EU' === $origin ) {
									esc_html_e( 'EU Countries', 'customs-fees-for-woocommerce' );
								} else {
									echo esc_html( WC()->countries->countries[ $origin ] ?? $origin );
								}
								?>
							</td>
							<td><?php echo esc_html( ucfirst( $rule['type'] ) ); ?></td>
							<td>
								<?php
								if ( 'percentage' === $rule['type'] ) {
									echo esc_html( $rule['rate'] . '%' );
								} else {
									echo wp_kses_post( wc_price( $rule['amount'] ) );
								}
								?>
							</td>
							<td>
								<button type="button" class="button cfwc-edit-rule" data-index="<?php echo esc_attr( $index ); ?>">
									<?php esc_html_e( 'Edit', 'customs-fees-for-woocommerce' ); ?>
								</button>
								<button type="button" class="button cfwc-delete-rule" data-index="<?php echo esc_attr( $index ); ?>">
									<?php esc_html_e( 'Delete', 'customs-fees-for-woocommerce' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr class="no-rules">
						<td colspan="6"><?php esc_html_e( 'No rules configured. Use the preset loader above or add rules manually.', 'customs-fees-for-woocommerce' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div><!-- .cfwc-rules-table-wrapper -->
	
	<!-- Hidden input for rules data -->
	<input type="hidden" name="cfwc_rules" id="cfwc_rules" value='<?php echo esc_attr( wp_json_encode( $rules ) ); ?>' />
	<!-- Hidden input to trigger WooCommerce form change detection -->
	<input type="hidden" name="cfwc_rules_changed" id="cfwc_rules_changed" value="" />
	<?php wp_nonce_field( 'cfwc_save_rules', 'cfwc_rules_nonce' ); ?>
</div>