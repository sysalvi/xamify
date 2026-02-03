const EXAM_URL = 'http://localhost:8000/';
const EXAM_ORIGINS = ['http://localhost:8000', 'http://127.0.0.1:8000'];
const ENABLED_KEY = 'xamify_enabled';
const ALLOWED_TAB_KEY = 'xamify_allowed_tab_id';
const SESSION_KEY = 'xamify_session_id';
const PING_KEY = 'xamify_ping_password';
const PING_HEADER_KEY = 'xamify_ping_password_header';
const HEADER_RULE_IDS = [101, 102];
const SAFE_PREFIXES = [
  'chrome://extensions',
  'arc://extensions',
  'chrome://newtab',
  'arc://newtab',
  'chrome://settings',
  'arc://settings',
  'chrome://version',
  'chrome://history',
  'about:blank',
  'chrome-extension://',
];
let suspendEnforcementUntil = 0;

const isExamTab = (tab) => {
  if (!tab || !tab.url) return false;
  return EXAM_ORIGINS.some((origin) => tab.url.startsWith(origin));
};

const isSafeTab = (tab) => {
  const url = tab?.url || tab?.pendingUrl || '';
  return SAFE_PREFIXES.some((prefix) => url.startsWith(prefix));
};

const getExamTab = (tabs) => tabs.find((tab) => isExamTab(tab));
const hasExamTab = (tabs) => Boolean(getExamTab(tabs));

const focusExamTab = () => {
  chrome.tabs.query({}, (tabs) => {
    const examTab = getExamTab(tabs);
    if (examTab && examTab.id) {
      chrome.tabs.update(examTab.id, { active: true });
    }
  });
};

const enforceTabs = () => {
  chrome.storage.local.get([ENABLED_KEY, ALLOWED_TAB_KEY], (result) => {
    const enabled = Boolean(result[ENABLED_KEY]);

    if (!enabled) {
      return;
    }

    if (Date.now() < suspendEnforcementUntil) {
      return;
    }

    chrome.tabs.query({}, (tabs) => {
      const examTab = getExamTab(tabs);

      if (!examTab) {
        chrome.tabs.create({ url: EXAM_URL }, (tab) => {
          chrome.storage.local.set({ [ALLOWED_TAB_KEY]: tab.id });
        });
        return;
      } else {
        chrome.storage.local.set({ [ALLOWED_TAB_KEY]: examTab.id });
      }
    });
  });
};

const focusOrCreateExamTab = () => {
  chrome.tabs.query({}, (tabs) => {
    const examTab = tabs.find((tab) => isExamTab(tab));

    if (examTab && examTab.id) {
      chrome.tabs.update(examTab.id, { active: true });
      return;
    }

    chrome.tabs.create({ url: EXAM_URL }, (tab) => {
      if (tab && tab.id) {
        chrome.storage.local.set({ [ALLOWED_TAB_KEY]: tab.id });
      }
    });
  });
};

const reloadExamTabs = () => {
  chrome.tabs.query({}, (tabs) => {
    for (const tab of tabs) {
      if (isExamTab(tab)) {
        chrome.tabs.reload(tab.id);
      }
    }
  });
};

const setHeaderRules = (enabled) => {
  if (!chrome.declarativeNetRequest) return;

  if (!enabled) {
    chrome.declarativeNetRequest.updateDynamicRules({
      removeRuleIds: HEADER_RULE_IDS,
    });
    return;
  }

  chrome.storage.local.get([PING_HEADER_KEY], (result) => {
    const pingHeader = result[PING_HEADER_KEY] || '';

    const headers = [
      {
        header: 'X-Xamify-Ext',
        operation: 'set',
        value: '1',
      },
    ];

    if (pingHeader) {
      headers.push({
        header: 'X-Xamify-Ping',
        operation: 'set',
        value: pingHeader,
      });
    }

    chrome.declarativeNetRequest.updateDynamicRules({
      removeRuleIds: HEADER_RULE_IDS,
      addRules: [
        {
          id: 101,
          priority: 1,
          action: {
            type: 'modifyHeaders',
            requestHeaders: headers,
          },
          condition: {
            urlFilter: 'localhost:8000/',
            resourceTypes: ['main_frame', 'sub_frame', 'xmlhttprequest'],
          },
        },
        {
          id: 102,
          priority: 1,
          action: {
            type: 'modifyHeaders',
            requestHeaders: headers,
          },
          condition: {
            urlFilter: '127.0.0.1:8000/',
            resourceTypes: ['main_frame', 'sub_frame', 'xmlhttprequest'],
          },
        },
      ],
    });
  });
};

const handshakeForPing = () => {
  return fetch(`${EXAM_URL}api/handshake-ext`, {
    method: 'POST',
    headers: {
      'X-Xamify-Ext': '1',
    },
  })
    .then((res) => res.json())
    .then((data) => {
      if (data && data.ping_password) {
        chrome.storage.local.set({ [PING_HEADER_KEY]: data.ping_password });
      }
      return data;
    })
    .catch(() => null);
};

const openExamWithPing = () => {
  chrome.storage.local.get([PING_HEADER_KEY], (result) => {
    const ping = result[PING_HEADER_KEY];
    const url = ping ? `${EXAM_URL}?xamify_ping=${encodeURIComponent(ping)}` : EXAM_URL;
    chrome.tabs.create({ url }, (tab) => {
      if (tab && tab.id) {
        chrome.storage.local.set({ [ALLOWED_TAB_KEY]: tab.id });
      }
    });
  });
};

const enableProtection = () => {
  return handshakeForPing().then(() => {
    setHeaderRules(true);
    enforceTabs();
    reloadExamTabs();
  });
};

const sendViolation = (reason) => {
  chrome.storage.local.get([SESSION_KEY, PING_KEY], (result) => {
    const sessionId = Number(result[SESSION_KEY] || 0);
    if (!sessionId) return;

    fetch(`${EXAM_URL}api/violation`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ session_id: sessionId, reason }),
    }).catch(() => {});
  });
};

chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.local.get([ENABLED_KEY], (result) => {
    const enabled = Boolean(result[ENABLED_KEY]);
    if (enabled) {
      enableProtection();
      return;
    }
    setHeaderRules(false);
  });
});

chrome.storage.onChanged.addListener((changes) => {
  if (changes[ENABLED_KEY]) {
    const enabled = Boolean(changes[ENABLED_KEY].newValue);
    if (enabled) {
      enableProtection();
      return;
    }
    setHeaderRules(false);
  }
  if (changes[PING_HEADER_KEY]) {
    chrome.storage.local.get([ENABLED_KEY], (result) => {
      setHeaderRules(Boolean(result[ENABLED_KEY]));
    });
  }
});

chrome.runtime.onMessage.addListener((message) => {
  if (!message || typeof message !== 'object') return;

  if (message.type === 'xamify-popup-opened') {
    suspendEnforcementUntil = Date.now() + 8000;
    return;
  }

  if (message.type === 'xamify-enable') {
    enableProtection().then(openExamWithPing);
  }

  if (message.type === 'xamify-open') {
    enableProtection().then(openExamWithPing);
  }
});

chrome.tabs.onCreated.addListener((tab) => {
  chrome.storage.local.get([ENABLED_KEY], (result) => {
    if (!result[ENABLED_KEY]) return;
    if (Date.now() < suspendEnforcementUntil) return;
    if (!tab.url && !tab.pendingUrl) return;
    chrome.tabs.query({}, (tabs) => {
      if (!hasExamTab(tabs)) return;
      if (!isExamTab(tab) && !isSafeTab(tab)) {
        sendViolation('tab_new');
        focusExamTab();
      }
    });
  });
});

chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (!changeInfo.url) return;

  chrome.storage.local.get([ENABLED_KEY, ALLOWED_TAB_KEY], (result) => {
    if (!result[ENABLED_KEY]) return;
    if (Date.now() < suspendEnforcementUntil) return;
    chrome.tabs.query({}, (tabs) => {
      if (!hasExamTab(tabs)) return;

      if (!isExamTab(tab) && !isSafeTab(tab)) {
        sendViolation('tab_update');
        focusExamTab();
        return;
      }

      chrome.storage.local.set({ [ALLOWED_TAB_KEY]: tabId });
    });
  });
});

chrome.tabs.onActivated.addListener((activeInfo) => {
  chrome.storage.local.get([ENABLED_KEY], (result) => {
    if (!result[ENABLED_KEY]) return;
    if (Date.now() < suspendEnforcementUntil) return;
    chrome.tabs.query({}, (tabs) => {
      if (!hasExamTab(tabs)) return;

    chrome.tabs.get(activeInfo.tabId, (tab) => {
      if (!tab) return;
      if (!tab.url && !tab.pendingUrl) return;
      if (isExamTab(tab) || isSafeTab(tab)) return;

      sendViolation('tab_switch');
      focusExamTab();
    });
  });
});
});
