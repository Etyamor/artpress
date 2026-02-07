const burger = document.querySelector('.burger-button');
const nav = document.getElementById('site-nav');

if (burger && nav) {
  burger.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('open');
    burger.classList.toggle('menu-open', isOpen);
    burger.setAttribute('aria-expanded', String(isOpen));
  });
}

const subscribeForm = document.getElementById('subscribe-form');

if (subscribeForm) {
  subscribeForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const msg = document.getElementById('subscribe-message');
    const btn = subscribeForm.querySelector('button[type="submit"]');
    const email = subscribeForm.querySelector('input[name="email"]');

    msg.classList.add('hidden');
    btn.disabled = true;

    const body = new FormData();
    body.append('action', 'artpress_subscribe');
    body.append('nonce', subscribeForm.dataset.nonce);
    body.append('email', email.value);

    try {
      const res = await fetch(subscribeForm.dataset.ajaxUrl, { method: 'POST', body });
      const json = await res.json();

      if (json.success) {
        subscribeForm.remove();
        msg.textContent = json.data?.message || '';
        msg.className = 'mt-1 text-sm text-green-300';
      } else {
        msg.textContent = json.data?.message || '';
        msg.className = 'absolute left-0 right-0 mt-1 text-sm text-red-300';
        btn.disabled = false;
      }
    } catch {
      msg.textContent = 'Something went wrong. Please try again.';
      msg.className = 'absolute left-0 right-0 mt-1 text-sm text-red-300';
      btn.disabled = false;
    }
  });
}
