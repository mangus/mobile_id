
YUI().use('node', 'node-load', function (Y) {
    var toHide = Y.one('#hideWithJavascript');
    toHide.hide();
    function checkMobileIdStatus() {
        // Ajax request...
        Y.one('#content').load('content.html');
    }
    function printDots() {
        var messageText = Y.one('.generalbox');
        messageText.append('&nbsp.');
    }
    setInterval(checkMobileIdStatus, 5000);
    setInterval(printDots, 1000);
});

