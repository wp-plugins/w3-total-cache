function w3tc_popup(url, name)
{
	return window.open(url, name, 'width=800,height=600,status=no,toolbar=no,menubar=no,scrollbars=yes');
}

jQuery(function($) {
	// general page
	$('.enabled').click(function() {
		var checked = false;
		$('.enabled').each(function() {
			if (this.checked) {
				checked = true;
			}
		});
		$('#enabled').each(function() {
			this.checked = checked;
		});
	});

	$('#enabled').click(function() {
		var checked = this.checked;
		$('.enabled').each(function() {
			this.checked = checked;
		});
	});

	// minify page
	function input_enable(input, enabled)
	{
		$(input).each(function() {
			this.disabled = ! enabled;
			if (enabled) {
				$(this).next(':hidden').remove();
			} else {
				var me = $(this);
				me.after($('<input />').attr({
					type: 'hidden',
					name: me.attr('name'),
				}).val(me.val()));
			}
		});
	}
	
	input_enable('.html_enabled', $('#html_enabled:checked').size());
	input_enable('.js_enabled', $('#js_enabled:checked').size());
	input_enable('.css_enabled', $('#css_enabled:checked').size());

	$('#html_enabled').click(function() {
		input_enable('.html_enabled', this.checked);
	});
	
	function js_enabled() 
	{
		$('#js_enabled').click(function() {
			input_enable('.js_enabled', this.checked);
		});
	}
	
	js_enabled();	

	function css_enabled() 
	{
		$('#css_enabled').click(function() {
			input_enable('.css_enabled', this.checked);
		});
	}
	
	css_enabled();	
	
	function js_file_delete()
	{
    	$('.js_file_delete').click(function() {
    		if (confirm('Are you sure you want to delete JS file?')) {
    			$(this).parent().remove();
    			if (! $('#js_files li').size()) {
    				$('#js_files').after('<div id="js_files_empty" class="w3tc-empty">No JS files added<\/div>').remove();
    			}
    		}
    		
    		return false;
    	});
	}

	js_file_delete();
	
	function file_verify()
	{
		$('.js_file_verify,.css_file_verify').click(function() {
			var file = $(this).parent().find(':text').val();
			if (file == '') {
				alert('Empty file');
			} else {
				var url = '';
				if (/^https?:\/\//.test(file)) {
					url = file;
				} else {
					url = '/' + file;
				}
				w3tc_popup(url, 'file_verify');
			}
		});
	}
	
	file_verify();
	
	function js_file_location_change()
	{
		$('.js_file_location').change(function() {
			$(this).parent().find(':text').attr('name', 'js_files_' + $(this).val() + '[]');
		});
	}
	
	js_file_location_change();

	$('#js_file_add').click(function() {
		if ($('#js_files_empty').size()) {
			$('#js_files_empty').after('<ol id="js_files"><\/ol>').remove();
		}
		$('#js_files').append('<li><input class="js_enabled" type="text" name="js_files_include[]" value="" size="100" \/>&nbsp;<select class="js_file_location js_enabled"><option value="include">Header</option><option value="include-nb">Header (non-blocking)</option><option value="include-footer">Footer</option><option value="include-footer-nb">Footer (non-blocking)</option></select>&nbsp;<input class="js_file_delete js_enabled button" type="button" value="Delete" />&nbsp;<input class="js_file_verify js_enabled button" type="button" value="Verify URI" /><\/li>');
		js_file_delete();
		file_verify();
		js_enabled();
		js_file_location_change();
	});
	
	function css_file_delete() 
	{
    	$('.css_file_delete').click(function() {
    		if (confirm('Are you sure you want to delete CSS file?')) {
    			$(this).parent().remove();
    			if (! $('#css_files li').size()) {
    				$('#css_files').after('<div id="css_files_empty" class="w3tc-empty">No CSS files added<\/div>').remove();
    			}
    		}
    		
    		return false;
    	});
	}

	css_file_delete();

	$('#css_file_add').click(function() {
		if ($('#css_files_empty').size()) {
			$('#css_files_empty').after('<ol id="css_files"><\/ol>').remove();
		}
		$('#css_files').append('<li><input class="css_enabled" type="text" name="css_files_include[]" value="" size="100" \/>&nbsp;<input class="css_file_delete css_enabled button" type="button" value="Delete" />&nbsp;<input class="css_file_verify css_enabled button" type="button" value="Verify URI" /><\/li>');
		css_file_delete();
		file_verify();
		css_enabled();
	});
	
	$('#minify_form').submit(function() {
		var invalid_js = [], invalid_css = [];
		$('#js_files :text').each(function() {
			var v = $(this).val();
			if (v != '' && ! /\.js$/.test(v)) {
				invalid_js.push(v);
			}
		});
		$('#css_files :text').each(function() {
			var v = $(this).val();
			if (v != '' && ! /\.css$/.test(v)) {
				invalid_css.push(v);
			}
		});
		
		if (invalid_js.length && ! confirm('These files have invalid JS file extension:\r\n\r\n' + invalid_js.join('\r\n') + '\r\n\r\nAre you confident this files contain valid JS code?')) {
			return false;
		}
		
		if (invalid_css.length && ! confirm('These files have invalid CSS file extension:\r\n\r\n' + invalid_css.join('\r\n') + '\r\n\r\nAre you confident this files contain valid CSS code?')) {
			return false;
		}
		
		return true;
	});
	
	// CDN
	$('.w3tc-tab').click(function() {
		$('.w3tc-tab-content').hide();
		$(this.rel).show();
	});
	
	$('#cdn_export_library').click(function() {
		w3tc_popup('options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=cdn_export_library', 'cdn_export_library');
	});
	
	$('#cdn_queue').click(function() {
		w3tc_popup('options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=cdn_queue', 'cdn_queue');
	});
	
	$('.cdn_export').click(function() {
		w3tc_popup('options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=cdn_export&cdn_export_type=' + this.name, 'cdn_export_' + this.name);
	});
	
	$('#test_ftp').click(function() {
		var status = $('#test_ftp_status');
		status.removeClass('w3tc-error');
		status.addClass('w3tc-process');
		status.html('Testing...');
		$.post('options-general.php', {
			page: 'w3-total-cache/w3-total-cache.php',
			w3tc_action: 'cdn_test_ftp',
			host: $('#cdn_ftp_host').val(),
			user: $('#cdn_ftp_user').val(),
			path: $('#cdn_ftp_path').val(),
			pass: $('#cdn_ftp_pass').val()
		}, function(data) {
			status.addClass(data.result ? 'w3tc-success' : 'w3tc-error');
			status.html(data.error);
		}, 'json');
	});
});
