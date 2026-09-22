<?php
/**
 * Admin Logs Template
 *
 * @package Samrat_Emily_Mail_Tracker
 *
 * @var array  $samrat_emily_mail_tracker_results      Current page of log rows.
 * @var int    $samrat_emily_mail_tracker_total_items  Total rows matching the current filter.
 * @var int    $samrat_emily_mail_tracker_total_pages  Total pages for the current filter.
 * @var int    $samrat_emily_mail_tracker_page         Current page number.
 * @var string $samrat_emily_mail_tracker_search       Current search term.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<div class="mail-log-wrapper">
		<div class="mail-log-content">
			<div class="mail-log-header">
				<h1 class="mail-log-title">
					<?php esc_html_e( 'Mail Logs', 'samrat-emily-mail-tracker' ); ?>
				</h1>

			<div class="stats">
				<?php esc_html_e( 'Total Captured Mails: ', 'samrat-emily-mail-tracker' ); ?><strong id="mail-log-total-count"><?php echo esc_html( $samrat_emily_mail_tracker_total_items ); ?></strong>
			</div>
		</div>

		<form method="get" class="mail-log-filters" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
			<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'samrat-emily-mail-tracker' ); ?>">
			<input type="search" name="s" class="form-control" placeholder="<?php esc_attr_e( 'Search recipient or subject...', 'samrat-emily-mail-tracker' ); ?>" value="<?php echo esc_attr( $samrat_emily_mail_tracker_search ); ?>" style="height: 35px; padding: 0 10px; min-width: 220px;">
			<button type="submit" class="btn-secondary"><?php esc_html_e( 'Search', 'samrat-emily-mail-tracker' ); ?></button>
			<?php if ( '' !== $samrat_emily_mail_tracker_search ) : ?>
				<a href="<?php echo esc_url( remove_query_arg( array( 's', 'paged' ) ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Reset', 'samrat-emily-mail-tracker' ); ?></a>
			<?php endif; ?>
		</form>

		<div class="mail-log-actions" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
			<select id="bulk-action-selector" class="form-control" style="max-width: 150px; height: 35px; padding: 0 10px;">
				<option value="-1"><?php esc_html_e( 'Bulk Actions', 'samrat-emily-mail-tracker' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'samrat-emily-mail-tracker' ); ?></option>
			</select>
			<button type="button" id="doaction" class="btn-secondary"><?php esc_html_e( 'Apply', 'samrat-emily-mail-tracker' ); ?></button>
		</div>

		<table class="mail-table">
			<thead>
				<tr>
					<th style="width: 40px;"><input type="checkbox" id="cb-select-all"></th>
					<th><?php esc_html_e( 'Date & Time', 'samrat-emily-mail-tracker' ); ?></th>
					<th><?php esc_html_e( 'Recipient', 'samrat-emily-mail-tracker' ); ?></th>
					<th><?php esc_html_e( 'Subject', 'samrat-emily-mail-tracker' ); ?></th>
					<th style="text-align: right;"><?php esc_html_e( 'Action', 'samrat-emily-mail-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $samrat_emily_mail_tracker_results ) : ?>
					<?php foreach ( $samrat_emily_mail_tracker_results as $samrat_emily_mail_tracker_row ) :
						$samrat_emily_mail_tracker_is_html = Samrat_Emily_Mail_Tracker::is_html_body( $samrat_emily_mail_tracker_row );
						?>
						<tr id="log-row-<?php echo esc_attr( $samrat_emily_mail_tracker_row->id ); ?>">
							<td><input type="checkbox" class="log-cb" value="<?php echo esc_attr( $samrat_emily_mail_tracker_row->id ); ?>"></td>
							<td style="font-size: 13px; color: #94a3b8;">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $samrat_emily_mail_tracker_row->created_at ) ) ); ?>
							</td>
							<td style="font-weight: 500; font-size: 14px;"><?php echo esc_html( $samrat_emily_mail_tracker_row->to_email ); ?></td>
							<td style="font-size: 14px;"><?php echo esc_html( $samrat_emily_mail_tracker_row->subject ); ?></td>
							<td style="text-align: right;">
								<div class="row-actions" style="display: flex; gap: 8px; justify-content: flex-end;">
									<button type="button" class="btn-view" data-id="<?php echo esc_attr( $samrat_emily_mail_tracker_row->id ); ?>">
										<?php esc_html_e( 'View', 'samrat-emily-mail-tracker' ); ?>
									</button>
									<button type="button" class="btn-delete-log" data-id="<?php echo esc_attr( $samrat_emily_mail_tracker_row->id ); ?>" style="background: #fee2e2; color: #dc2626; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px;">
										<span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin-top: 2px;"></span>
									</button>
								</div>

								<div id="ml-overlay-<?php echo esc_attr( $samrat_emily_mail_tracker_row->id ); ?>" class="ml-modal-overlay"></div>
								<div id="ml-content-<?php echo esc_attr( $samrat_emily_mail_tracker_row->id ); ?>" class="ml-modal-content">
									<div class="ml-modal-header">
										<h2 style="margin: 0; font-size: 18px; color: #1e293b;"><?php echo esc_html( $samrat_emily_mail_tracker_row->subject ); ?></h2>
										<button class="ml-close-modal">&times;</button>
									</div>
									<div class="ml-modal-body">
										<div style="background: #f8fafc; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px; line-height: 1.6; color: #475569; border: 1px solid #e2e8f0; text-align:left;">
											<div style="display: flex; margin-bottom: 4px;"><strong style="width: 80px;"><?php esc_html_e( 'To:', 'samrat-emily-mail-tracker' ); ?></strong> <?php echo esc_html( $samrat_emily_mail_tracker_row->to_email ); ?></div>
											<div style="display: flex;"><strong style="width: 80px;"><?php esc_html_e( 'Date:', 'samrat-emily-mail-tracker' ); ?></strong> <?php echo esc_html( $samrat_emily_mail_tracker_row->created_at ); ?></div>
										</div>
										<div class="mail-body-content" style="border: 1px solid #f1f5f9; padding: 20px; border-radius: 12px; background: #fff;">
											<?php
											if ( '' === $samrat_emily_mail_tracker_row->message ) {
												echo '<em>' . esc_html__( 'Body not stored (message logging is disabled).', 'samrat-emily-mail-tracker' ) . '</em>';
											} elseif ( $samrat_emily_mail_tracker_is_html ) {
												echo wp_kses_post( $samrat_emily_mail_tracker_row->message );
											} else {
												echo wp_kses_post( nl2br( esc_html( $samrat_emily_mail_tracker_row->message ) ) );
											}
											?>
										</div>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" style="padding: 60px; text-align: center; color: #94a3b8;">
							<span class="dashicons dashicons-email" style="font-size: 48px; width: 48px; height: 48px; opacity: 0.3; margin-bottom: 16px;"></span>
							<p style="margin: 0; font-size: 16px;"><?php esc_html_e( 'No email logs found yet.', 'samrat-emily-mail-tracker' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $samrat_emily_mail_tracker_total_pages > 1 ) : ?>
			<div class="pagination" style="margin-top: 30px; display: flex; gap: 8px; justify-content: center;">
				<?php
				echo wp_kses_post( paginate_links( array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'prev_text' => __( '&laquo;', 'samrat-emily-mail-tracker' ),
					'next_text' => __( '&raquo;', 'samrat-emily-mail-tracker' ),
					'total'     => $samrat_emily_mail_tracker_total_pages,
					'current'   => $samrat_emily_mail_tracker_page,
				) ) );
				?>
			</div>
		<?php endif; ?>
		</div>
	</div>
</div>
