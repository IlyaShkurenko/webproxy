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

// Enable validator with extra validation rules
$(function() {
	var form = $('form[data-toggle=validator]');

	form.validator({
		custom: {
			'not-equals': function(el) {
				var notMatch = el.data('not-equals');
				if (el.val() == notMatch) {
					return "Value cannot be equal to " + notMatch;
				}
			}
		}
	});

	// Treat captcha as validated field
	if (form.find('.g-recaptcha').length) {
		var interval = setInterval(function() {
			if (!form.find('.g-recaptcha-response').length) return;

			// Captcha initialized
			interval = clearInterval(interval);
			var input = form.find('.g-recaptcha-response');

			// Workaround field change event ain't fired
			(function() {
				var filled = false;
				setInterval(function() {
					var field = form.find('#' + input.attr('id'));
					if (!field.attr('data-validate')) {
						field.attr('data-validate', 'true').attr('required', 'required');
						form.validator('update');
					}
					if (!field.val() && filled) {
						filled = false;
						field.change();
					}
					else if (field.val() && !filled) {
						filled = true;
						field.change();
					}
				}, 500);
			})()
		}, 250);
	}
});

// --- Definitions

var $checkoutForm = $('#proxy-form');
var $rowTemplate = $('.proxy-row.hide');
var $actionRow = $('.action-row');
var $total = $('#total');
var $submit = $("#submit");

$("#proxy-builder .add").on('click', function(event) {
    if (event) { event.preventDefault(); }
    $rowTemplate.clone().removeClass('hide').insertBefore($actionRow);
});
$rowTemplate.clone().removeClass('hide').insertBefore($actionRow);
$rowTemplate.remove();

// --- Handlers

// Sync country, category, min/max amounts
(function() {
	var getAvailable = function(country, category) {
		var available = [], products = getSharedVar('availableProducts');
		available.indexed = {};

		for (var i = 0; i < products.length; i++) {
			var product = products[i];

			var shouldPush = true;
			if (country && country != product.country) {
				shouldPush = false;
			}
			if (category && category != product.category) {
				shouldPush = false;
			}

			if (shouldPush) {
				available.push(product);
				available.indexed[product.country + product.category] = product;
			}
		}

		return available;
	};

	var unselectIfSelected = function(option) {
		if (option.prop('selected')) {
			option.attr('selected', false);
			try {
				option.closest('select').find('option:visible').not(':disabled').eq(0).prop('selected', true).change();
			}
			catch (e) {}
		}
	};

	var handler = function() {
		var country = $(this).closest('.row').find('select.country'),
			category = $(this).closest('.row').find('select.category'),
			amount = $(this).closest('.row').find('input.amount');

		var available = [];

		if (country.val()) {
			available = getAvailable(country.val());
			var availableCategories = [];

			for (var i = 0; i < available.length; i++) {
				var product = available[i];
				availableCategories.push(product.category);
			}

			// Hide some options
			category.find('option').each(function() {
				if (!$(this).val()) {}
				else if(~availableCategories.indexOf($(this).val())) {
					$(this).show();

					var product = available.indexed[country.val() + $(this).val()];

					// Disable if needed
					if (product && product.disabled) {
						$(this).prop('disabled', true);
						unselectIfSelected($(this));
					}
					else {
						$(this).prop('disabled', false);
					}
				}
				else {
					$(this).hide();
					// Un-select if something chosen
					unselectIfSelected($(this));
				}
			});
		}

		if (category.val()) {
			available = getAvailable(country.val(), category.val());

			// More than 1 option available, skip validation
			if (available.length > 1) {
				amount.prop('min', null);
				amount.prop('max', null);
				amount.prop('step', null);
			}
			else {
				// Set proper validation
				amount.prop('min', available[0].amount.min);
				amount.prop('max', available[0].amount.max);
				amount.prop('step', available[0].amount.step);

				// Revalidate the value
				if (amount.val()) {
					amount.change();
				}
			}
		}
	};

	$(document).on('change', '#proxy-builder select.country', handler);
	$(document).on('change', '#proxy-builder select.category', handler);
	// Initialize
	setTimeout(function() {
		(function(context) {
			var bought = getSharedVar('boughtProducts');
			// Do not select already bought product
			if (bought) {
				var country = context.closest('.row').find('select.country'),
					category = context.closest('.row').find('select.category');

				if (country.val() && category.val()) {
					for (var i = 0; i < bought.length; i++) {
						var product = bought[i];

						// Same product chosen, handle it
						if (product.country == country.val() && product.category == category.val()) {
							category.prepend('<option value=""></option>');
							unselectIfSelected(category.find('option:selected'));
						}
					}
				}
			}

			handler.call(context);
		})($('#proxy-builder select.country'));
	}, 150);
})();

// Set total for proxy
(function() {
	// To be used by validator
	var form = $('#proxy-builder').find('select').closest('form');
	if (!form[0]) return;

	var priceValidatorInput = $('<input type="text" class="valid-price hide" required>');
	form.find('select').eq(0).closest('.form-group').after(priceValidatorInput);

	var handler = function() {
		// Form is invalid unless price received
		priceValidatorInput.val(null).change();

		// Check if fields filled
		var filled = true;
		form.find('select, input[type=number]').each(function() {
			if (!$(this).val()) {
				filled = false;
			}
		});
		if (!filled) {
			return;
		}

		if (lastTimeout) {
			clearTimeout(lastTimeout);
		}
		
		if (lastRequest && 'resolved' != lastRequest.state()) {
			lastRequest.abort();
		}

		lastTimeout = setTimeout(function() {
			lastRequest = $.ajax($config.routing.checkTotal, {
				method: "POST",
				data: $checkoutForm.serialize(),
				dataType: 'json',
				success: function(data) {
					var discountEl = form.find('.discount-total');

					var total = data.total + 0;
					$total.text("$" + total.toFixed(2));

					if (total > 0) {
						priceValidatorInput.val(1).change();
					}

					if (data.discount) {
						discountEl.text('$' + (total - data.discount).toFixed(2)).show().removeClass('hide');
						$total.addClass('line-through');
					}
					else {
						discountEl.addClass('hide');
						$total.removeClass('line-through');
					}

					var promoInput = form.find('.promocode input');
					if (promoInput.val().trim()) {
						if (data.promoValid !== undefined) {
							if (!data.promoValid) {
								promoInput.removeClass('bg-success').addClass('bg-danger');
							}
							else {
								promoInput.removeClass('bg-danger').addClass('bg-success');
							}
						}
					}
				}
			});
		}, 500);
	}, lastRequest, lastTimeout;

	$(document).on('change keyup', '#proxy-builder select, #proxy-builder input[type=number]', function(e) {
		if (e.isDefaultPrevented()) {
			return;
		}

		handler();
	});

	// Initialize
	setTimeout(handler, 250);
})();

// Disclaimer
(function() {
	var wrapper = $('#proxy-builder');

	if (!wrapper.length) return;

	var handler = function() {
		var category = wrapper.find('select.category:visible'),
			selected = category.find('option:selected'),
			country = wrapper.find('select.country:visible').val();

		if (selected.attr('data-notice-' + country)) {
			bootbox.alert({
				message: selected.attr('data-notice-' + country),
				size: 'small',
				buttons: {
					ok: {
						className: 'btn-warning'
					}
				}
			});
		}

		if (selected.attr('data-disclaimer-' + country)) {
			wrapper.find('.disclaimer').html(selected.attr('data-disclaimer-' + country)).removeClass('hide').show();
		}
		else {
			wrapper.find('.disclaimer').hide();
		}

		if (lastCheckbox) {
			lastCheckbox.slideUp();
			lastCheckbox.find('[type=checkbox]').prop('required', false);
			lastCheckbox = null;
		}
		if (selected.attr('data-checkbox-required-' + country)) {
			var checkbox = wrapper.find(selected.attr('data-checkbox-required-' + country));
			if (checkbox) {
				checkbox.hide().removeClass('hide').slideDown();
				checkbox.find('[type=checkbox]').prop('required', true);
				lastCheckbox = checkbox;
			}
		}
	}, lastCheckbox;

	wrapper.find('select.category').change(callbackWithDelay(100, handler));
	wrapper.find('select.country').change(callbackWithDelay(100, handler));
	// Initialize
	setTimeout(handler, 250);
})();

// Migrate package
$(function() {
	var migrateBtn = $('.page-checkout .btn-migrate');

	migrateBtn.click(function() {
		var self = $(this);

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
					spinner.show();
					$.getJSON(self.attr('data-url'), {}, function(response) {
						spinner.hide();

						var message = '';
						if (response.errors && response.errors.length) {
							for (var i = 0; i < response.errors.length; i++) {
								message += '<div class="alert alert-danger"><h4>' + response.errors[i] + '</h4></div>'
							}
						}
						if (response.info && response.info.length) {
							for (var i = 0; i < response.info.length; i++) {
								message += '<div class="alert alert-success"><h4>' + response.info[i] + '</h4></div>'
							}
						}

						bootbox.alert({
							title: self.attr('data-title'),
							message: message,
							size: 'small',
							callback: function() {
								if ('ok' == response.status) {
									eval('var cb = ' + self.attr('data-callback'));
									//noinspection JSUnresolvedVariable,JSUnresolvedFunction
									cb.call(self, self);
								}
							}
						});
					});
				}
			}
		});

		return false;
	});

	setTimeout(function() {
		for (var i = 0; i < migrateBtn.length; i++) {
			if (migrateBtn.eq(i).attr('data-autorun')) {
					migrateBtn.eq(i).click();
				break;
			}
		}
	}, 250)
});

// --- Manage existent proxies

(function() {
	var handler = function() {
		var $element = $(this);
		var country = $element.data('country');
		var category = $element.data('category');

		var $form = $('form.' + country + '_' + category);
		var $locationSave = $('.location_save', $form);
		$('.location-settings-message', $form).addClass('hide');

		var total = 0, valid = true;
		var ports = parseInt($('#' + country + '_' + category + '_total').data('total'));
		$('.region[data-country=' + country + '][data-category=' + category + ']').each(function(idx, element) {
			var value = parseInt(element.value);
			if (value > 0) {
				total += value;
			}
			else if (value < 0) {
				valid = false;
			}
		});

		var need = ports - total;
		$('#' + country + '_' + category + '_total').text(need);
		if (need > 0 || !valid) {
			$locationSave.addClass('disabled');
			$('.less', $form).addClass('hide');
			$('.more', $form).removeClass('hide');
			$('.good', $form).addClass('hide');
		} else if (need < 0) {
			$locationSave.addClass('disabled');
			$('.less', $form).removeClass('hide');
			$('.more', $form).addClass('hide');
			$('.good', $form).addClass('hide');
		} else if (need == 0) {
			$locationSave.removeClass('disabled');
			$('.less', $form).addClass('hide');
			$('.more', $form).addClass('hide');
			$('.good', $form).removeClass('hide');
		}
	}
	$("body").on("input", "input.region", handler);
	$("body").on("change", "input.region", handler);
})();
$("input.region").trigger('input');

$("body").on("submit", ".location_form", function(e) {
    e.preventDefault();
    var $form = $(this);
    if ($form.find('.location_save.disabled').length) {
    	return;
	}
    $.ajax($config.routing.saveLocations, {
    	method:'POST',
		dataType: 'json',
		data: $form.serialize(),
		success: function(response) {
			$('.location-settings-message', $form).removeClass('hide');

			if (response.redirectUrl) {
				window.location = response.redirectUrl;
			}
		}});
});

// Set auth format
$('input.format').on("click", function() {
    var $ele = $(this);
    var format = $ele.val();
    $.getJSON($config.routing.saveFormat.replace('_format_', format), function(response) {
        if (format == "PW") {
            $("#PW-AUTH").removeClass('hide');
            if ($ele.data('hasRotate')) {
                $("#IP-AUTH").removeClass('hide');
            } else {
                $("#IP-AUTH").addClass('hide');
            }
        } else if (format == "IP") {
            $("#IP-AUTH").removeClass('hide');
            $("#PW-AUTH").addClass('hide');
        }

    });
});

// Add Auth New IP
$(".add-new-ip").on("click", function(){
    var status = $(this);
    var data = {}, ip = $(this).parent().parent().find(".new-ip").val();

    // Empty field
	if (!ip.replace(/\s+/g, '').length) {
		return;
	}

    $(status).addClass("whirl").addClass("back-and-forth").addClass("line");

    $.ajax($config.routing.addIp.replace('_ip_', ip), {
    	method:'GET',
		dataType: 'json',
		data: data,
		success: function(resp) {
			if (resp.status === 'success') {
				var template = $(".add-new-ip").closest("table").find('tr.template'), html = '';
				if (template.length) {
					html = template[0].outerHTML;
					html = html.replace(/\{ipId}/g, resp.ip.id).replace(/\{ip}/, resp.ip.ip);
					html = $(html).removeClass('template').removeClass('hide');
				}
				else {
					html = '<tr data-ip-id="' + resp.ip.id + '">';
					html += '<td>' + resp.ip.ip + '</td>';
					html += '<td>';
					html += '<button type="button" class="btn btn-labeled btn-danger remove-ip" data-ip-id="' + resp.ip.id + '">';
					html += '<span class="btn-label"><i class="fa fa-times"></i></span>Remove';
					html += '</button>';
					html += '<span class="status"></span>';
					html += '</td>';
					html += '</tr>';
				}

				// Add new record
				$(".add-new-ip").closest("tr").before(html);
				// Clean up ip input
				$('.use-current-ip').parent().find(".new-ip").val('')
			}
			else {
				alert("Error adding IP -- " + resp.message);
			}
			$(status).removeClass("whirl").removeClass("back-and-forth").removeClass("line");
		}});
});

// Remove Auth IP
$("body").on("click", ".remove-ip", function(){
    var status = $(this);
    $(status).addClass("whirl").addClass("back-and-forth").addClass("line");

    var data = {}, ip = $(this).attr("data-ip-id");

    $.ajax($config.routing.removeIp.replace('_id_', ip), {
    	method:'GET',
		dataType: 'json',
		data: ip,
		success: function(resp) {
			if (resp.status === 'success') {
				$(status).closest("tr").remove();
			}
			else {
				$(status).removeClass("whirl").removeClass("back-and-forth").removeClass("line");
			}
		}});
});

// Settings: rotation
$('.rotation_type, .rotate_option').on('change', function(e) {
    var data = {};

    switch ($(this).prop('name')) {
        case 'rotation_type':
            data.rotationType = $(this).val();
            break;

        case 'rotate_ever':
            data.rotateEver = $(this).prop('checked') ? 1 : 0;
            break;

        case 'rotate_30':
            data.rotate30 = $(this).prop('checked') ? 1 : 0;
            break;

        default:
            return;
    }

    $.ajax($config.routing.saveSettings, {
    	method:'POST',
		dataType: 'json',
		data: data,
		success: function(resp) {
			if (resp.status !== 'success') {
				alert('Error occured, settings have not saved!');
			}
		}
    });
});

// Use My AIP
$("body").on("click", ".use-current-ip", function(){
    $(this).parent().find(".new-ip").val($(this).attr("data-ip"));
});

// Set rotation time
$('body').on('change', '.rotation-time', function(e) {
	var status = $(this);
	$(status).closest("tr").find(".status").text(null);

	$.ajax($config.routing.setRotationTime, {
		method:'POST',
		dataType: 'json',
		data: {
			'time' : $(this).val(),
			'portId' : $(this).attr("data-port-id")
		},
		success: function(resp){
		if(resp.status != 'success'){
			$(status).closest("tr").find(".status").text(resp.message || "Error!");
		}
	}});
});

spinner = {
    show: function() {
		var container = spinner.constructContainer().addClass('spinner');
		$('body').prepend(container);
    },
    hide: function() {
        $('body > .spinner').remove();
    },
	constructContainer: function() {
		var container = $('<div class="spinner">');
		container.css({
			'width': '100%',
			'height': '100%',
			'position': 'fixed',
			'z-index': 2000,
			'background': '#F5F7FA',
			'opacity': .8
		});

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

		return container;
	}
};

// --- Quick Buy

// Proxy plan tab title, button (enable/disable)
(function() {
    var page = $('.page-quick_buy, .page-do_quick_buy'),
        tab = page.find('.panel-form-plan'),
		total = tab.find('#total'),
        inputs = tab.find('select');

    if (!inputs.length) return;

	// To be used by validator
	var input = $('<input type="text" class="hide" required>');
	total.after(input);

    var handler = function(enable) {
		var header = tab.find('.panel-title a');
		if (enable) {
			tab.find('.btn-continue').removeClass('disabled').trigger('btn:status', [true]);
			header.text(header.attr('data-text-plan')
				.replace('{amount}', tab.find('input[name*=amount]').val())
				.replace('{country}', tab.find('select[name*=country] option:selected').text())
				.replace('{category}', tab.find('select[name*=category] option:selected').text())
			);
		}
		else {
			tab.find('.btn-continue').addClass('disabled').trigger('btn:status', [false]);
			header.text(header.attr('data-text-empty'));
			input.val(null);
		}

		// Update value only if something changed
		if (!!input.val() != enable) {
			input.val(enable ? 1 : null);
			input.change();
		}
	};

    // Check the value
    tab.find('.valid-price').change(function() {
		handler(!!$(this).val());
	});


	// Add category-country to email validator callback
	(function() {
		var inputEmail = page.find('#email');
		tab.find('select.country').change(function() {
			inputEmail.attr('data-remote', inputEmail.attr('data-remote')
				.replace(/(country=)[^&]+/, '$1' + $(this).val()));
		});
		tab.find('select.category').change(function() {
			inputEmail.attr('data-remote', inputEmail.attr('data-remote')
				.replace(/(category=)[^&]+/, '$1' + $(this).val()));
		});
	})();
})();

// Click on continue button
$('.page-quick_buy, .page-do_quick_buy').find('.panel-form-plan').find('.btn-continue.btn-continue-step1').click(function() {
    var self = $(this);

    if (!self.hasClass('disabled')) {
		self.closest('.panel-collapse').collapse('hide');
		$(self.attr('href')).collapse('show');
    }

	return false;
});

// Promocode check
$(function() {
	var wrapper = $('.page-checkout, .page-quick_buy, .page-do_quick_buy').find('.promocode'),
		btn = wrapper.find('.btn'),
		input = wrapper.find('input');

	var updateTotal = function() {
		var amountField = wrapper.closest('form').find('.field-amount');
		if (amountField.val()) {
			amountField.change();
		}
	};

	btn.click(function() {
		// Code passed
		if (input.val()) {
			updateTotal();
		}

		return false;
	});
	input.keyup(function() {
		// Background color revert to initial state
		setTimeout(function() {
			if (!input.val()) {
				input.removeClass('bg-success bg-danger');
				updateTotal();
			}
		}, 100);
	});

});
