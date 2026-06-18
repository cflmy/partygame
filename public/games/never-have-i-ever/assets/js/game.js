(() => {
  'use strict';

  const API = 'api.php';
  const STORAGE_KEY = 'partygame_nhie_state_v1';

  const panels = {
    setup: document.getElementById('panel-setup'),
    play: document.getElementById('panel-play'),
  };

  const els = {
    playerInput: document.getElementById('player-input'),
    addPlayerBtn: document.getElementById('add-player-btn'),
    playerList: document.getElementById('player-list'),
    levelGroup: document.getElementById('level-group'),
    fingerGroup: document.getElementById('finger-group'),
    startBtn: document.getElementById('start-btn'),
    backSetupBtn: document.getElementById('back-setup-btn'),
    statementText: document.getElementById('statement-text'),
    roundHint: document.getElementById('round-hint'),
    playersSection: document.getElementById('players-section'),
    playersGrid: document.getElementById('players-grid'),
    nextBtn: document.getElementById('next-btn'),
    swapBtn: document.getElementById('swap-btn'),
  };

  const state = {
    players: [],
    level: 'normal',
    initialFingers: 3,
    currentText: '',
    roundMarked: new Set(),
  };

  function saveSetup() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        players: state.players.map((p) => p.name),
        level: state.level,
        initialFingers: state.initialFingers,
      }));
    } catch (_) {
      /* ignore */
    }
  }

  function loadSetup() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
      if (data.initialFingers) state.initialFingers = data.initialFingers;
      if (data.level) state.level = data.level;
      if (Array.isArray(data.players)) {
        state.players = data.players.filter(Boolean).map((name) => ({
          name,
          fingers: state.initialFingers,
        }));
      }
    } catch (_) {
      /* ignore */
    }
  }

  function showPanel(name) {
    Object.entries(panels).forEach(([key, node]) => {
      node.classList.toggle('is-active', key === name);
    });
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderSetupPlayers() {
    els.playerList.innerHTML = '';
    state.players.forEach((player, index) => {
      const chip = document.createElement('span');
      chip.className = 'game-chip';
      chip.innerHTML = `<span>${escapeHtml(player.name)}</span><button type="button" class="game-chip__remove" aria-label="移除">×</button>`;
      chip.querySelector('.game-chip__remove').addEventListener('click', () => {
        state.players.splice(index, 1);
        saveSetup();
        renderSetupPlayers();
      });
      els.playerList.appendChild(chip);
    });
  }

  function renderLevelButtons() {
    els.levelGroup.querySelectorAll('.game-level-btn').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.level === state.level);
    });
  }

  function renderFingerButtons() {
    els.fingerGroup.querySelectorAll('.nhie-finger-btn').forEach((btn) => {
      btn.classList.toggle('is-active', Number(btn.dataset.fingers) === state.initialFingers);
    });
  }

  function fingerEmoji(count) {
    if (count <= 0) return '—';
    return '🖐'.repeat(Math.min(count, 10));
  }

  function renderPlayPlayers() {
    els.playersGrid.innerHTML = '';
    if (state.players.length === 0) {
      els.playersSection.classList.add('is-hidden');
      els.roundHint.textContent = '做过的人请自觉接受惩罚或喝一口，然后进入下一条。';
      return;
    }

    els.playersSection.classList.remove('is-hidden');
    els.roundHint.textContent = '做过这件事的人点击自己的昵称放下 1 根手指，全部确认后进入下一条。';

    state.players.forEach((player, index) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'nhie-player-card';
      if (player.fingers <= 0) btn.classList.add('is-out');
      if (state.roundMarked.has(index)) btn.classList.add('is-marked');
      btn.disabled = player.fingers <= 0 || state.roundMarked.has(index);

      btn.innerHTML = `
        <span class="nhie-player-card__name">${escapeHtml(player.name)}</span>
        <span class="nhie-player-card__fingers">${fingerEmoji(player.fingers)}</span>
        <span class="nhie-player-card__hint">${player.fingers <= 0 ? '已出局' : '点我：我做过'}</span>
      `;

      btn.addEventListener('click', () => {
        if (player.fingers <= 0 || state.roundMarked.has(index)) return;
        player.fingers -= 1;
        state.roundMarked.add(index);
        renderPlayPlayers();
      });

      els.playersGrid.appendChild(btn);
    });
  }

  async function fetchStatement() {
    const params = new URLSearchParams({ level: state.level });
    if (state.currentText) params.set('exclude', state.currentText);

    const res = await fetch(`${API}?${params.toString()}`);
    if (!res.ok) throw new Error('fetch failed');
    const data = await res.json();
    return data.text || '';
  }

  async function loadStatement(resetRound) {
    document.body.classList.add('is-loading');
    try {
      const text = await fetchStatement();
      state.currentText = text;
      if (resetRound) state.roundMarked.clear();
      els.statementText.textContent = text;
      renderPlayPlayers();
      showPanel('play');
    } catch (_) {
      window.alert('题目加载失败，请稍后重试。');
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  function bindEvents() {
    els.addPlayerBtn.addEventListener('click', () => {
      const name = els.playerInput.value.trim();
      if (!name) return;
      if (state.players.some((p) => p.name === name)) {
        window.alert('该昵称已存在。');
        return;
      }
      state.players.push({ name, fingers: state.initialFingers });
      els.playerInput.value = '';
      saveSetup();
      renderSetupPlayers();
    });

    els.playerInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        els.addPlayerBtn.click();
      }
    });

    els.levelGroup.addEventListener('click', (event) => {
      const btn = event.target.closest('.game-level-btn');
      if (!btn) return;
      state.level = btn.dataset.level;
      saveSetup();
      renderLevelButtons();
    });

    els.fingerGroup.addEventListener('click', (event) => {
      const btn = event.target.closest('.nhie-finger-btn');
      if (!btn) return;
      state.initialFingers = Number(btn.dataset.fingers);
      state.players = state.players.map((p) => ({ ...p, fingers: state.initialFingers }));
      saveSetup();
      renderFingerButtons();
    });

    els.startBtn.addEventListener('click', () => {
      state.players = state.players.map((p) => ({
        name: p.name,
        fingers: state.initialFingers,
      }));
      saveSetup();
      loadStatement(true);
    });

    els.nextBtn.addEventListener('click', () => loadStatement(true));
    els.swapBtn.addEventListener('click', () => loadStatement(false));
    els.backSetupBtn.addEventListener('click', () => showPanel('setup'));
  }

  loadSetup();
  renderSetupPlayers();
  renderLevelButtons();
  renderFingerButtons();
  bindEvents();
  showPanel('setup');
})();
