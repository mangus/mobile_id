YUI().use('node', 'io-base', function (Y) {
    var toHide = Y.one('#hideWithJavascript');
    toHide.hide();
    var sessionid = Y.one('#sessionid').getContent();

    function checkMobileIdStatus() {
        var request = Y.io('/auth/mobile_id/ajax.php', {
            method: "GET",
            data: {'sessionid': sessionid},
            on: {
                success:
                    function (id, answer) {
                        if ('USER_AUTHENTICATED' == answer.response) {
                            location.replace('/auth/mobile_id/login.php?startlogin=' + sessionid);
                        } else
                            location.replace('/auth/mobile_id/login.php?error=1&status=' + answer.response);
                    },
                failure:
                    function (id, answer) {
                        location.replace('/auth/mobile_id/login.php?error=1&status=AJAX_REQUEST_FAILURE');
                    }
            }
        });

    }

    function printDots() {
        var messageText = Y.one('.generalbox');
        messageText.append('&nbsp.');
    }

    checkMobileIdStatus();
    setInterval(printDots, 1000);
});

