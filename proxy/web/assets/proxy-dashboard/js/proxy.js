// --- Common functions

function stringToFunction(str) {
	eval('var cb = ' + str);

	//noinspection JSUnresolvedVariable
	return cb;
}

function getSharedVar(key, def) {
	if (undefined === def) {
		def = false;
	}

	// Initialize cache
	if (undefined === this.$data) {
		this.$data = {};
	}

	// Use cache if possible
	if (undefined !== this.$data[key]) {
		return this.$data[key];
	}

	// Return default value, if key not found
	var container = $("#" + key);
	if (!container.length) {
		return def;
	}

	return this.$data[key] = $.parseJSON(container.html());
}

function callbackWithDelay(delay, cb) {
	return function() {
		setTimeout(cb.bind(this), delay);
	};
}

// --- Common handlers

$(document).on('click', '.btn-confirm', function() {
	var self = $(this);

	if (!self.attr('data-confirmed')) {
		if (self.attr('disabled') || self.hasClass('disabled')) {
			return false;
		}

		bootbox.confirm({
			title: self.attr('data-title'),
			message: self.attr('data-text-callback') ?
				stringToFunction(self.attr('data-text-callback')).call(self, self) :
				self.attr('data-text') ?
					self.attr('data-text') :
					'Are you sure?',
			backdrop: true,
			size: 'small',
			buttons: {
				confirm: {
					className: self.attr('data-btn-ok-class') || 'btn-danger'
				}
			},
			callback: function(confirmed) {
				if (confirmed) {
					// Callback
					if (self.attr('data-callback')) {
						stringToFunction(self.attr('data-callback')).call(self, self);
					}
					// Or execute handlers
					else {
						self.attr('data-confirmed', 1);
						self.click();
						if (self.attr('href')) {
							window.location = self.attr('href');
						}
						self.attr('data-confirmed', 0)
					}
				}
			}
		});

		return false;
	}
});

// Show spinner
$(document).on('submit', '.active-spinner', function(e) {
	if (!e.isDefaultPrevented()) {
		spinner.show();
	}
});
$(document).on('click', '.btn-active-spinner', function(e) {
	if (!e.isDefaultPrevented()) {
		spinner.show();
	}
});

// Extra validation rules
$(function() {
	$('form[data-toggle=validator]').validator({
		custom: {
			'not-equals': function(el) {
				var notMatch = el.data('not-equals');
				if (el.val() == notMatch) {
					return "Value must be no equal " + notMatch;
				}
			}
		}
	});
});

spinner = {
    show: function() {
		var container = $('<div class="spinner">');
		container.css({
		    'width': '100%',
            'height': '100%',
            'position': 'fixed',
            'z-index': 2000,
            'background': '#F5F7FA',
            'opacity': .8
        });
		$('body').prepend(container);
		container.spinner('wave', {
			'bars': '6',
			'height': '80px',
			'width': '12px',
			'spacing': '1px',
			'speed': '70',
			'opacity': '1',
			'color': '#35BDE7'
		});
		container.find('.spinner-bars').css('height', '100%');
    },
    hide: function() {
        $('body > .spinner').remove();
    }
};