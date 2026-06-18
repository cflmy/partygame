(() => {
  'use strict';

  const API = 'api.php';
  const STORAGE_KEY = 'partygame_nb_state_v1';

  const panels = {
    setup: document.getElementById('panel-setup'),
    play: document.getElementById('panel-play'),
    result: document.getElementById('panel-result'),
  };

  const els = {
    playerInput: document.getElementById('player-input'),
    addPlayerBtn: document.getElementById('add-player-btn'),
    playerList: document.getElementById('player-list'),
    rangeGroup: document.getElementById('range-group'),
    startBtn: document.getElementById('start-btn'),
    backSetupBtn: document.getElementById('back-setup-btn'),
    backSetupResultBtn: document.getElementById('back-setup-result-btn'),
    rangeLow: document.getElementById('range-low'),
    rangeHigh: document.getElementById('range-high'),
    rangeBar: document.getElementById('range-bar'),
    currentPlayer: document.getElementById('current-player'),
    guessInput: document.getElementById('guess-input'),
    guessBtn: document.getElementById('guess-btn'),
    feedback: document.getElementById('feedback'),
    history: document.getElementById('history'),
    resultTitle: document.getElementById('result-title'),
    resultText: document.getElementById('result-text'),
    restartBtn: document.getElementById('restart-btn'),
  };

  const state = {
    players: [],
    rangeMin: 1,
    rangeMax: 100,
    gameMin: 1,
    gameMax: 100,
    low: 1,
    high: 100,
    playerIndex: 0,
    history: [],
  };

  function saveSetup() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        players: state.players,
        rangeMin: state.rangeMin,
        rangeMax: state.rangeMax,
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
      if (Array.isArray(data.players)) state.players = data.players.filter(Boolean);
      if (data.rangeMin) state.rangeMin = data.rangeMin;
      if (data.rangeMax) state.rangeMax = data.rangeMax;
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
    state.players.forEach((name, index) => {
      const chip = document.createElement('span');
      chip.className = 'game-chip';
      chip.innerHTML = `<span>${escapeHtml(name)}</span><button type="button" class="game-chip__remove" aria-label="移除">×</button>`;
      chip.querySelector('.game-chip__remove').addEventListener('click', () => {
        state.players.splice(index, 1);
        saveSetup();
        renderSetupPlayers();
      });
      els.playerList.appendChild(chip);
    });
  }

  function renderRangeButtons() {
    els.rangeGroup.querySelectorAll('.nb-range-btn').forEach((btn) => {
      const active = Number(btn.dataset.min) === state.rangeMin && Number(btn.dataset.max) === state.rangeMax;
      btn.classList.toggle('is-active', active);
    });
  }

  function currentPlayerName() {
    if (state.players.length === 0) return '玩家';
    return state.players[state.playerIndex % state.players.length];
  }

  function nextPlayer() {
    if (state.players.length === 0) return;
    state.playerIndex = (state.playerIndex + 1) % state.players.length;
  }

  function updateRangeUI() {
    els.rangeLow.textContent = String(state.low);
    els.rangeHigh.textContent = String(state.high);
    els.guessInput.min = String(state.low);
    els.guessInput.max = String(state.high);
    els.guessInput.placeholder = `${state.low} ~ ${state.high}`;

    const span = state.gameMax - state.gameMin;
    const left = ((state.low - state.gameMin) / span) * 100;
    const width = ((state.high - state.low) / span) * 100;
    els.rangeBar.style.left = `${left}%`;
    els.rangeBar.style.width = `${Math.max(width, 2)}%`;

    els.currentPlayer.innerHTML = `轮到 <strong>${escapeHtml(currentPlayerName())}</strong> 猜数字`;
  }

  function setFeedback(text, type) {
    els.feedback.textContent = text;
    els.feedback.className = 'nb-feedback' + (type ? ` nb-feedback--${type}` : '');
  }

  function renderHistory() {
    els.history.innerHTML = state.history
      .map((item) => `<div class="nb-history__item">${escapeHtml(item)}</div>`)
      .join('');
    els.history.scrollTop = els.history.scrollHeight;
  }

  async function apiStart() {
    const params = new URLSearchParams({
      action: 'start',
      min: String(state.rangeMin),
      max: String(state.rangeMax),
    });
    const res = await fetch(`${API}?${params.toString()}`);
    if (!res.ok) throw new Error('start failed');
    return res.json();
  }

  async function apiGuess(guess) {
    const params = new URLSearchParams({
      action: 'guess',
      guess: String(guess),
    });
    const res = await fetch(`${API}?${params.toString()}`);
    if (!res.ok) throw new Error('guess failed');
    return res.json();
  }

  async function startGame() {
    document.body.classList.add('is-loading');
    try {
      const data = await apiStart();
      state.gameMin = data.min;
      state.gameMax = data.max;
      state.low = data.low;
      state.high = data.high;
      state.playerIndex = 0;
      state.history = [];
      els.guessInput.value = '';
      setFeedback('在范围内猜一个数字，小心炸弹！', '');
      renderHistory();
      updateRangeUI();
      showPanel('play');
    } catch (_) {
      window.alert('游戏启动失败，请稍后重试。');
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function submitGuess() {
    const guess = Number(els.guessInput.value);
    if (!Number.isInteger(guess)) {
      setFeedback('请输入整数', '');
      return;
    }

    document.body.classList.add('is-loading');
    try {
      const data = await apiGuess(guess);

      if (data.error === 'out of range') {
        setFeedback(data.message || '超出当前范围', '');
        return;
      }

      const player = currentPlayerName();
      state.history.push(`${player} 猜了 ${guess}`);

      if (data.result === 'boom') {
        state.history.push(`💥 炸弹数字是 ${data.bomb}！`);
        renderHistory();
        els.resultTitle.textContent = `${player} 踩到炸弹！`;
        els.resultText.textContent = `炸弹数字是 ${data.bomb}，共猜测 ${data.turns} 次。${player} 接受惩罚，然后重新开始吧。`;
        showPanel('result');
        return;
      }

      state.low = data.low;
      state.high = data.high;
      if (data.result === 'low') {
        setFeedback(`${guess} 太小了，再大一点！`, 'low');
      } else {
        setFeedback(`${guess} 太大了，再小一点！`, 'high');
      }

      renderHistory();
      updateRangeUI();
      els.guessInput.value = '';
      nextPlayer();
      els.currentPlayer.innerHTML = `轮到 <strong>${escapeHtml(currentPlayerName())}</strong> 猜数字`;
    } catch (_) {
      window.alert('提交失败，请重试。');
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
      saveSetup();
      renderSetupPlayers();
    });

    els.playerInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        els.addPlayerBtn.click();
      }
    });

    els.rangeGroup.addEventListener('click', (event) => {
      const btn = event.target.closest('.nb-range-btn');
      if (!btn) return;
      state.rangeMin = Number(btn.dataset.min);
      state.rangeMax = Number(btn.dataset.max);
      saveSetup();
      renderRangeButtons();
    });

    els.startBtn.addEventListener('click', () => {
      saveSetup();
      startGame();
    });

    els.guessBtn.addEventListener('click', submitGuess);
    els.guessInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        submitGuess();
      }
    });

    els.restartBtn.addEventListener('click', startGame);
    els.backSetupBtn.addEventListener('click', () => showPanel('setup'));
    els.backSetupResultBtn.addEventListener('click', () => showPanel('setup'));
  }

  loadSetup();
  renderSetupPlayers();
  renderRangeButtons();
  bindEvents();
  showPanel('setup');
})();
