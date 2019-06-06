(function () {
  function attachedFile(inputField, submitButton) {
    var fileInput = document.getElementById(inputField);
    var submitBtn = document.getElementById(submitButton);
    fileInput.addEventListener('change', function (event) {
      if (event.target.value) {
        submitBtn.removeAttribute('disabled');
      } else {
        submitBtn.setAttribute('disabled', 'disabled');
      }
    });
  }
  attachedFile('import-file', 'import-submit');
  attachedFile('404-file', '404-submit')
})();
