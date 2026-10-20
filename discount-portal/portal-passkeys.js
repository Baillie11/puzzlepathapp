// Included inside the page's nonce-protected script; no external script is loaded.
const passkeyToggle = document.getElementById('passkey-toggle');
passkeyToggle?.addEventListener('click', () => {
    const panel = document.getElementById('passkeys');
    panel.hidden = !panel.hidden;
    passkeyToggle.setAttribute('aria-expanded', String(!panel.hidden));
    if (panel.hidden) document.getElementById('passkey-password').value = '';
});
const passkeyButton = document.getElementById('passkey-button');
const passkeyStatus = document.getElementById('passkey-status');
const passkeyDecode = text => Uint8Array.from(atob(text.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - text.length % 4) % 4)), char => char.charCodeAt(0));
const passkeyEncode = buffer => btoa(String.fromCharCode(...new Uint8Array(buffer))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
async function passkeyPost(action, fields = {}) {
    const csrf = document.querySelector('input[name="csrf"]').value;
    const response = await fetch('?puzzlepath-discounts=1', {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action, csrf, ...fields})
    });
    let result;
    try { result = await response.json(); }
    catch { throw new Error('The server did not return a passkey response. Refresh and use your password if needed.'); }
    if (!response.ok || result.error) throw new Error(result.error || 'Passkey verification failed.');
    return result;
}
if (!window.PublicKeyCredential || !navigator.credentials || !window.isSecureContext) {
    passkeyButton.disabled = true;
    passkeyStatus.textContent = 'Passkeys are unavailable in this browser. Open the portal in an updated Chrome browser on your phone, or use your password.';
}
passkeyButton.addEventListener('click', async () => {
    const register = passkeyButton.dataset.mode === 'register';
    const password = document.getElementById('passkey-password');
    if (register && !password.value) { passkeyStatus.textContent = 'Enter your existing portal password to approve adding a passkey.'; password.focus(); return; }
    passkeyButton.disabled = true;
    passkeyStatus.textContent = register ? 'Preparing passkey setup…' : 'Preparing secure sign-in…';
    try {
        const mode = register ? 'register' : 'login';
        const start = await passkeyPost('passkey_' + mode + '_options', register ? {password: password.value} : {});
        if (password) password.value = '';
        const options = start.options.publicKey;
        options.challenge = passkeyDecode(options.challenge);
        if (options.user) options.user.id = passkeyDecode(options.user.id);
        for (const field of ['excludeCredentials', 'allowCredentials']) {
            if (options[field]) options[field] = options[field].map(item => ({...item, id: passkeyDecode(item.id)}));
        }
        passkeyStatus.textContent = 'Approve the prompt on your device with your fingerprint or screen lock.';
        const credential = register ? await navigator.credentials.create({publicKey: options}) : await navigator.credentials.get({publicKey: options});
        if (!credential) throw new Error('No passkey was selected. You can try again or use your password.');
        const fields = {request: start.request, id: passkeyEncode(credential.rawId), clientDataJSON: passkeyEncode(credential.response.clientDataJSON)};
        if (register) fields.attestationObject = passkeyEncode(credential.response.attestationObject);
        else {
            fields.authenticatorData = passkeyEncode(credential.response.authenticatorData);
            fields.signature = passkeyEncode(credential.response.signature);
            fields.userHandle = credential.response.userHandle ? passkeyEncode(credential.response.userHandle) : '';
        }
        const result = await passkeyPost('passkey_' + mode + '_finish', fields);
        if (register && result.registered) {
            passkeyStatus.textContent = 'Passkey enabled! Next time you sign out, choose “Sign in with passkey”. Your portal password still works as a backup.';
            passkeyButton.textContent = 'Passkey enabled';
            passkeyButton.dataset.complete = 'true';
        } else if (!register && result.authenticated) window.location.assign('?puzzlepath-discounts=1');
        else throw new Error('Passkey verification did not finish. Please try again.');
    } catch (error) {
        const messages = {
            NotAllowedError: 'The device prompt was cancelled or timed out. Try again, or use your portal password.',
            InvalidStateError: 'A passkey for this portal is already saved on this device. Try signing in with it.',
            NotSupportedError: 'This device cannot create the requested passkey. Use updated Chrome with a screen lock enabled.',
            SecurityError: 'Passkeys must be used on the secure Puzzle Path website in your phone browser.'
        };
        passkeyStatus.textContent = messages[error.name] || error.message || 'Passkey setup failed. Your password still works.';
    } finally {
        if (password) password.value = '';
        if (!passkeyButton.dataset.complete) passkeyButton.disabled = false;
    }
});
