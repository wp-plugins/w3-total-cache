jQuery(function() {
    jQuery('.w3tc-widget-ps-view-all').click(function() {
        window.open('admin.php?page=w3tc_general&w3tc_pagespeed_results&_wpnonce=' + jQuery(this).metadata().nonce, 'pagespeed_results', 'width=800,height=600,status=no,toolbar=no,menubar=no,scrollbars=yes');

        return false;
    });

    jQuery('.w3tc-widget-ps-refresh').click(function() {
        document.location.href = 'index.php?w3tc_widget_pagespeed_force=1';
    });

    jQuery(document).ready(function() {
        var e = jQuery('#w3tc_latest div.inside:visible').find('.widget-loading');
        if (e.length) {
            var p = e.parent();
            setTimeout(function() {
                jQuery.ajax({
                    url : ajaxurl + '?action=w3tc_widget_latest',
                    success : 
                        function (data) {
                            // cut trailing '0' char in wp response
                            if (data.length > 0 && data.substr(data.length - 1, 1) == '0')
                                data = data.substr(0, data.length - 1);

                            p.html(data);
                            p.hide().slideDown('normal', 
                                function() {
                                    jQuery(this).css('display', '');
                                });
                        }
                    });
            }, 500);
        }
    });
});
