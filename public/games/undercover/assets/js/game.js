(() => {
  'use strict';

  const API = 'api.php';
  const STORAGE_KEY = 'partygame_uc_setup_v1';

  const panels = {
    mode: document.getElementById('panel-mode'),
    setup: document.getElementById('panel-setup'),
    reveal: document.getElementById('panel-reveal'),
    describe: document.getElementById('panel-describe'),
    vote: document.getElementById('panel-vote'),
    ended: document.getElementById('panel-ended'),
  };

  const els = {
    playerInput: document.getElementById('player-input'),
    addPlayerBtn: document.getElementById('add-player-btn'),
    playerList: document.getElementById('player-list'),
    undercoverGroup: document.getElementById('undercover-group'),
    startBtn: document.getElementById('start-btn'),
    revealHandoff: document.getElementById('reveal-handoff'),
    revealName: document.getElementById('reveal-name'),
    revealHidden: document.getElementById('reveal-hidden'),
    revealWordBlock: document.getElementById('reveal-word-block'),
    revealWord: document.getElementById('reveal-word'),
    showWordBtn: document.getElementById('show-word-btn'),
    confirmRevealBtn: document.getElementById('confirm-reveal-btn'),
    describeRound: document.getElementById('describe-round'),
    describeAlive: document.getElementById('describe-alive'),
    describeEliminated: document.getElementById('describe-eliminated'),
    beginVoteBtn: document.getElementById('begin-vote-btn'),
    voteGrid: document.getElementById('vote-grid'),
    eliminatedBox: document.getElementById('eliminated-box'),
    endedCard: document.getElementById('ended-card'),
    endedTitle: document.getElementById('ended-title'),
    endedText: document.getElementById('ended-text'),
    restartBtn: document.getElementById('restart-btn'),
    backSetupDescribeBtn: document.getElementById('back-setup-describe-btn'),
    backSetupVoteBtn: document.getElementById('back-setup-vote-btn'),
    backSetupEndedBtn: document.getElementById('back-setup-ended-btn'),
    backModeLocalBtn: document.getElementById('back-mode-local-btn'),
    modeLocalBtn: document.getElementById('mode-local-btn'),
  };

  const state = {
    players: [],
    undercoverCount: 1,
    wordVisible: false,
  };

  function saveSetup() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        players: state.players,
        undercoverCount: state.undercoverCount,
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
      if (data.undercoverCount) state.undercoverCount = data.undercoverCount;
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
        renderUndercoverButtons();
      });
      els.playerList.appendChild(chip);
    });
    renderUndercoverButtons();
  }

  function renderUndercoverButtons() {
    const allowTwo = state.players.length >= 8;
    els.undercoverGroup.querySelectorAll('.uc-role-btn').forEach((btn) => {
      const count = Number(btn.dataset.count);
      btn.classList.toggle('is-active', count === state.undercoverCount);
      btn.disabled = count === 2 && !allowTwo;
      btn.style.display = count === 2 && !allowTwo ? 'none' : '';
    });
  }

  async function apiGet(params) {
    const res = await fetch(`${API}?${params.toString()}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'request failed');
    return data;
  }

  async function startGame() {
    if (state.players.length < 4) {
      window.alert('至少需要 4 名玩家。');
      return;
    }

    document.body.classList.add('is-loading');
    try {
      const params = new URLSearchParams({
        action: 'start',
        players: state.players.join(','),
        undercovers: String(state.undercoverCount),
      });
      await apiGet(params);
      state.wordVisible = false;
      await loadReveal();
      showPanel('reveal');
    } catch (err) {
      window.alert('游戏启动失败：' + (err.message || '请重试'));
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function loadReveal() {
    const data = await apiGet(new URLSearchParams({ action: 'reveal' }));
    state.wordVisible = false;
    els.revealHandoff.textContent = `请把设备交给第 ${data.index + 1} / ${data.total} 位玩家`;
    els.revealName.textContent = data.name;
    els.revealWord.textContent = data.word;
    els.revealHidden.style.display = '';
    els.revealWordBlock.hidden = true;
    els.showWordBtn.style.display = '';
    els.confirmRevealBtn.disabled = true;
  }

  async function confirmReveal() {
    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({ action: 'confirm_reveal' }));
      if (data.phase === 'describe') {
        await renderDescribe(data);
        showPanel('describe');
      } else {
        await loadReveal();
      }
    } catch (err) {
      window.alert('操作失败：' + (err.message || '请重试'));
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function renderDescribe(data) {
    data = data || await apiGet(new URLSearchParams({ action: 'state' }));
    els.describeRound.textContent = String(data.round || 1);
    els.describeAlive.textContent = String(data.alive_count || 0);
    if (data.eliminated) {
      const roleLabel = data.eliminated.role === 'undercover' ? '卧底' : '平民';
      els.describeEliminated.hidden = false;
      els.describeEliminated.innerHTML = `<strong>${escapeHtml(data.eliminated.name)}</strong> 出局了！身份是 ${roleLabel}，词语是「${escapeHtml(data.eliminated.word)}」`;
    } else if (data.last_vote && data.phase === 'describe') {
      const roleLabel = data.last_vote.role === 'undercover' ? '卧底' : '平民';
      els.describeEliminated.hidden = false;
      els.describeEliminated.innerHTML = `<strong>${escapeHtml(data.last_vote.name)}</strong> 出局了！身份是 ${roleLabel}，词语是「${escapeHtml(data.last_vote.word)}」`;
    } else {
      els.describeEliminated.hidden = true;
    }
    if (data.phase === 'ended') {
      renderEnded(data);
      showPanel('ended');
    }
  }

  async function beginVote() {
    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({ action: 'begin_vote' }));
      renderVote(data);
      showPanel('vote');
    } catch (err) {
      window.alert('进入投票失败：' + (err.message || '请重试'));
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  function renderVote(data) {
    els.eliminatedBox.hidden = true;
    els.voteGrid.innerHTML = '';
    (data.players || []).forEach((player) => {
      if (!player.alive) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'uc-player-btn';
      btn.innerHTML = `
        <span class="uc-player-btn__name">${escapeHtml(player.name)}</span>
        <span class="uc-player-btn__status">点击投票出局</span>
      `;
      btn.addEventListener('click', () => submitVote(player.index));
      els.voteGrid.appendChild(btn);
    });
  }

  async function submitVote(index) {
    if (!window.confirm('确定投票让该玩家出局吗？')) return;

    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({ action: 'vote', index: String(index) }));
      if (data.eliminated) {
        const roleLabel = data.eliminated.role === 'undercover' ? '卧底' : '平民';
        els.eliminatedBox.hidden = false;
        els.eliminatedBox.innerHTML = `<strong>${escapeHtml(data.eliminated.name)}</strong> 出局了！身份是 ${roleLabel}，词语是「${escapeHtml(data.eliminated.word)}」`;
      }

      if (data.phase === 'ended') {
        renderEnded(data);
        showPanel('ended');
      } else {
        await renderDescribe(data);
        showPanel('describe');
      }
    } catch (err) {
      window.alert('投票失败：' + (err.message || '请重试'));
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  function renderEnded(data) {
    const winner = data.winner === 'undercover' ? '卧底' : '平民';
    els.endedCard.className = 'uc-result-card ' + (data.winner === 'undercover' ? 'uc-result-card--undercover' : 'uc-result-card--civilian');
    els.endedTitle.textContent = `${winner}阵营获胜！`;
    const pair = data.pair || {};
    els.endedText.textContent = `平民词：${pair.civilian || '—'}，卧底词：${pair.undercover || '—'}。感谢参与！`;
  }

  function bindEvents() {
    if (els.modeLocalBtn) {
      els.modeLocalBtn.addEventListener('click', () => showPanel('setup'));
    }
    if (els.backModeLocalBtn) {
      els.backModeLocalBtn.addEventListener('click', () => showPanel('mode'));
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

    els.undercoverGroup.addEventListener('click', (event) => {
      const btn = event.target.closest('.uc-role-btn');
      if (!btn || btn.disabled) return;
      state.undercoverCount = Number(btn.dataset.count);
      saveSetup();
      renderUndercoverButtons();
    });

    els.startBtn.addEventListener('click', () => {
      saveSetup();
      startGame();
    });

    els.showWordBtn.addEventListener('click', () => {
      state.wordVisible = true;
      els.revealHidden.style.display = 'none';
      els.revealWordBlock.hidden = false;
      els.showWordBtn.style.display = 'none';
      els.confirmRevealBtn.disabled = false;
    });

    els.confirmRevealBtn.addEventListener('click', () => {
      if (!state.wordVisible) return;
      confirmReveal();
    });

    els.beginVoteBtn.addEventListener('click', beginVote);
    els.restartBtn.addEventListener('click', startGame);

    [els.backSetupDescribeBtn, els.backSetupVoteBtn, els.backSetupEndedBtn]
      .filter(Boolean)
      .forEach((btn) => {
        btn.addEventListener('click', () => showPanel('setup'));
      });
  }

  loadSetup();
  renderSetupPlayers();
  bindEvents();
  showPanel('mode');
})();
