<!DOCTYPE html>
<body>
	<div>
		<form action="/broadcast">
			<textarea name="message"></textarea>
			<input type="submit" value="Send">
		</form>
		<script type="text/javascript" charset="utf-8">
		var form = document.querySelector('form'), messageField = document.querySelector('[name=message]');
		form.addEventListener('submit', function(e){
			e.preventDefault();
			if (messageField.value != "") {
				var x = new XMLHttpRequest();
				x.addEventListener('readystatechange', function(e){
					if (e.target.readyState == XMLHttpRequest.DONE) {
					};
				})
				x.open('POST', form.action);
				x.setRequestHeader( 'Content-Type', 'application/json' );
				x.send( JSON.stringify( {message:messageField.value}));
				messageField.value = "";
			};
		})
		</script>
	</div>
	<table border="0" cellspacing="5" cellpadding="5">
		<thead>
			<tr>
				<th scope="col">Token</th>
				<th scope="col">Time</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach( $tokens as $device => $time ): ?>
			<tr>
				<th scope="row"><?php echo $device ?></th>
				<td><?php echo $time ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</body>