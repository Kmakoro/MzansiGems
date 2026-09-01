(() => {
  document.querySelectorAll('[data-dismiss-toast]').forEach(btn => btn.addEventListener('click', () => btn.closest('.toast')?.remove()));
  setTimeout(() => document.querySelectorAll('.toast').forEach(t => t.classList.add('toast-expire')), 5000);
  setTimeout(() => document.querySelectorAll('.toast-expire').forEach(t => t.remove()), 5600);

  document.querySelectorAll('[data-char-counter]').forEach(input => {
    const target = document.querySelector(input.dataset.charCounter);
    const max = Number(input.maxLength || 0);
    const update = () => { if (target) target.textContent = `${input.value.length}/${max}`; };
    input.addEventListener('input', update); update();
  });

  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => { if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault(); });
  });

  document.querySelectorAll('[data-password-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.querySelector(btn.dataset.passwordToggle);
      if (input) input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  const adminToggle = document.querySelector('[data-admin-menu]');
  if (adminToggle) adminToggle.addEventListener('click', () => document.querySelector('.admin-sidebar')?.classList.toggle('open'));

  document.querySelectorAll('[data-image-preview]').forEach(input => {
    input.addEventListener('change', () => {
      const target = document.querySelector(input.dataset.imagePreview);
      if (!target) return;
      target.innerHTML = '';
      [...input.files].slice(0, 3).forEach(file => {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.cssText = 'width:110px;height:80px;object-fit:cover;border-radius:10px;margin:8px 5px 0';
        target.appendChild(img);
      });
    });
  });

  document.querySelectorAll('[data-max-checks]').forEach(group => {
    const max = Number(group.dataset.maxChecks || 0);
    group.addEventListener('change', event => {
      const checked = group.querySelectorAll('input[type="checkbox"]:checked');
      if (checked.length > max) {
        event.target.checked = false;
        alert(`Choose up to ${max} options.`);
      }
    });
  });

  const surprise = document.querySelector('[data-surprise-me]');
  if (surprise) surprise.addEventListener('click', async () => {
    surprise.disabled = true;
    try {
      const response = await fetch(`${window.HIDDEN_GEMS.baseUrl}/random-gem.php`);
      const data = await response.json();
      if (data.url) window.location.href = data.url;
    } catch (e) { alert('Could not pick a gem right now.'); }
    finally { surprise.disabled = false; }
  });
})();
