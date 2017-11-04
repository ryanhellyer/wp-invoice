<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<?php wp_head(); ?>
</head>
<body>

<header>
	<div class="light-colour"><div class="block"></div></div>
	<div class="medium-colour"><div class="block"></div></div>
	<div class="dark-colour"><div class="block"></div><h1>Invoice <span>Ryan Hellyer</span></h1></div>
</header>

<main>

	<p class="box invoice-no">
		<em>Invoice number:</em>
		<?php echo esc_html( $data[ '_invoice_no' ] ); ?>
	</p>

	<p class="box invoice-to">
		<em>Invoice to:</em>
		<strong class="invoice-to-name"><?php echo esc_html( $client_name ); ?></strong>
		<span class="invoice-to-details"><?php

			if ( '' !== $client_description ) {
				$client_description = $client_description . "\n\n";
			} else {
				$client_description = '';
			}

			echo wp_kses_post( str_replace( "\n", '<br />', $client_description . $data[ '_invoice_to_details' ] ) );
		?></span>
		<a class="invoice-to-website" href="#"><?php echo esc_html( $website ); ?></a>
		</span>
	</p>

	<p class="box invoice-from">
		Tax identity number: 16/339/01057<br />
		Ryan Hellyer<br />
		Friedrichstraße 123<br />
		10117 Berlin, Deutschland<br />
		<br />
		<em>Total Due:</em>
		<strong class="total-amount"><?php echo esc_html( $data['_currency'] . round( $total_amount ) ); ?></strong>
	</p>

	<table>
		<thead>
			<tr>
				<th>Description</th>
				<th><?php

				$meta_data = get_post_meta( get_the_ID(), '_wp_invoice', true );
				if ( 'on' === $meta_data['_show_completed_date'] ) {
					echo 'Date';
				} else {
					echo 'Due date';
				}

				?></th>
				<th>Hours</th>
				<th>Amount</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="2"></td>
				<td>Total amount</td>
				<td class="total-amount"><?php echo esc_html( $data['_currency'] . round( $total_amount ) ); ?></td>
			</tr>
		</foot>
		<tbody><?php

// Outputting the tasks
foreach ( $data['_tasks'] as $key => $task ) {

	// Ensuring variables exist, to avoid debug errors
	foreach ( $this->possible_keys as $possible_key => $field ) {
		if ( ! isset( $task[ $possible_key ] ) ) {
			$task[ $possible_key ] = '';
		}

	}

	$meta_data = get_post_meta( get_the_ID(), '_wp_invoice', true );
	if ( 'on' === $meta_data['_show_completed_date'] ) {
		$date = $task['completed_date'];
	} else {
		$date = $data[ '_due_date' ];
	}

	echo '
			<tr>
				<td>
					<strong>' . esc_html( $task[ 'title' ] ) . '</strong>
					' . esc_html( $task[ 'description' ] ) . '
				</td>
				<td>' . esc_html( $date ) . '</td>
				<td>' . esc_html( $task[ 'hours' ] ) . '</td>
				<td>' . esc_html( $data['_currency'] . round( $task[ 'amount' ] ) ) . '</td>
			</tr>';
}


?>

		</tbody>
	</table>

	<p class="box bank-details">
		<strong>Bank details for direct deposit</strong>
		Berliner Sparkasse:<br />
		Ryan Hellyer<br />
		Account number: 1063737628<br />
		IBAN: DE 93 1005 0000 1063737628<br />
		BIC: BELADEBE<br />
		Address: Rankestraße 33­34, 10789 Berlin, Deutschland<br />
	</p>

	<p class="box note">
		<?php echo wp_kses_post( $data[ '_note' ] ); ?>
	</p>

</main>

<footer>
	<p>Thank your for your business!</p>
	<p>ryanhellyer@gmail.com | geek.hellyer.kiwi</p>
</footer>

<?php wp_footer(); ?>
</body>
</html>