(() => {
  /** @type {HTMLElement} */
  const statusBox = document.getElementById('statusBox');
  /** @type {HTMLElement} */
  const logList = document.getElementById('logList');
  /** @type {HTMLFormElement} */
  const transmitForm = document.getElementById('transmitForm');

  /**
   * Fetch JSON with sensible defaults.
   *
   * @param {string} url
   * @param {RequestInit} [options]
   * @returns {Promise<any>}
   */
  const fetchJson = async (url, options = {}) => {
    const res = await fetch(url, {
      headers: { Accept: 'application/json' },
      ...options,
    });
    if (!res.ok) {
      const text = await res.text();
      throw new Error(text || `HTTP ${res.status}`);
    }
    return res.json();
  };

  /**
   * Refresh rig/status box.
   *
   * @returns {Promise<void>}
   */
  const refreshStatus = async () => {
    statusBox.textContent = 'Loading...';
    try {
      const data = await fetchJson('api/status');
      statusBox.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
      statusBox.textContent = `Error: ${e.message}`;
    }
  };

  /**
   * Refresh QSO log list.
   *
   * @returns {Promise<void>}
   */
  const refreshLogs = async () => {
    logList.innerHTML = 'Loading...';
    try {
      const data = await fetchJson('api/logs');
      logList.innerHTML = '';
      data.entries
        .slice()
        .reverse()
        .forEach((entry) => {
          const el = document.createElement('div');
          el.className = 'log-row';
          const when = new Date(entry.timestamp * 1000).toLocaleString();
          el.innerHTML = `<strong>${entry.callSign}</strong> ${entry.mode} ${entry.grid} | ${entry.rstSent}/${entry.rstRecv}<br><small>${when}</small><div>${entry.notes ?? ''}</div>`;
          logList.appendChild(el);
        });
      if (!data.entries.length) {
        logList.innerHTML = '<p>No QSOs logged yet.</p>';
      }
    } catch (e) {
      logList.innerHTML = `<p>Error: ${e.message}</p>`;
    }
  };

  document.getElementById('refreshStatus').addEventListener('click', refreshStatus);
  document.getElementById('refreshLogs').addEventListener('click', refreshLogs);

  document.getElementById('pttOn').addEventListener('click', () =>
    fetchJson('api/ptt', {
      method: 'POST',
      body: new URLSearchParams({ engage: 'true' }),
    })
      .then(refreshStatus)
      .catch((err) => alert(err.message)),
  );

  document.getElementById('pttOff').addEventListener('click', () =>
    fetchJson('api/ptt', {
      method: 'POST',
      body: new URLSearchParams({ engage: 'false' }),
    })
      .then(refreshStatus)
      .catch((err) => alert(err.message)),
  );

  transmitForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(transmitForm);
    try {
      await fetchJson('api/transmit', { method: 'POST', body: data });
      await refreshLogs();
      await refreshStatus();
      alert('Transmission queued and logged.');
    } catch (err) {
      alert(err.message);
    }
  });

  refreshStatus();
  refreshLogs();
})();