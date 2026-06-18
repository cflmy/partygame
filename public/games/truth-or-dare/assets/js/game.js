(() => {
  'use strict';

  const API = 'api.php';
  const STORAGE_KEY = 'partygame_tod_state_v1';

  const panels = {
    setup: document.getElementById('panel-setup'),
    spin: document.getElementById('panel-spin'),
    choose: document.getElementById('panel-choose'),
    reveal: document.getElementById('panel-reveal'),
  };

  const els = {
    playerInput: document.getElementById('player-input'),
    addPlayerBtn: document.getElementById('add-player-btn'),
    playerList: document.getElementById('player-list'),
    levelGroup: document.getElementById('level-group'),
    startBtn: document.getElementById('start-btn'),
    spinBtn: document.getElementById('spin-btn'),
    bottle: document.getElementById('bottle'),
    currentPlayer: document.getElementById('current-player'),
    currentHint: document.getElementById('current-hint'),
    choosePlayerLabel: document.getElementById('choose-player-label'),
    revealBadge: document.getElementById('reveal-badge'),
    revealText: document.getElementById('reveal-text'),
    revealPlayer: document.getElementById('reveal-player'),
    nextBtn: document.getElementById('next-btn'),
    swapBtn: document.getElementById('swap-btn'),
    backSetupBtn: document.getElementById('back-setup-btn'),
    backSetupChooseBtn: document.getElementById('back-setup-btn-choose'),
    truthCard: document.getElementById('truth-card'),
    dareCard: document.getElementById('dare-card'),
  };

  const state = {
    players: [],
    level: 'normal',
    currentPlayer: '',
    currentType: '',
    currentText: '',
    bottleRotation: 0,
    spinning: false,
  };

  function saveState() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        players: state.players,
        level: state.level,
      }));
    } catch (_) {
      /* ignore storage errors */
    }
  }

  function loadState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
      if (Array.isArray(data.players)) state.players = data.players.filter(Boolean);
      if (data.level) state.level = data.level;
    } catch (_) {
      /* ignore parse errors */
    }
  }

  function showPanel(name) {
    Object.entries(panels).forEach(([key, node]) => {
      node.classList.toggle('is-active', key === name);
    });
  }

  function renderPlayers() {
    els.playerList.innerHTML = '';
    state.players.forEach((name, index) => {
      const chip = document.createElement('span');
      chip.className = 'game-chip';
      chip.innerHTML = `<span>${escapeHtml(name)}</span><button type="button" class="game-chip__remove" aria-label="移除 ${escapeHtml(name)}">×</button>`;
      chip.querySelector('.game-chip__remove').addEventListener('click', () => {
        state.players.splice(index, 1);
        saveState();
        renderPlayers();
      });
      els.playerList.appendChild(chip);
    });
  }

  function renderLevelButtons() {
    els.levelGroup.querySelectorAll('.game-level-btn').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.level === state.level);
    });
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function hasRoomSession() {
    try {
      const raw = localStorage.getItem('partygame_tod_room_v1');
      if (!raw) return false;
      const data = JSON.parse(raw);
      return !!data.roomId;
    } catch (_) {
      return false;
    }
  }

  function pickRandomPlayer() {
    if (state.players.length === 0) {
      return '玩家';
    }
    const index = Math.floor(Math.random() * state.players.length);
    return state.players[index];
  }

  function spinBottle() {
    if (state.spinning) return;
    state.spinning = true;
    els.spinBtn.disabled = true;
    els.spinBtn.textContent = '转瓶中…';

    const extra = 1440 + Math.floor(Math.random() * 360);
    state.bottleRotation += extra;
    els.bottle.style.transform = `rotate(${state.bottleRotation}deg)`;

    window.setTimeout(() => {
      state.currentPlayer = pickRandomPlayer();
      els.currentPlayer.textContent = state.currentPlayer;
      els.currentHint.textContent = '轮到你啦！请选择真心话或大冒险';
      els.choosePlayerLabel.textContent = `当前玩家：${state.currentPlayer}`;
      state.spinning = false;
      els.spinBtn.disabled = false;
      els.spinBtn.textContent = '再转一次';
      showPanel('choose');
    }, 2900);
  }

  async function fetchQuestion(type) {
    const params = new URLSearchParams({
      type,
      level: state.level,
    });
    if (state.currentText) {
      params.set('exclude', state.currentText);
    }

    const res = await fetch(`${API}?${params.toString()}`);
    if (!res.ok) {
      throw new Error('fetch failed');
    }
    const data = await res.json();
    return data.text || '';
  }

  async function reveal(type) {
    document.body.classList.add('is-loading');
    try {
      const text = await fetchQuestion(type);
      state.currentType = type;
      state.currentText = text;
      els.revealBadge.textContent = type === 'truth' ? '真心话' : '大冒险';
      els.revealBadge.className = `game-reveal__badge game-reveal__badge--${type}`;
      els.revealText.textContent = text;
      els.revealPlayer.textContent = `${state.currentPlayer}，请诚实回答或完成挑战`;
      showPanel('reveal');
    } catch (_) {
      window.alert('题目加载失败，请稍后重试。');
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  function bindEvents() {
    const modeLocal = document.getElementById('mode-local-btn');
    if (modeLocal) {
      modeLocal.addEventListener('click', () => {
        document.getElementById('panel-mode')?.classList.remove('is-active');
        showPanel('setup');
      });
    }

    els.addPlayerBtn.addEventListener('click', () => {
      const name = els.playerInput.value.trim();
      if (!name) return;
      if (state.players.includes(name)) {
        window.alert('该昵称已存在。');
        return;
      }
      state.players.push(name);
      els.playerInput.value = '';
      saveState();
      renderPlayers();
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
      saveState();
      renderLevelButtons();
    });

    els.startBtn.addEventListener('click', () => {
      saveState();
      if (hasRoomSession()) return;
      els.currentPlayer.textContent = '准备开始';
      els.currentHint.textContent = '点击按钮转动瓶子，随机选出本轮玩家';
      els.spinBtn.textContent = '转动瓶子';
      showPanel('spin');
    });

    els.spinBtn.addEventListener('click', spinBottle);

    els.truthCard.addEventListener('click', () => reveal('truth'));
    els.dareCard.addEventListener('click', () => reveal('dare'));

    els.swapBtn.addEventListener('click', () => {
      if (hasRoomSession()) return;
      if (!state.currentType) return;
      reveal(state.currentType);
    });

    els.nextBtn.addEventListener('click', () => {
      state.currentText = '';
      els.currentHint.textContent = '点击按钮转动瓶子，随机选出下一位玩家';
      els.spinBtn.textContent = '转动瓶子';
      showPanel('spin');
    });

    els.backSetupBtn.addEventListener('click', () => {
      if (hasRoomSession()) return;
      showPanel('setup');
    });
    els.backSetupChooseBtn.addEventListener('click', () => {
      if (hasRoomSession()) return;
      showPanel('setup');
    });
  }

  loadState();
  renderPlayers();
  renderLevelButtons();
  bindEvents();
  if (!document.getElementById('panel-mode')) {
    showPanel('setup');
  }
})();
