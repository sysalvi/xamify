const EXAM_URL = 'http://localhost:8000';
const EXAM_ORIGINS = ['http://localhost:8000', 'http://127.0.0.1:8000'];
const ENABLED_KEY = 'xamify_enabled';
const ALLOWED_TAB_KEY = 'xamify_allowed_tab_id';

const toggle = document.getElementById('toggle');
const statusText = document.getElementById('status-text');
const openExam = document.getElementById('open-exam');

const updateStatus = (enabled) => {
  statusText.textContent = enabled ? 'Aktif' : 'Non-Aktif';
  statusText.style.color = enabled ? '#1f7a1f' : '#7b8794';
};

const openExamPage = () => {
  chrome.runtime.sendMessage({ type: 'xamify-open' });
};

chrome.storage.local.get([ENABLED_KEY], (result) => {
  const enabled = Boolean(result[ENABLED_KEY]);
  toggle.checked = enabled;
  updateStatus(enabled);
});


toggle.addEventListener('change', () => {
  const enabled = toggle.checked;
  chrome.storage.local.set({ [ENABLED_KEY]: enabled }, () => {
    updateStatus(enabled);

    if (enabled) {
      chrome.runtime.sendMessage({ type: 'xamify-enable' });
      return;
    }

    chrome.storage.local.remove(ALLOWED_TAB_KEY);

    chrome.tabs.query({}, (tabs) => {
      for (const tab of tabs) {
        if (!tab.url) continue;
        if (EXAM_ORIGINS.some((origin) => tab.url.startsWith(origin))) {
          chrome.tabs.reload(tab.id);
        }
      }
    });
  });
});


openExam.addEventListener('click', openExamPage);
chrome.runtime.sendMessage({ type: 'xamify-popup-opened' });
