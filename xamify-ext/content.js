const ENABLED_KEY = 'xamify_enabled';
(() => {
  if (window.__XAMIFY_EXT_LOADED__) {
    return;
  }

  window.__XAMIFY_EXT_LOADED__ = true;

  const EXAM_ROOT = 'http://localhost:8000';
  const ENABLED_KEY = 'xamify_enabled';
  const SESSION_KEY = 'xamify_session_id';
  const PING_KEY = 'xamify_ping_password';

const getSessionId = () => Number(localStorage.getItem(SESSION_KEY) || 0);
const getPingPassword = () => localStorage.getItem(PING_KEY) || '';

const sendViolation = async (reason) => {
  const sessionId = getSessionId();
  if (!sessionId) return;

  try {
    await fetch(`${EXAM_ROOT}/api/violation`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ session_id: sessionId, reason }),
    });
  } catch (error) {
    // ignore
  }
};

const sendHeartbeat = async () => {
  const sessionId = getSessionId();
  const pingPassword = getPingPassword();
  if (!sessionId || !pingPassword) return;

  try {
    await fetch(`${EXAM_ROOT}/api/heartbeat`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ session_id: sessionId, ping_password: pingPassword }),
    });
  } catch (error) {
    // ignore
  }
};

const renderBlocked = () => {
  document.documentElement.innerHTML = `
    <html lang="id">
      <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>XAMIFY</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <style>
          body {
            margin: 0;
            font-family: "Space Grotesk", system-ui, sans-serif;
            background: radial-gradient(circle at top, rgba(253, 120, 24, 0.2), transparent 55%),
              radial-gradient(circle at bottom, rgba(158, 35, 111, 0.25), transparent 55%),
              #0b1120;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
          }
          .card {
            max-width: 520px;
            width: 100%;
            padding: 32px;
            border-radius: 24px;
            background: rgba(15, 23, 42, 0.82);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.45);
          }
          .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(253, 120, 24, 0.2);
            color: #fd7818;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.2em;
          }
          h1 {
            margin: 16px 0 8px;
            font-size: 22px;
          }
          p {
            margin: 0;
            font-size: 14px;
            color: #cbd5f5;
          }
          .logo {
            margin: 18px auto 0;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: #ffffff;
            color: #9e236f;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 28px;
          }
          .hint {
            margin-top: 18px;
            font-size: 12px;
            color: #94a3b8;
          }
        </style>
      </head>
      <body data-xamify-blocked="1">
        <div class="card">
          <div class="badge">Akses Ditolak</div>
          <div class="logo">X</div>
          <h1>Gunakan Aplikasi XAMIFY-CLIENT</h1>
          <p>Ekstensi tidak aktif. Aktifkan proteksi untuk melanjutkan ujian.</p>
          <p class="hint">Silakan aktifkan XAMIFY Extension atau gunakan aplikasi resmi.</p>
        </div>
      </body>
    </html>
  `;
};

const enforceState = (enabled) => {
  if (!enabled) {
    renderBlocked();
    return;
  }

  if (window.location.href.startsWith(EXAM_ROOT)) {
    if (document.body && document.body.dataset && document.body.dataset.xamifyBlocked === '1') {
      window.location.reload();
    }
    return;
  }

  window.location.href = EXAM_ROOT;
};

  chrome.storage.local.get([ENABLED_KEY], (result) => {
    enforceState(Boolean(result[ENABLED_KEY]));
  });

  chrome.storage.onChanged.addListener((changes) => {
    if (changes[ENABLED_KEY]) {
      enforceState(Boolean(changes[ENABLED_KEY].newValue));
    }
  });

  const persistSession = () => {
    const sessionId = getSessionId();
    const pingPassword = getPingPassword();
    if (!sessionId) return;

    chrome.storage.local.set({
      [SESSION_KEY]: sessionId,
      [PING_KEY]: pingPassword,
    });
  };

  persistSession();

  document.addEventListener('fullscreenchange', () => {
    if (document.fullscreenElement === null) {
      sendViolation('exit_fullscreen');
    }
  });

  window.addEventListener('blur', () => sendViolation('window_blur'));
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
      sendViolation('visibility_hidden');
    }
  });

  setInterval(sendHeartbeat, 60000);
})();
