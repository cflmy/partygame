(() => {
  'use strict';

  const API = 'api.php';
  const STORAGE_KEY = 'partygame_uc_room_v1';
  const POLL_MS = 2500;
  const VOTE_TIMEOUT_SEC = 60;

  const panels = {
    entry: document.getElementById('panel-room-entry'),
    lobby: document.getElementById('panel-room-lobby'),
    word: document.getElementById('panel-room-word'),
    describe: document.getElementById('panel-room-describe'),
    vote: document.getElementById('panel-room-vote'),
    spectate: document.getElementById('panel-room-spectate'),
    removed: document.getElementById('panel-room-removed'),
    ended: document.getElementById('panel-room-ended'),
  };

  const els = {
    modeRoomBtn: document.getElementById('mode-room-btn'),
    createName: document.getElementById('room-create-name'),
    createBtn: document.getElementById('room-create-btn'),
    joinCode: document.getElementById('room-join-code'),
    joinName: document.getElementById('room-join-name'),
    joinSpectate: document.getElementById('room-join-spectate'),
    joinBtn: document.getElementById('room-join-btn'),
    displayCode: document.getElementById('room-display-code'),
    copyCodeBtn: document.getElementById('room-copy-code-btn'),
    lobbyHint: document.getElementById('room-lobby-hint'),
    playerList: document.getElementById('room-player-list'),
    hostSettings: document.getElementById('room-host-settings'),
    startBtn: document.getElementById('room-start-btn'),
    leaveBtn: document.getElementById('room-leave-btn'),
    myName: document.getElementById('room-my-name'),
    wordHidden: document.getElementById('room-word-hidden'),
    wordBlock: document.getElementById('room-word-block'),
    myWord: document.getElementById('room-my-word'),
    wordSync: document.getElementById('room-word-sync'),
    showWordBtn: document.getElementById('room-show-word-btn'),
    recWordBtn: document.getElementById('room-rec-word-btn'),
    describeRound: document.getElementById('room-describe-round'),
    describeAlive: document.getElementById('room-describe-alive'),
    describeEliminated: document.getElementById('room-describe-eliminated'),
    beginVoteBtn: document.getElementById('room-begin-vote-btn'),
    recWordDescribeBtn: document.getElementById('room-rec-word-describe-btn'),
    voteHint: document.getElementById('room-vote-hint'),
    voteStatus: document.getElementById('room-vote-status'),
    voteGrid: document.getElementById('room-vote-grid'),
    voteTimer: document.getElementById('room-vote-timer'),
    voteSeconds: document.getElementById('room-vote-seconds'),
    watchTitle: document.getElementById('room-watch-title'),
    watchText: document.getElementById('room-watch-text'),
    watchList: document.getElementById('room-watch-list'),
    watchEliminated: document.getElementById('room-watch-eliminated'),
    watchVoteTimer: document.getElementById('room-watch-vote-timer'),
    watchVoteSeconds: document.getElementById('room-watch-vote-seconds'),
    endedCard: document.getElementById('room-ended-card'),
    endedTitle: document.getElementById('room-ended-title'),
    endedText: document.getElementById('room-ended-text'),
    leaveEndedBtn: document.getElementById('room-leave-ended-btn'),
    removedText: document.getElementById('room-removed-text'),
    undercoverGroup: document.getElementById('room-undercover-group'),
    lobbyUndercoverGroup: document.getElementById('room-lobby-undercover-group'),
  };

  const state = {
    roomId: '',
    token: '',
    isHost: false,
    isSpectator: false,
    name: '',
    undercoverCount: 1,
    wordVisible: false,
    pollTimer: null,
    voteDeadlineAt: 0,
  };

  const ERRORS = {
    'room not found': '房间不存在或已过期',
    'invalid token': '连接已失效，请重新加入',
    'host only': '仅房主可操作',
    'name taken': '昵称已被占用',
    'room full': '房间已满',
    'game already started': '游戏已开始',
    'already started': '游戏已开始',
    'need at least 4 players': '至少需要 4 名玩家',
    'already voted': '你已经投过票了',
    'cannot vote self': '不能投自己',
    'cannot kick after start': '游戏开始后无法踢人',
    'player not found': '玩家不存在',
  };

  function saveSession() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        roomId: state.roomId,
        token: state.token,
        isHost: state.isHost,
        isSpectator: state.isSpectator,
        name: state.name,
        undercoverCount: state.undercoverCount,
      }));
    } catch (_) {
      /* ignore */
    }
  }

  function clearSession() {
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch (_) {
      /* ignore */
    }
    state.roomId = '';
    state.token = '';
    state.isHost = false;
    state.isSpectator = false;
    state.voteDeadlineAt = 0;
  }

  function loadSession() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return false;
      const data = JSON.parse(raw);
      if (!data.roomId || !data.token) return false;
      state.roomId = data.roomId;
      state.token = data.token;
      state.isHost = !!data.isHost;
      state.isSpectator = !!data.isSpectator;
      state.name = data.name || '';
      state.undercoverCount = data.undercoverCount || 1;
      return true;
    } catch (_) {
      return false;
    }
  }

  function showPanel(name) {
    Object.entries(panels).forEach(([key, node]) => {
      if (node) node.classList.toggle('is-active', key === name);
    });
  }

  function showModePanel() {
    const modePanel = document.getElementById('panel-mode');
    if (modePanel) modePanel.classList.add('is-active');
    Object.values(panels).forEach((node) => {
      if (node) node.classList.remove('is-active');
    });
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function translateError(msg) {
    return ERRORS[msg] || msg || '操作失败';
  }

  async function apiGet(params) {
    const res = await fetch(`${API}?${params.toString()}`);
    const data = await res.json();
    if (!res.ok) {
      const err = new Error(translateError(data.error));
      err.code = data.error || '';
      throw err;
    }
    return data;
  }

  function stopPoll() {
    if (state.pollTimer) {
      clearInterval(state.pollTimer);
      state.pollTimer = null;
    }
  }

  function showRemoved(message) {
    stopPoll();
    const savedRoomId = state.roomId;
    clearSession();
    state.wordVisible = false;
    hideMainPanels();
    if (els.removedText) {
      els.removedText.textContent = message || '你已被房主移出房间。';
    }
    if (els.joinCode && savedRoomId) {
      els.joinCode.value = savedRoomId;
    }
    showPanel('removed');
  }

  function isKickedError(err) {
    return err && (err.code === 'kicked' || String(err.message || '').includes('移出'));
  }

  function startPoll() {
    stopPoll();
    state.pollTimer = setInterval(() => {
      refreshState().catch(() => {
        /* ignore transient errors */
      });
    }, POLL_MS);
  }

  function voteSecondsLeft(data) {
    if (typeof data.vote_seconds_left === 'number') {
      return Math.max(0, data.vote_seconds_left);
    }
    if (state.voteDeadlineAt > 0) {
      return Math.max(0, Math.ceil((state.voteDeadlineAt - Date.now()) / 1000));
    }
    return 0;
  }

  function syncVoteDeadline(data) {
    if ((data.phase || '') === 'vote') {
      const left = voteSecondsLeft(data);
      state.voteDeadlineAt = Date.now() + left * 1000;
    } else {
      state.voteDeadlineAt = 0;
    }
  }

  function renderVoteTimer(data, timerEl, secondsEl) {
    if (!timerEl || !secondsEl) return;
    if ((data.phase || '') !== 'vote') {
      timerEl.hidden = true;
      return;
    }
    const left = voteSecondsLeft(data);
    timerEl.hidden = false;
    secondsEl.textContent = String(left);
  }

  function renderLobby(data) {
    els.displayCode.textContent = data.room_id || state.roomId;
    els.playerList.innerHTML = '';

    (data.players || []).forEach((player) => {
      const li = document.createElement('li');
      li.className = 'uc-room-player-item';
      const badges = [];
      if (player.is_host) badges.push('房主');
      const kickBtn = state.isHost && !player.is_host
        ? `<button type="button" class="uc-kick-btn" data-target="${escapeHtml(player.id)}" data-type="player">踢出</button>`
        : '';
      li.innerHTML = `
        <span class="uc-room-player-item__name">${escapeHtml(player.name)}</span>
        <span class="uc-room-player-item__actions">
          ${badges.length ? `<span class="uc-room-player-item__badge">${badges.join(' · ')}</span>` : ''}
          ${kickBtn}
        </span>
      `;
      els.playerList.appendChild(li);
    });

    (data.spectators || []).forEach((spectator) => {
      const li = document.createElement('li');
      li.className = 'uc-room-player-item uc-room-player-item--spectator';
      const kickBtn = state.isHost
        ? `<button type="button" class="uc-kick-btn" data-target="${escapeHtml(spectator.id)}" data-type="spectator">踢出</button>`
        : '';
      li.innerHTML = `
        <span class="uc-room-player-item__name">${escapeHtml(spectator.name)}</span>
        <span class="uc-room-player-item__actions">
          <span class="uc-room-player-item__badge">观战</span>
          ${kickBtn}
        </span>
      `;
      els.playerList.appendChild(li);
    });

    const count = data.player_count || 0;
    const specCount = data.spectator_count || 0;
    els.lobbyHint.textContent = count < 4
      ? `已加入 ${count} 人${specCount ? `，${specCount} 人观战` : ''}，至少 4 人后可开始`
      : `已加入 ${count} 人${specCount ? `，${specCount} 人观战` : ''}，可以开始游戏`;

    if (state.isHost) {
      els.hostSettings.hidden = false;
      els.startBtn.hidden = false;
      els.startBtn.disabled = count < 4;
      renderUndercoverButtons(els.lobbyUndercoverGroup, count, data.undercover_count || state.undercoverCount);
    } else {
      els.hostSettings.hidden = true;
      els.startBtn.hidden = true;
    }
  }

  function renderUndercoverButtons(group, playerCount, activeCount) {
    if (!group) return;
    const allowTwo = playerCount >= 8;
    group.querySelectorAll('.uc-role-btn').forEach((btn) => {
      const count = Number(btn.dataset.count);
      btn.classList.toggle('is-active', count === activeCount);
      btn.disabled = count === 2 && !allowTwo;
      btn.style.display = count === 2 && !allowTwo ? 'none' : '';
    });
    state.undercoverCount = activeCount;
  }

  function renderElimination(box, info) {
    if (!info || !box) return;
    const roleLabel = info.role === 'undercover' ? '卧底' : '平民';
    box.hidden = false;
    box.innerHTML = `<strong>${escapeHtml(info.name)}</strong> 出局了！身份是 ${roleLabel}，词语是「${escapeHtml(info.word)}」`;
  }

  function renderVote(data) {
    renderVoteTimer(data, els.voteTimer, els.voteSeconds);
    els.voteGrid.innerHTML = '';
    const me = data.me || {};

    if (me.has_voted) {
      els.voteHint.textContent = '你已投票，等待其他玩家…';
      els.voteStatus.hidden = false;
      els.voteStatus.textContent = `投票进度：${data.vote_count || 0} / ${data.vote_needed || 0}`;
    } else if (!me.alive) {
      els.voteHint.textContent = '你已被淘汰，转为观战';
    } else {
      els.voteHint.textContent = '点击你认为最可疑的玩家';
      els.voteStatus.hidden = true;
    }

    (data.players || []).forEach((player) => {
      if (!player.alive) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'uc-player-btn';
      btn.disabled = !me.alive || me.has_voted || player.id === me.id;
      btn.innerHTML = `
        <span class="uc-player-btn__name">${escapeHtml(player.name)}</span>
        <span class="uc-player-btn__status">${player.id === me.id ? '这是你' : '点击投票'}</span>
      `;
      btn.addEventListener('click', () => submitVote(player.id));
      els.voteGrid.appendChild(btn);
    });
  }

  function renderWatch(data) {
    const phase = data.phase || 'lobby';
    const phaseLabels = {
      lobby: '等待开始',
      word: '查看词语中',
      describe: '描述阶段',
      vote: '投票阶段',
      ended: '游戏结束',
    };

    els.watchTitle.textContent = state.isSpectator ? '观战中' : '你已出局 · 观战中';
    renderVoteTimer(data, els.watchVoteTimer, els.watchVoteSeconds);

    if (phase === 'lobby') {
      els.watchText.textContent = `房间 ${data.room_id || state.roomId} · 等待房主开始（${data.player_count || 0} 人）`;
    } else if (phase === 'word') {
      els.watchText.textContent = `已有 ${data.word_seen_count || 0} / ${data.alive_count || 0} 人查看词语`;
    } else if (phase === 'describe') {
      els.watchText.textContent = `第 ${data.round || 1} 轮描述 · 存活 ${data.alive_count || 0} 人`;
    } else if (phase === 'vote') {
      els.watchText.textContent = `投票进行中 · ${data.vote_count || 0} / ${data.vote_needed || 0} 人已投票`;
    } else if (phase === 'ended') {
      const winner = data.winner === 'undercover' ? '卧底' : '平民';
      els.watchText.textContent = `${winner}阵营获胜`;
    } else {
      els.watchText.textContent = phaseLabels[phase] || '同步中…';
    }

    els.watchList.innerHTML = '';
    (data.players || []).forEach((player) => {
      const li = document.createElement('li');
      li.className = 'uc-room-player-item' + (player.alive ? '' : ' is-out');
      const tags = [];
      if (player.is_host) tags.push('房主');
      if (!player.alive && phase !== 'lobby') tags.push('出局');
      if (phase === 'vote' && player.has_voted) tags.push('已投票');
      li.innerHTML = `
        <span class="uc-room-player-item__name">${escapeHtml(player.name)}</span>
        ${tags.length ? `<span class="uc-room-player-item__badge">${tags.join(' · ')}</span>` : ''}
      `;
      els.watchList.appendChild(li);
    });

    if (data.last_vote && phase !== 'lobby') {
      renderElimination(els.watchEliminated, data.last_vote);
    } else {
      els.watchEliminated.hidden = true;
    }
  }

  function renderEnded(data) {
    const winner = data.winner === 'undercover' ? '卧底' : '平民';
    els.endedCard.className = 'uc-result-card ' + (data.winner === 'undercover' ? 'uc-result-card--undercover' : 'uc-result-card--civilian');
    els.endedTitle.textContent = `${winner}阵营获胜！`;
    const pair = data.pair || {};
    els.endedText.textContent = `平民词：${pair.civilian || '—'}，卧底词：${pair.undercover || '—'}。感谢参与！`;
  }

  function hideMainPanels() {
    const modePanel = document.getElementById('panel-mode');
    if (modePanel) modePanel.classList.remove('is-active');
    ['panel-setup', 'panel-reveal', 'panel-describe', 'panel-vote', 'panel-ended'].forEach((id) => {
      const node = document.getElementById(id);
      if (node) node.classList.remove('is-active');
    });
  }

  function shouldWatch(data) {
    const me = data.me || {};
    if (me.is_spectator) return true;
    if ((data.phase || '') !== 'lobby' && me.alive === false) return true;
    return false;
  }

  function applyState(data) {
    if (!data) return;
    hideMainPanels();
    state.isHost = !!(data.me && data.me.is_host);
    state.isSpectator = !!(data.me && data.me.is_spectator);
    state.name = (data.me && data.me.name) || state.name;
    if (data.undercover_count) state.undercoverCount = data.undercover_count;
    syncVoteDeadline(data);

    const phase = data.phase || 'lobby';

    if (phase === 'ended') {
      renderEnded(data);
      showPanel('ended');
      return;
    }

    if (shouldWatch(data)) {
      renderWatch(data);
      showPanel('spectate');
      return;
    }

    if (phase === 'lobby') {
      renderLobby(data);
      showPanel('lobby');
      return;
    }

    if (phase === 'word') {
      els.myName.textContent = state.name;
      els.wordSync.textContent = `已有 ${data.word_seen_count || 0} / ${data.alive_count || 0} 人查看词语`;
      if (!state.wordVisible) {
        els.wordHidden.style.display = '';
        els.wordBlock.hidden = true;
        els.showWordBtn.style.display = '';
      }
      showPanel('word');
      return;
    }

    if (phase === 'describe') {
      els.describeRound.textContent = String(data.round || 1);
      els.describeAlive.textContent = String(data.alive_count || 0);
      if (data.eliminated || data.last_vote) {
        renderElimination(els.describeEliminated, data.eliminated || data.last_vote);
      } else {
        els.describeEliminated.hidden = true;
      }
      els.beginVoteBtn.hidden = !state.isHost;
      showPanel('describe');
      return;
    }

    if (phase === 'vote') {
      renderVote(data);
      showPanel('vote');
      return;
    }
  }

  async function refreshState() {
    if (!state.roomId || !state.token) return null;
    try {
      const data = await apiGet(new URLSearchParams({
        action: 'room_state',
        room: state.roomId,
        token: state.token,
      }));
      applyState(data);
      return data;
    } catch (err) {
      if (isKickedError(err)) {
        showRemoved('你已被房主移出房间，可以重新加入或返回。');
        return null;
      }
      throw err;
    }
  }

  async function createRoom() {
    const name = els.createName.value.trim();
    if (!name) {
      window.alert('请输入昵称。');
      return;
    }

    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({
        action: 'room_create',
        name,
        undercovers: String(state.undercoverCount),
      }));
      state.roomId = data.room_id;
      state.token = data.token;
      state.isHost = true;
      state.isSpectator = false;
      state.name = name;
      saveSession();
      startPoll();
      if (data.state) {
        applyState(data.state);
      } else {
        await refreshState();
      }
    } catch (err) {
      window.alert(err.message);
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function joinRoom() {
    const roomId = (els.joinCode.value || '').replace(/\D/g, '');
    const name = els.joinName.value.trim();
    const asSpectator = !!(els.joinSpectate && els.joinSpectate.checked);

    if (roomId.length !== 6) {
      window.alert('请输入 6 位房间号。');
      return;
    }
    if (!name) {
      window.alert('请输入昵称。');
      return;
    }

    document.body.classList.add('is-loading');
    try {
      const params = new URLSearchParams({
        action: 'room_join',
        room: roomId,
        name,
      });
      if (asSpectator) params.set('spectate', '1');

      const data = await apiGet(params);
      state.roomId = data.room_id || roomId;
      state.token = data.token;
      state.isHost = !!data.is_host;
      state.isSpectator = !!data.is_spectator || asSpectator;
      state.name = name;
      saveSession();
      startPoll();
      if (data.state) {
        applyState(data.state);
      } else {
        await refreshState();
      }
    } catch (err) {
      window.alert(err.message);
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function kickMember(targetId, type) {
    if (!window.confirm('确定踢出该成员吗？')) return;

    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({
        action: 'room_kick',
        room: state.roomId,
        token: state.token,
        target: targetId,
        type,
      }));
      if (data.state) applyState(data.state);
    } catch (err) {
      window.alert(err.message);
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function startGame() {
    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({
        action: 'room_start',
        room: state.roomId,
        token: state.token,
      }));
      if (data.state) applyState(data.state);
    } catch (err) {
      window.alert(err.message);
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function fetchWord() {
    const data = await apiGet(new URLSearchParams({
      action: 'room_word',
      room: state.roomId,
      token: state.token,
    }));
    state.wordVisible = true;
    els.myWord.textContent = data.word || '—';
    els.wordHidden.style.display = 'none';
    els.wordBlock.hidden = false;
    els.showWordBtn.style.display = 'none';
    els.recWordBtn.hidden = false;
    if (data.state) applyState(data.state);
  }

  async function beginVote() {
    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({
        action: 'room_begin_vote',
        room: state.roomId,
        token: state.token,
      }));
      if (data.state) applyState(data.state);
    } catch (err) {
      window.alert(err.message);
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function submitVote(targetId) {
    if (!window.confirm('确定投票让该玩家出局吗？')) return;

    document.body.classList.add('is-loading');
    try {
      const data = await apiGet(new URLSearchParams({
        action: 'room_vote',
        room: state.roomId,
        token: state.token,
        target: targetId,
      }));
      if (data.state) applyState(data.state);
    } catch (err) {
      window.alert(err.message);
    } finally {
      document.body.classList.remove('is-loading');
    }
  }

  async function updateUndercover(count) {
    if (!state.isHost || !state.roomId) return;
    try {
      const data = await apiGet(new URLSearchParams({
        action: 'room_settings',
        room: state.roomId,
        token: state.token,
        undercovers: String(count),
      }));
      if (data.state) applyState(data.state);
    } catch (err) {
      window.alert(err.message);
    }
  }

  function leaveRoom() {
    stopPoll();
    clearSession();
    state.wordVisible = false;
    showModePanel();
  }

  function bindUndercoverGroup(group, localOnly) {
    if (!group) return;
    group.addEventListener('click', (event) => {
      const btn = event.target.closest('.uc-role-btn');
      if (!btn || btn.disabled) return;
      const count = Number(btn.dataset.count);
      state.undercoverCount = count;
      if (localOnly) {
        group.querySelectorAll('.uc-role-btn').forEach((b) => {
          b.classList.toggle('is-active', Number(b.dataset.count) === count);
        });
        return;
      }
      updateUndercover(count);
    });
  }

  function bindTabs() {
    document.querySelectorAll('.uc-room-tab').forEach((tab) => {
      tab.addEventListener('click', () => {
        const name = tab.dataset.tab;
        document.querySelectorAll('.uc-room-tab').forEach((t) => {
          t.classList.toggle('is-active', t === tab);
        });
        document.getElementById('room-tab-create').classList.toggle('is-active', name === 'create');
        document.getElementById('room-tab-join').classList.toggle('is-active', name === 'join');
      });
    });
  }

  function bindEvents() {
    if (els.modeRoomBtn) {
      els.modeRoomBtn.addEventListener('click', () => showPanel('entry'));
    }

    els.createBtn.addEventListener('click', createRoom);
    els.joinBtn.addEventListener('click', joinRoom);
    els.startBtn.addEventListener('click', startGame);
    els.showWordBtn.addEventListener('click', () => {
      fetchWord().catch((err) => window.alert(err.message));
    });
    els.recWordBtn.addEventListener('click', () => {
      fetchWord().catch((err) => window.alert(err.message));
    });
    els.recWordDescribeBtn.addEventListener('click', () => {
      fetchWord().catch((err) => window.alert(err.message));
    });
    els.beginVoteBtn.addEventListener('click', beginVote);

    els.playerList.addEventListener('click', (event) => {
      const btn = event.target.closest('.uc-kick-btn');
      if (!btn) return;
      kickMember(btn.dataset.target, btn.dataset.type || 'player');
    });

    els.copyCodeBtn.addEventListener('click', async () => {
      const code = state.roomId || els.displayCode.textContent;
      try {
        await navigator.clipboard.writeText(code);
        window.alert('房间号已复制');
      } catch (_) {
        window.prompt('复制房间号', code);
      }
    });

    [els.leaveBtn, els.leaveEndedBtn].forEach((btn) => {
      btn.addEventListener('click', leaveRoom);
    });

    const watchLeaveBtn = document.getElementById('room-watch-leave-btn');
    if (watchLeaveBtn) {
      watchLeaveBtn.addEventListener('click', leaveRoom);
    }

    const rejoinBtn = document.getElementById('room-removed-rejoin-btn');
    if (rejoinBtn) {
      rejoinBtn.addEventListener('click', () => showPanel('entry'));
    }
    const removedHomeBtn = document.getElementById('room-removed-home-btn');
    if (removedHomeBtn) {
      removedHomeBtn.addEventListener('click', showModePanel);
    }

    bindUndercoverGroup(els.undercoverGroup, true);
    bindUndercoverGroup(els.lobbyUndercoverGroup, false);
    bindTabs();
  }

  async function tryResume() {
    if (!loadSession()) return;
    try {
      await refreshState();
      startPoll();
    } catch (_) {
      clearSession();
    }
  }

  bindEvents();
  tryResume();
})();
