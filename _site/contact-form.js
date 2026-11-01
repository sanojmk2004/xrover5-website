  // Contact form: posts to send.php on the hosted site; shows a preview note where no mail script exists
  const form = document.getElementById('contactForm');
  const status = document.getElementById('formStatus');
  const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
  if (form) form.addEventListener('submit', async e => {
    e.preventDefault();
    const missing = [...form.querySelectorAll('[required]')].filter(f => !f.value.trim() || (f.type === 'tel' && f.value.replace(/\D/g, '').length < 10));
    status.hidden = false;
    if (missing.length) {
      status.classList.add('err');
      status.textContent = 'Please fill in your name, mobile number and a message.';
      missing[0].focus();
      return;
    }
    const first = form.name.value.trim().split(' ')[0];
    submitBtn.disabled = true; submitBtn.textContent = 'Sending…';
    status.classList.remove('err'); status.textContent = 'Sending your message…';
    try {
      const res = await fetch('/send.php', { method: 'POST', body: new FormData(form) });
      const out = await res.json().catch(() => null);
      if (res.status === 404 || !out) throw { preview: true };
      if (!out.ok) throw { msg: out.error };
      status.textContent = 'Thanks, ' + first + '. We\'ve received your message and will get back to you soon.';
      form.reset();
    } catch (err) {
      status.classList.add('err');
      status.textContent = err && err.preview
        ? 'Thanks, ' + first + '. This form is a preview and is not connected to an inbox yet, so your message was not sent.'
        : ((err && err.msg) || 'Your message could not be sent.') + ' Please call or WhatsApp us on +91 97473 69666, or email info@xrover5.com.';
    } finally {
      submitBtn.disabled = false; submitBtn.textContent = 'Send message';
    }
  });
