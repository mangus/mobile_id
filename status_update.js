
YUI().use('node', 'io-base', function (Y) {
    var toHide = Y.one('#hideWithJavascript');
    toHide.hide();
    function checkMobileIdStatus() {
        var request = Y.io('/auth/mobile_id/ajax.php', {
            method:"GET",
            data: {'sesscode': 'TODO'},
            on: {success:
                function(id, answer) {
                    if (1 == answer) {
                        location.href='/auth/mobile_id/login.php?startlogin=SESSCODE';
                    }
                }
            }
        });

    }
    var timeCount = 0;
    function printDots() {
        if (++timeCount > 80)
            location.href='/auth/mobile_id/login.php?timeout=1';
        var messageText = Y.one('.generalbox');
        messageText.append('&nbsp.');
    }
    setInterval(checkMobileIdStatus, 5000);
    setInterval(printDots, 1000);
});

