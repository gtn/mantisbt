
// GTN
$(function() {
	var forms = $('#bug-change-status-form, #bugnoteadd');
	if (forms.length) {
		// only for but note add, and for change status form

		$(forms).find('input[name=time_tracking]').keyup(function(){
			if (this.value && !$(forms).find('textarea[name=bugnote_text]').val().trim() ) {
				// empty note and added time
				// -> flash the private button and turn private on!
				// so the customer won't see our empty time entree
				$(forms).find('input[name=private]').closest('tr').children().animate({
				    backgroundColor: "yellow"
	            });
				if (!$(forms).find('.gtn-note').length) {
					$(forms).find('input[name=private]').parent().append('<span class="gtn-note">, Notizen mit Zeit und ohne Text sind privat</span>');
				}
				$(forms).find('input[name=private]').prop('checked', true);
			}
		});
	}
});

// submit time tracking form
$(document).on('change', '#time_tracking [name=user_id], #time_tracking [name=project_id]', function(){
	$('#time_tracking').submit();
});

$(function(){
	// date picker for time tracking form
	if ($('#time_tracking').length) {
		var c = {
			changeMonth: true,
			changeYear: true,
			// numberOfMonths: 2,
			dateFormat: 'yy-mm-dd',
            onSelect: function(dateText) {
	            // change the select inputs
	            var date = dateText.split('-');
	            var selects = $(this).siblings('select');
	            $(selects[0]).val(parseInt(date[0]));
	            $(selects[1]).val(parseInt(date[1]));
	            $(selects[2]).val(parseInt(date[2]));
            }
		};

		// add date picker
		$('<input />').attr('readonly', 'true').insertBefore('select[name=start_year]').datepicker(c).val($('select[name^=start_]').map(function() { return this.value < 10 ? '0'+this.value : this.value; }).get().join('-'));
		$('<input />').attr('readonly', 'true').insertBefore('select[name=end_year]').datepicker(c).val($('select[name^=end_]').map(function() { return this.value < 10 ? '0'+this.value : this.value; }).get().join('-'));

		$('#time_tracking select[name^=start_], #time_tracking select[name^=end_]').hide();
	}

	// datetimepicker for bugnote form
	var $button = $('<input type="button" value="change time"/>');
	var $input = $('input[name=date_worked]'); // edit bugnote form
	if (!$input.length) {
		// try to find add note form
		var $time_tracking = $('form#bugnoteadd input[name=time_tracking]');
		if ($time_tracking.length) {
			$input = $('<input type="text" name="date_worked" />').insertAfter($time_tracking);
		}
	}
	if ($input.length) {
		function set_input() {
			var int = parseInt($input.val());
			if (int > 0) {
				$input.val(int);
				$button.val((new DateFormatter()).formatDate(new Date(int * 1000), 'Y-m-d H:i'));
			} else {
				$input.val('');
			}
		}
		set_input();

		$button.insertAfter($input);
		$button.click(function(){
			$input.datetimepicker('show');
		});
		$input.datetimepicker({
			format: 'U',
			onChangeDateTime: set_input,
			dayOfWeekStart: 1,
			closeOnDateSelect: true,
			step: 30,
		});
		// hide it
		// .hide() would not work with the date dialog
		$input.css({ width: 0, opacity: 0 });
	}
});

// csv export button
$(document).on('click', '#csv_export_button', function(){
	var $form = $(this).closest('form');
	document.location.href = 'billing_page.csv.php?'+$form.serialize();
});