
YUI().use('node', 'io-base', function (Y) {
    var toHide = Y.one('#hideWithJavascript');
    toHide.hide();
    var sesscode = Y.one('#sesscode').getContent();
    function checkMobileIdStatus() {
        var request = Y.io('/auth/mobile_id/ajax.php', {
            method:"GET",
            data: {'sesscode': sesscode},
            on: {success:
                    function(id, answer) {
                        if ('USER_AUTHENTICATED' == answer.response) {
                            location.href='/auth/mobile_id/login.php?startlogin=' + sesscode;
                        } else if ('EXPIRED_TRANSACTION' == answer.response) {
                            location.href='/auth/mobile_id/login.php?timeout=1';                        
                        } else if ('OUTSTANDING_TRANSACTION' == answer.response) {
                            // Waiting more...
                        } else
                            location.href='/auth/mobile_id/login.php?error=1';
                    },
                failure:
                    function(id, answer) {
                        location.href='/auth/mobile_id/login.php?error=1';
                    }
            }
        });

    }
    function printDots() {
        var messageText = Y.one('.generalbox');
        messageText.append('&nbsp.');
    }
    setInterval(checkMobileIdStatus, 5000);
    setInterval(printDots, 1000);
});

