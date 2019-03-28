(function () {
    var allowLeave = true;
    document.getElementById('bonnier-redirect-add-form').addEventListener('submit', function () {
        allowLeave = true;
    });

    var edited = function () {
        allowLeave = false;
    };

    document.querySelectorAll('#bonnier-redirect-add-form input').forEach(function (element) {
        element.addEventListener('input', edited);
    });
    document.querySelectorAll('#bonnier-redirect-add-form select').forEach(function (element) {
        element.addEventListener('change', edited);
    });

    window.addEventListener('beforeunload', function (event) {
        if (allowLeave) {
            return;
        }

        var confirmation = 'Are you sure you want to leave the page with unsaved changes?';

        (event || window.event).returnValue = confirmation;
        return confirmation;
    });
})();
