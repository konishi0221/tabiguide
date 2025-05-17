/**
 * showToast(msg , type = 'success' , link = '')
 *  - msg  : 表示テキスト
 *  - type : 'success' | 'error'
 *  - link : '' 以外ならクリックで遷移する URL
 */
function showToast(msg, type = 'success', link = '') {
  // 現在 DOM にあるトースト数をカウント（フェードアウト中も含む）
  const existing = document.querySelectorAll('.copy-toast').length;
  const el = document.createElement('div');

  // base top 24px + 56px * N
  el.style.top = `${24 + existing * 56}px`;
  el.className = 'copy-toast';
  if (type === 'error') el.classList.add('error');

  // icon
  const icon = document.createElement('span');
  icon.className = 'material-symbols-outlined';
  icon.textContent = type === 'error' ? 'error' : 'check_circle';

  // message area (with optional link)
  const msgSpan = document.createElement(link ? 'a' : 'span');
  msgSpan.className = 'msg';
  msgSpan.textContent = msg;
  if (link) {
    msgSpan.href = link;
    msgSpan.style.textDecoration = 'underline';
    msgSpan.style.color = '#fff';
    msgSpan.target = '_blank';
  }

  // close button
  const closeBtn = document.createElement('span');
  closeBtn.className = 'material-symbols-outlined close-btn';
  closeBtn.textContent = 'close';
  closeBtn.addEventListener('click', () => {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 300);
  });

  el.appendChild(icon);
  el.appendChild(msgSpan);
  el.appendChild(closeBtn);

  document.body.appendChild(el);
  requestAnimationFrame(() => el.classList.add('show'));

  // auto-remove after 20s
  const timer = setTimeout(removeToast, 5000);

  function removeToast() {
    clearTimeout(timer);
    el.classList.remove('show');
    setTimeout(() => el.remove(), 400);
  }
}
