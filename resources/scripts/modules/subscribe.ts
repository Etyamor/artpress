interface SubscribeResponse {
  success: boolean;
  data?: { message?: string };
}

const subscribeForm = document.getElementById('subscribe-form') as HTMLFormElement | null;

if (subscribeForm) {
  subscribeForm.addEventListener('submit', async (e: SubmitEvent) => {
    e.preventDefault();

    const msg = document.getElementById('subscribe-message');
    const btn = subscribeForm.querySelector<HTMLButtonElement>('button[type="submit"]');
    const email = subscribeForm.querySelector<HTMLInputElement>('input[name="email"]');

    if (!msg || !btn || !email) return;

    const showError = (text: string): void => {
      msg.textContent = text;
      msg.className = 'absolute left-0 right-0 mt-1 text-sm text-red-300';
      btn.disabled = false;
    };

    msg.classList.add('hidden');
    btn.disabled = true;

    const body = new FormData();
    body.append('action', 'artpress_subscribe');
    body.append('nonce', subscribeForm.dataset.nonce ?? '');
    body.append('email', email.value);

    try {
      const res = await fetch(subscribeForm.dataset.ajaxUrl ?? '', { method: 'POST', body });
      const json = (await res.json()) as SubscribeResponse;

      if (json.success) {
        subscribeForm.remove();
        msg.textContent = json.data?.message || '';
        msg.className = 'mt-1 text-sm text-green-300';
      } else {
        showError(json.data?.message || '');
      }
    } catch {
      showError('Something went wrong. Please try again.');
    }
  });
}
