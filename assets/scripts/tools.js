(function () {
  var fileInput = document.getElementById('import-file');
  var submitBtn = document.getElementById('import-submit');
  fileInput.addEventListener('change', function (event) {
    if (event.target.value) {
      submitBtn.removeAttribute('disabled');
    } else {
      submitBtn.setAttribute('disabled', 'disabled');
    }
  });
})();
