(function () {
  var externalCheckbox = document.getElementById('external_checkbox');
  function toggleExternalRedirect() {
    var spanToUrl = document.getElementById('to_home_url');
    var inputToUrl = document.getElementById('to_url');
    if (externalCheckbox.checked) {
      spanToUrl.style.display = 'none';
      inputToUrl.setAttribute('placeholder', 'https://bonnier.com');
      inputToUrl.style.width = '100%';
    } else {
      spanToUrl.style.display = 'inline';
      inputToUrl.setAttribute('placeholder', '/new/page/slug');
      inputToUrl.style.width = 'auto';
    }
  }
  externalCheckbox.addEventListener('change', toggleExternalRedirect);
  toggleExternalRedirect();
})();
