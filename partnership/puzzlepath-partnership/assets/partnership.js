(() => {
  const form = document.getElementById('pp-partner-form');
  if (!form) return;
  const phone = form.elements.phone;
  function updatePhone() {
    phone.required = form.elements.contact_method.value === 'Phone';
    document.querySelector('label[for="pp-phone"]').textContent = 'Contact Phone Number' + (phone.required ? ' *' : '');
  }
  form.querySelectorAll('[name="contact_method"]').forEach(input => input.addEventListener('change', updatePhone));
  updatePhone();
  form.addEventListener('submit', event => {
    if (form.dataset.preview === 'true') {
      event.preventDefault();
      form.querySelector('.pp-form-status').textContent = 'Your details are valid. This local preview has not sent an enquiry.';
      return;
    }
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    form.querySelector('.pp-form-status').textContent = 'Sending your enquiry…';
  });
  window.addEventListener('pageshow', () => { form.querySelector('button[type="submit"]').disabled = false; });
})();
