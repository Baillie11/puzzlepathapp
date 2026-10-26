(() => {
  'use strict';
  const form = document.querySelector('#pps-form');
  if (!form) return;
  const button = form.querySelector('button[type=submit]');
  const status = document.querySelector('#pps-status');
  const phone = form.elements.phone;
  let credentials, busy = false;
  const setStatus = (message, error = false) => { status.textContent = message; status.classList.toggle('pps-error', error); };
  const post = async (data) => {
    const response = await fetch(form.dataset.endpoint, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data), headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(20000) });
    let result;
    try { result = await response.json(); } catch { throw new Error('Our enquiry service is temporarily unavailable. Please try again later.'); }
    if (!response.ok || !result.success) throw Object.assign(new Error(result.data?.message || 'We could not save your enquiry. Please try again.'), { fields: result.data?.errors });
    return result.data;
  };
  const updatePhone = () => {
    const required = form.elements.contact_method.value === 'phone';
    document.querySelector('#pps-phone-field').hidden = !required;
    phone.required = required; phone.disabled = !required;
    if (!required) { phone.value = ''; phone.removeAttribute('aria-invalid'); document.querySelector('#pps-error-phone').textContent = ''; }
  };
  form.querySelectorAll('[name=contact_method]').forEach(input => input.addEventListener('change', updatePhone));
  updatePhone();
  const normaliseWebsite = () => {
    const input = form.elements.website;
    const value = input.value.trim();
    input.value = value && !/^[a-z][a-z\d+.-]*:\/\//i.test(value) ? `https://${value}` : value;
  };
  form.elements.website.addEventListener('blur', normaliseWebsite);
  const showFields = (errors) => {
    for (const [name, message] of Object.entries(errors)) {
      const field = form.elements[name];
      const output = document.querySelector(`#pps-error-${name}`);
      if (output) output.textContent = message;
      if (field?.setAttribute) field.setAttribute('aria-invalid', 'true');
    }
    const invalid = form.querySelector('[aria-invalid=true]');
    if (invalid) invalid.focus(); else status.focus();
  };
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (busy || !credentials) return;
    form.querySelectorAll('[aria-invalid]').forEach(el => el.removeAttribute('aria-invalid'));
    form.querySelectorAll('.pps-field-error').forEach(el => el.textContent = '');
    normaliseWebsite();
    const errors = {};
    for (const input of form.querySelectorAll('input:not(:disabled)')) if (!input.validity.valid) errors[input.name] = input.validity.valueMissing ? 'Please complete this field.' : 'Please enter a valid value.';
    if (Object.keys(errors).length) { setStatus('Please check the highlighted fields.', true); showFields(errors); return; }
    busy = true; button.disabled = true; form.setAttribute('aria-busy', 'true'); setStatus('Sending your sponsorship enquiry…');
    try {
      const result = await post({ ...Object.fromEntries(new FormData(form)), ...credentials, action: 'pps_submit' });
      if (!/^[a-f0-9-]{36}$/.test(result.reference_id || '')) throw new Error('We could not confirm receipt. Please try again.');
      document.querySelector('#pps-reference').textContent = result.reference_id;
      form.hidden = true;
      const success = document.querySelector('#pps-success'); success.hidden = false; success.focus();
    } catch (error) {
      setStatus(error.fields ? error.message : (error.name === 'TimeoutError' || error instanceof TypeError ? 'We could not confirm receipt. Your details are still here; please try again. Retrying will not duplicate a saved enquiry.' : error.message), true);
      showFields(error.fields || {});
    } finally { busy = false; button.disabled = false; form.removeAttribute('aria-busy'); }
  });
  post({ action: 'pps_bootstrap' }).then(data => {
    credentials = data;
    setTimeout(() => { button.disabled = false; setStatus(''); }, 2100);
  }).catch(error => { setStatus(`${error.message || 'The secure form is unavailable.'} Please reload to try again.`, true); });
  document.querySelectorAll('.pps a[href="#sponsorship-form"]').forEach(link => link.addEventListener('click', event => {
    event.preventDefault(); const target = document.querySelector('#sponsorship-form');
    target.focus({ preventScroll: true }); target.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
    history.replaceState(null, '', '#sponsorship-form');
  }));
})();
