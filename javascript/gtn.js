
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

// date picker
$(function(){
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
});