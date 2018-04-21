<script type="text/javascript">
	(function () {
		var id = 'customfield' + {$id},
			el = $('#' + id);

		console.info(id, el);

		// Field not found
		if (!el.length) {
			return;
		}

		// Already listens
		if (el.attr('data-proxy-listener')) {
			return;
		}

		// Update price on change
		el.change(function() {
				updatesummary();
		});

		// Mark as processed
	  el.attr('data-proxy-listener', 1);
	})();
</script>