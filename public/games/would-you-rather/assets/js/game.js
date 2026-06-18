(() => {
  'use strict';

  const API = 'api.php';
  const STORAGE_KEY = 'partygame_wyr_state_v1';

  const panels = {
    setup: document.getElementById('panel-setup'),
    play: document.getElementById('panel-play'),
  };

  const els = {
    levelGroup: document.getElementById('level-group'),
    startBtn: document.getElementById('start-btn'),
    backSetupBtn: document.getElementById('back-setup-btn'),
    optionA: document.getElementById('option-a'),
    optionB: document.getElementById('option-b'),
    choiceA: document.getElementById('choice-a'),
    choiceB: document.getElementById('choice-b'),
    voteBarA: document.getElementById('vote-bar-a'),
    voteBarB: document.getElementById('vote-bar-b'),
    voteCountA: document.getElementById('vote-count-a'),
    voteCountB: document.getElementById('vote-count-b'),
    resetVotesBtn: document.getElementById('reset-votes-btn'),
    nextBtn: document.getElementById('next-btn'),
    swapBtn: document.getElementById('swap-btn'),
  };

  const state = {
    level: 'normal',
    currentKey: '',
    optionA: '',
    optionB: '',
    votesA: 0,
    votesB: 0,
  };

  function saveSetup() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ level: state.level }));
    } catch (_) {
      /* ignore */
    }
  }

  function loadSetup() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
      if (data.level) state.level = data.level;
    } catch (_) {
      /* ignore */
    }
  }

  function showPanel(name) {
    Object.entries(panels).forEach(([key, node]) => {
      node.classList.toggle('is-active', key === name);
    });
  }

  function renderLevelButtons() {
    els.levelGroup.querySelectorAll('.game-level-btn').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.level === state.level);
    });
  }

  function questionKey(optionA, optionB) {
    return `${optionA}|${optionB}`;
  }

  function resetVotes() {
    state.votesA = 0;
    state.votesB = 0;
    renderVotes();
  }

  function renderVotes() {
    const total = state.votesA + state.votesB;
    const pctA = total === 0 ? 50 : Math.round((state.votesA / total) * 100);
    const pctB = total === 0 ? 50 : 100 - pctA;

    els.voteBarA.style.width = `${pctA}%`;
    els.voteBarB.style.width = `${pctB}%`;
    els.voteCountA.textContent = String(state.votesA);
    els.voteCountB.textContent = String(state.votesB);
  }

  function renderQuestion() {
    els.optionA.textContent = state.optionA;
    els.optionB.textContent = state.optionB;
    renderVotes();
  }

  async function fetchQuestion() {
    const params = new URLSearchParams({ level: state.level });
    if (state.currentKey) params.set('exclude', state.currentKey);

    const res = await fetch(`${API}?${params.toString()}`);
    if (!res.ok) throw new Error('fetch failed');
    return res.json();
  }

  async function loadQuestion(resetVotesFlag) {
    document.body.classList.add('is-loading');
    try {
      const data = await fetchQuestion();
      state.optionA = data.option_a || '';
      state.optionB = data.option_b || '';
      state.currentKey = questionKey(state.optionA, state.optionB);
      if (resetVotesFlag) resetVotes();
      renderQuestion();
      showPanel('play');
    } catch (_) {
      window.alert('题目加载失败，请稍后重试。');
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  function bindEvents() {
    els.levelGroup.addEventListener('click', (event) => {
      const btn = event.target.closest('.game-level-btn');
      if (!btn) return;
      state.level = btn.dataset.level;
      saveSetup();
      renderLevelButtons();
    });

    els.startBtn.addEventListener('click', () => {
      saveSetup();
      loadQuestion(true);
    });

    els.choiceA.addEventListener('click', () => {
      state.votesA += 1;
      renderVotes();
    });

    els.choiceB.addEventListener('click', () => {
      state.votesB += 1;
      renderVotes();
    });

    els.resetVotesBtn.addEventListener('click', resetVotes);
    els.nextBtn.addEventListener('click', () => loadQuestion(true));
    els.swapBtn.addEventListener('click', () => loadQuestion(false));
    els.backSetupBtn.addEventListener('click', () => showPanel('setup'));
  }

  loadSetup();
  renderLevelButtons();
  bindEvents();
  showPanel('setup');
})();
