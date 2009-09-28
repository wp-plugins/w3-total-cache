function w3tc_popup(url, name, width, height) {
    if (width === undefined) {
        width = 800;
    }
    if (height === undefined) {
        height = 600;
    }

    return window.open(url, name, 'width=' + width + ',height=' + height + ',status=no,toolbar=no,menubar=no,scrollbars=yes');
}

function input_enable(input, enabled) {
    jQuery(input).each(function() {
        this.disabled = !enabled;
        if (enabled) {
            jQuery(this).next('[type=hidden]').remove();
        } else {
            var me = jQuery(this), t = me.attr('type');
            if ((t != 'radio' && t != 'checkbox') || this.checked) {
                me.after(jQuery('<input />').attr( {
                    type: 'hidden',
                    name: me.attr('name')
                }).val(me.val()));
            }
        }
    });
}

function js_file_location_change() {
    jQuery('.js_file_location').change(function() {
        jQuery(this).parent().find(':text').attr('name', 'js_files[' + jQuery('#js_groups').val() + '][' + jQuery(this).val() + ']');
    });
}

function file_verify() {
    jQuery('.js_file_verify,.css_file_verify').click(function() {
        var file = jQuery(this).parent().find(':text').val();
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

function file_validate() {
    var js = [], css = [], invalid_js = [], invalid_css = [], duplicate = false, query_js = [], query_css = [];

    jQuery('#js_files :text').each(function() {
        var v = jQuery(this).val();
        if (v != '') {
            for ( var i = 0; i < js.length; i++) {
                if (js[i] == v) {
                    duplicate = true;
                    break;
                }
            }

            js.push(v);

            var qindex = v.indexOf('?');
            if (qindex != -1) {
                if (!/^https?:\/\//.test(v)) {
                    query_js.push(v);
                }
                v = v.substr(0, qindex);
            }

            if (!/\.js$/.test(v)) {
                invalid_js.push(v);
            }
        }
    });

    jQuery('#css_files :text').each(function() {
        var v = jQuery(this).val();
        if (v != '') {
            for ( var i = 0; i < css.length; i++) {
                if (css[i] == v) {
                    duplicate = true;
                    break;
                }
            }

            css.push(v);

            var qindex = v.indexOf('?');
            if (qindex != -1) {
                if (!/^https?:\/\//.test(v)) {
                    query_css.push(v);
                }
                v = v.substr(0, qindex);
            }

            if (!/\.css$/.test(v)) {
                invalid_css.push(v);
            }
        }
    });

    if (jQuery('#js_enabled:checked').size()) {
        if (invalid_js.length && !confirm('These files have invalid JS file extension:\r\n\r\n' + invalid_js.join('\r\n') + '\r\n\r\nAre you confident this files contain valid JS code?')) {
            return false;
        }

        if (query_js.length) {
            alert('These JS files contain query string in the name:\r\n\r\n' + query_js.join('\r\n'));
            return false;
        }
    }

    if (jQuery('#css_enabled:checked').size()) {
        if (invalid_css.length && !confirm('These files have invalid CSS file extension:\r\n\r\n' + invalid_css.join('\r\n') + '\r\n\r\nAre you confident this files contain valid CSS code?')) {
            return false;
        }

        if (query_css.length) {
            alert('These CSS files contain query string in the name:\r\n\r\n' + query_css.join('\r\n'));
            return false;
        }
    }

    if (duplicate) {
        alert('Duplicate files have been found in your minify settings, please check your settings again.');
        return false;
    }

    return true;
}

function js_file_clear() {
    if (!jQuery('#js_files :visible').length) {
        jQuery('#js_files_empty').show();
    } else {
        jQuery('#js_files_empty').hide();
    }
}

function css_file_clear() {
    if (!jQuery('#css_files :visible').length) {
        jQuery('#css_files_empty').show();
    } else {
        jQuery('#css_files_empty').hide();
    }
}

function js_enabled() {
    jQuery('#js_enabled').click(function() {
        input_enable('.js_enabled', this.checked);
    });
}

function css_enabled() {
    jQuery('#css_enabled').click(function() {
        input_enable('.css_enabled', this.checked);
    });
}

function js_file_delete() {
    jQuery('.js_file_delete').click(function() {
        if (confirm('Are you sure you want to delete JS file?')) {
            jQuery(this).parent().remove();
            if (!jQuery('#js_files li').size()) {
                js_file_clear();
            }
        }

        return false;
    });
};

function css_file_delete() {
    jQuery('.css_file_delete').click(function() {
        if (confirm('Are you sure you want to delete CSS file?')) {
            jQuery(this).parent().remove();
            if (!jQuery('#css_files li').size()) {
                css_file_clear();
            }
        }

        return false;
    });
}

function js_file_add(group, location, file) {
    jQuery('#js_files').append('<li><input class="js_enabled" type="text" name="js_files[' + group + '][' + location + '][]" value="' + file + '" size="100" \/>&nbsp;<select class="js_file_location js_enabled"><option value="include"' + (location == 'include' ? ' selected="selected"' : '') + '>Embed in: Header</option><option value="include-nb"' + (location == 'include-nb' ? ' selected="selected"' : '') + '>Embed in: Header (non-blocking)</option><option value="include-footer"' + (location == 'include-footer' ? ' selected="selected"' : '') + '>Embed in: Footer</option><option value="include-footer-nb"' + (location == 'include-footer-nb' ? ' selected="selected"' : '') + '>Embed in: Footer (non-blocking)</option></select>&nbsp;<input class="js_file_delete js_enabled button" type="button" value="Delete" />&nbsp;<input class="js_file_verify js_enabled button" type="button" value="Verify URI" /><\/li>');
    js_file_clear();
    js_file_delete();
    file_verify();
    js_enabled();
    js_file_location_change();
}

function css_file_add(group, file) {
    jQuery('#css_files').append('<li><input class="css_enabled" type="text" name="css_files[' + group + '][include][]" value="' + file + '" size="100" \/>&nbsp;<input class="css_file_delete css_enabled button" type="button" value="Delete" />&nbsp;<input class="css_file_verify css_enabled button" type="button" value="Verify URI" /><\/li>');
    css_file_clear();
    css_file_delete();
    file_verify();
    css_enabled();
}

function js_group(group) {
    jQuery('#js_groups').val(group);
    jQuery('#js_files :text').each(function() {
        var input = jQuery(this);
        if (input.attr('name').indexOf('js_files[' + group) != 0) {
            input.parent().hide();
        } else {
            input.parent().show();
        }
    });
    js_file_clear();
}

function css_group(group) {
    jQuery('#css_groups').val(group);
    jQuery('#css_files :text').each(function() {
        var input = jQuery(this);
        if (input.attr('name').indexOf('css_files[' + group) != 0) {
            input.parent().hide();
        } else {
            input.parent().show();
        }
    });
    css_file_clear();
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
    input_enable('.html_enabled', $('#html_enabled:checked').size());
    input_enable('.js_enabled', $('#js_enabled:checked').size());
    input_enable('.css_enabled', $('#css_enabled:checked').size());

    $('#html_enabled').click(function() {
        input_enable('.html_enabled', this.checked);
    });

    file_verify();
    js_file_location_change();

    js_enabled();
    css_enabled();

    js_file_delete();
    css_file_delete();

    js_group('default');
    css_group('default');

    $('#js_file_add').click(function() {
        js_file_add($('#js_groups').val(), 'include', '');
    });

    $('#css_file_add').click(function() {
        css_file_add($('#css_groups').val(), '');
    });

    $('#js_groups').change(function() {
        js_group($(this).val());
    });

    $('#css_groups').change(function() {
        css_group($(this).val());
    });

    $('#minify_form').submit(file_validate);

    // CDN
    $('.w3tc-tab').click(function() {
        $('.w3tc-tab-content').hide();
        $(this.rel).show();
    });

    $('#cdn_export_library').click(function() {
        w3tc_popup('options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=cdn_export_library', 'cdn_export_library');
    });

    $('#cdn_import_library').click(function() {
        w3tc_popup('options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=cdn_import_library', 'cdn_import_library');
    });

    $('#cdn_queue').click(function() {
        w3tc_popup('options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=cdn_queue', 'cdn_queue');
    });

    $('#cdn_rename_domain').click(function() {
        w3tc_popup('options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=cdn_rename_domain', 'cdn_rename_domain');
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

    $('#test_memcached').click(function() {
        var status = $('#test_memcached_status');
        status.removeClass('w3tc-error');
        status.addClass('w3tc-process');
        status.html('Testing...');
        $.post('options-general.php', {
            page: 'w3-total-cache/w3-total-cache.php',
            w3tc_action: 'test_memcached',
            servers: $('#memcached_servers').val()
        }, function(data) {
            status.addClass(data.result ? 'w3tc-success' : 'w3tc-error');
            status.html(data.error);
        }, 'json');
    });
});
