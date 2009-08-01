var Cdn_Export_File = {
	paused: 0,
	limit: 25,
	offset: 0,
	files: [],
	upload_files: [],
	
	set_progress: function(percent)
	{
		jQuery('#cdn_export_file_progress .progress-bar').width(percent + '%');
		jQuery('#cdn_export_file_progress .progress-value').html(percent + '%');
	},
	
	set_status: function(status)
	{
		jQuery('#cdn_export_file_status').html(status);
	},
	
	set_processed: function(processed)
	{
		jQuery('#cdn_export_file_processed').html(processed);
	},
	
	set_button_text: function(text)
	{
		jQuery('#cdn_export_file_start').val(text);
	},
	
	log: function(path, result, error)
	{
		jQuery('#cdn_export_file_log').prepend('<div class="log-' + (result == 1 ? 'success' : 'error') + '">' + path + ' <strong>' + error + '</strong></div>');
	},
	
	process: function()
	{
		if (this.paused) {
			return;
		}
		
		this.upload_files = [];
		
		for (var i = this.offset, l = this.files.length, j = 0; i < l && j < this.limit; i++, j++) {
			this.upload_files.push(this.files[i]);
		}
		
		var me = this;
		if (this.upload_files.length) {
			jQuery.post('options-general.php', {
				page:			'w3-total-cache/w3-total-cache.php',
				w3tc_action:	'cdn_export_process',
				'files[]':		this.upload_files
			}, function(data) {
				me.process_callback(data);
			}, 'json');
		}
	},

	process_callback: function(data)
	{
		var failed = false;
		for (var i = 0; i < data.results.length; i++) {
			this.log(data.results[i].remote_path, data.results[i].result, data.results[i].error);
			if (data.results[i].result == -1) {
				failed = true;
				break;
			}
		}
		
		if (failed) {
			this.offset = 0;
			this.set_progress(0);
			this.set_processed(1);
			this.set_status('failed');
			this.set_button_text('Start');
		} else {
			this.offset += this.upload_files.length;
			this.set_progress((this.offset * 100 / files.length).toFixed(0));
			this.set_processed(this.offset);
			
			if (this.offset < this.files.length) {
				this.process();
			} else {
				this.offset = 0;
				this.set_status('done');
				this.set_button_text('Start');
			}
		}
	},

	init: function(files) {
		if (files === undefined) {
			files = [];
		}
		
		this.files = files;
		
		var me = this;
		jQuery('#cdn_export_file_start').click(function() {
			if (this.value == 'Pause') {
				me.paused = 1;
				me.set_button_text('Resume');
				me.set_status('paused');
			} else {
				me.paused = 0;
				me.set_button_text('Pause');	
				me.set_status('processing');
			}
			
			me.process();
		});
	}
}

var Cdn_Export_Table = {
	action: '',
	paused: 0,
	limit: 25,
	offset: 0,
	
	set_progress: function(percent)
	{
		jQuery('#cdn_export_table_progress .progress-bar').width(percent + '%');
		jQuery('#cdn_export_table_progress .progress-value').html(percent + '%');
	},
	
	set_status: function(status)
	{
		jQuery('#cdn_export_table_status').html(status);
	},
	
	set_processed: function(processed)
	{
		jQuery('#cdn_export_table_processed').html(processed);
	},
	
	set_total: function(total)
	{
		jQuery('#cdn_export_table_total').html(total);		
	},
	
	set_button_text: function(text)
	{
		jQuery('#cdn_export_table_start').val(text);
	},
	
	log: function(path, result, error)
	{
		jQuery('#cdn_export_table_log').prepend('<div class="log-' + (result == 1 ? 'success' : 'error') + '">' + path + ' <strong>' + error + '</strong></div>');
	},
	
	process: function()
	{
		if (this.paused) {
			return;
		}
		
		var me = this;
		jQuery.post('options-general.php', {
			page:			'w3-total-cache/w3-total-cache.php',
			w3tc_action:	this.action,
			limit: 			this.limit,
			offset:			this.offset
		}, function(data) {
			me.process_callback(data);
		}, 'json');		
	},

	process_callback: function(data)
	{
		this.offset += data.count;

		this.set_total(data.total);
		this.set_processed(this.offset);
		this.set_progress((this.offset * 100 / data.total).toFixed(0));
		
		var failed = false;
		for (var i = 0; i < data.results.length; i++) {
			this.log(data.results[i].remote_path, data.results[i].result, data.results[i].error);
			if (data.results[i].result == -1) {
				failed = true;
				break;
			}
		}
		
		if (failed) {
			this.offset = 0;
			this.set_progress(0);
			this.set_processed(1);
			this.set_status('failed');
			this.set_button_text('Start');
		} else {
			if (this.offset < data.total) {
				this.process();
			} else {
				this.offset = 0;
				this.set_status('done');
				this.set_button_text('Start');
			}
		}
	},

	init: function(action) {
		if (action === undefined) {
			action = 'cdn_export_table';
		}
		
		this.action = action;
		
		var me = this;
		jQuery('#cdn_export_table_start').click(function() {
			if (this.value == 'Pause') {
				me.paused = 1;
				me.set_button_text('Resume');
				me.set_status('paused');
			} else {
				me.paused = 0;
				me.set_button_text('Pause');	
				me.set_status('processing');
			}
			
			me.process();
		});
	}
}

jQuery(function($) {
	$('.tab').click(function() {
		$('.tab').removeClass('tab-selected');
		$('.tab-content').hide();
		$(this).addClass('tab-selected');
		$(this.rel).show();
	});
	
	$('.cdn_queue_delete').click(function() {
		return confirm('Are you sure you want to delete this file from the queue?');
	});
	
	$('.cdn_queue_empty').click(function() {
		return confirm('Are you sure you want to empty the queue?');
	});

	Cdn_Export_Table.init('cdn_export_library_process');
	if (typeof files !== 'undefined') {
		Cdn_Export_File.init(files);
	}
});