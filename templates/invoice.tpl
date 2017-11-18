<!DOCTYPE html>
<html lang="en-NZ" prefix="og: http://ogp.me/ns#">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width" />
	<title>Invoicing</title>
	<style>
		{css}
	</style>
</head>
<body>

<header>
	<div class="light-colour"><div class="block"></div></div>
	<div class="medium-colour"><div class="block"></div></div>
	<div class="dark-colour"><div class="block"></div><h1>Invoice <span>{display_name}</span></h1></div>
</header>

<main>

	<p class="box invoice-no">
		<em>Invoice number:</em>
		{invoice_number}
	</p>

	<p class="box invoice-to">
		<em>Invoice to:</em>
		<strong class="invoice-to-name">{client_name}</strong>
		<span class="invoice-to-details">
			<br />
			{client_description}
		</span>
		<a class="invoice-to-website" href="{client_website}">{client_domain}</a>
		</span>
	</p>

	<p class="box invoice-from">
		{invoice_from}
		<br />
		<em>Total Due:</em>
		<strong class="total-amount">{total_amount}</strong>
	</p>

	<table>
		<thead>
			<tr>
				<th>Description</th>
				<th>Start</th>
				<th>End</th>
				<th>Hours</th>
				<th>Amount</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="3"></td>
				<td>Total</td>
				<td class="total-amount">{total_amount}</td>
			</tr>
		</foot>
		<tbody>
		{entries}

			<tr>
				<td>{entry_title}</td>
				<td>{entry_start_date}</td>
				<td>{entry_end_date}</td>
				<td>{entry_hours}</td>
				<td>{entry_amount}</td>
			</tr>

		{/entries}
		</tbody>
	</table>

	<p class="box bank-details">
		<strong>Bank details for direct deposit</strong>
		{bank_details}
	</p>

	<p class="box note">
		{note}
	</p>

</main>

<footer>
	<p>{footer_comment_1}</p>
	<p>{footer_comment_2}</p>
</footer>

</body>
</html>