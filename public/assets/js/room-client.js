/**
 * Shared room client for party games.
 * Usage: PartyRoom.create({ gameSlug, storageKey, minPlayers, onState })
 */
(() => {
  'use strict';

  const POLL_MS = 2500;

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /** Hide host-only action buttons for non-host players. */
  function setHostActions(ids, isHost) {
    ids.forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.hidden = !isHost;
    });
  }

  /** Show a waiting banner inside a panel (e.g. while host controls the flow). */
  function setWaitBanner(panel, show, message) {
    if (!panel) return;
    let banner = panel.querySelector('.pg-wait-banner');
    if (show) {
      if (!banner) {
        banner = document.createElement('p');
        banner.className = 'pg-wait-banner';
        panel.insertBefore(banner, panel.firstElementChild);
      }
      banner.textContent = message;
      banner.hidden = false;
    } else if (banner) {
      banner.hidden = true;
    }
  }

  window.PartyRoom = {
    setHostActions,
    setWaitBanner,
    create(config) {
      const {
        gameSlug = '',
        storageKey,
        minPlayers = 2,
        onState = null,
      } = config;

      const panels = {
        entry: document.getElementById('panel-room-entry'),
        lobby: document.getElementById('panel-room-lobby'),
        removed: document.getElementById('panel-room-removed'),
        spectate: document.getElementById('panel-room-spectate'),
      };

      const els = {
        modeLocalBtn: document.getElementById('mode-local-btn'),
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
        startBtn: document.getElementById('room-start-btn'),
        hostStartWrap: document.getElementById('room-host-start-wrap'),
        leaveBtn: document.getElementById('room-leave-btn'),
        leaveHint: document.getElementById('room-leave-hint'),
        dissolveBtn: document.getElementById('room-dissolve-btn'),
        hostActions: document.getElementById('room-host-actions'),
        playerActions: document.getElementById('room-player-actions'),
        lobbyBannerHost: document.getElementById('room-lobby-banner-host'),
        lobbyBannerPlayer: document.getElementById('room-lobby-banner-player'),
        actionBar: document.getElementById('room-action-bar'),
        removedText: document.getElementById('room-removed-text'),
        watchTitle: document.getElementById('room-watch-title'),
        watchText: document.getElementById('room-watch-text'),
        watchList: document.getElementById('room-watch-list'),
      };

      const state = {
        roomId: '',
        token: '',
        isHost: false,
        isSpectator: false,
        name: '',
        pollTimer: null,
      };

      function saveSession() {
        try {
          localStorage.setItem(storageKey, JSON.stringify({
            roomId: state.roomId,
            token: state.token,
            isHost: state.isHost,
            isSpectator: state.isSpectator,
            name: state.name,
          }));
        } catch (_) { /* ignore */ }
      }

      function clearSession() {
        try { localStorage.removeItem(storageKey); } catch (_) { /* ignore */ }
        state.roomId = '';
        state.token = '';
        state.isHost = false;
        state.isSpectator = false;
      }

      function loadSession() {
        try {
          const raw = localStorage.getItem(storageKey);
          if (!raw) return false;
          const data = JSON.parse(raw);
          if (!data.roomId || !data.token) return false;
          state.roomId = data.roomId;
          state.token = data.token;
          state.isHost = !!data.isHost;
          state.isSpectator = !!data.isSpectator;
          state.name = data.name || '';
          return true;
        } catch (_) {
          return false;
        }
      }

      function showPanel(name) {
        Object.entries(panels).forEach(([key, node]) => {
          if (node) node.classList.toggle('is-active', key === name);
        });
        hideLocalPanels();
      }

      function hideModePanel() {
        const modePanel = document.getElementById('panel-mode');
        if (modePanel) modePanel.classList.remove('is-active');
      }

      function hideLocalPanels() {
        document.querySelectorAll('.game-panel:not([id^="panel-room"])').forEach((node) => {
          node.classList.remove('is-active');
        });
      }

      function showModePanel() {
        hideLocalPanels();
        Object.entries(panels).forEach(([, node]) => {
          if (node) node.classList.remove('is-active');
        });
        const modePanel = document.getElementById('panel-mode');
        if (modePanel) modePanel.classList.add('is-active');
      }

      function bindRoomTabs() {
        document.querySelectorAll('.pg-room-tab').forEach((tab) => {
          tab.addEventListener('click', () => {
            const name = tab.dataset.tab;
            document.querySelectorAll('.pg-room-tab').forEach((t) => {
              t.classList.toggle('is-active', t === tab);
            });
            const createPanel = document.getElementById('room-tab-create');
            const joinPanel = document.getElementById('room-tab-join');
            if (createPanel) createPanel.classList.toggle('is-active', name === 'create');
            if (joinPanel) joinPanel.classList.toggle('is-active', name === 'join');
          });
        });
      }

      function setRoomActionBar(isHost, isSpectator = false) {
        if (els.actionBar) {
          els.actionBar.hidden = !state.roomId;
        }
        if (els.hostActions) {
          els.hostActions.hidden = !isHost;
        }
        if (els.playerActions) {
          els.playerActions.hidden = isHost;
        }
        if (els.leaveBtn) {
          els.leaveBtn.textContent = isSpectator ? '退出观战' : '退出房间';
        }
        if (els.leaveHint) {
          els.leaveHint.textContent = isSpectator
            ? '退出后将离开观战'
            : '退出后将离开本局联机';
        }
      }

      function setLobbyRoleUI(isHost) {
        if (els.lobbyBannerHost) {
          els.lobbyBannerHost.hidden = !isHost;
        }
        if (els.lobbyBannerPlayer) {
          els.lobbyBannerPlayer.hidden = isHost;
        }
        if (els.hostStartWrap) {
          els.hostStartWrap.hidden = !isHost;
        }
      }

      function hideRoomActionBar() {
        if (els.actionBar) els.actionBar.hidden = true;
        if (els.hostActions) els.hostActions.hidden = true;
        if (els.playerActions) els.playerActions.hidden = true;
        if (els.lobbyBannerHost) els.lobbyBannerHost.hidden = true;
        if (els.lobbyBannerPlayer) els.lobbyBannerPlayer.hidden = true;
        if (els.hostStartWrap) els.hostStartWrap.hidden = true;
      }

      async function leaveRoom() {
        if (state.roomId && state.token) {
          try {
            const params = baseParams();
            params.set('action', 'room_leave');
            await apiGet(params);
          } catch (_) { /* room may already be gone */ }
        }
        stopPoll();
        clearSession();
        hideRoomActionBar();
        showModePanel();
      }

      async function dissolveRoom() {
        if (!state.isHost || !state.roomId) return;
        if (!window.confirm('确定解散房间吗？所有玩家将被移出。')) return;
        try {
          const params = baseParams();
          params.set('action', 'room_dissolve');
          await apiGet(params);
        } catch (_) { /* ignore */ }
        stopPoll();
        clearSession();
        hideRoomActionBar();
        showModePanel();
      }

      function renderLobby(data) {
        els.displayCode.textContent = data.room_id || state.roomId;
        els.playerList.innerHTML = '';

        (data.players || []).forEach((player) => {
          const kickBtn = state.isHost && !player.is_host
            ? `<button type="button" class="pg-kick-btn" data-target="${escapeHtml(player.id)}" data-type="player">踢出</button>`
            : '';
          const li = document.createElement('li');
          li.className = 'pg-room-player-item';
          li.innerHTML = `
            <span>${escapeHtml(player.name)}${player.is_host ? ' · 房主' : ''}</span>
            <span class="pg-room-player-item__actions">${kickBtn}</span>
          `;
          els.playerList.appendChild(li);
        });

        (data.spectators || []).forEach((spectator) => {
          const kickBtn = state.isHost
            ? `<button type="button" class="pg-kick-btn" data-target="${escapeHtml(spectator.id)}" data-type="spectator">踢出</button>`
            : '';
          const li = document.createElement('li');
          li.className = 'pg-room-player-item pg-room-player-item--spectator';
          li.innerHTML = `
            <span>${escapeHtml(spectator.name)} · 观战</span>
            <span class="pg-room-player-item__actions">${kickBtn}</span>
          `;
          els.playerList.appendChild(li);
        });

        const count = data.player_count || 0;
        if (state.isHost) {
          els.lobbyHint.textContent = count < minPlayers
            ? `已加入 ${count} 人，至少 ${minPlayers} 人后可开始`
            : `已加入 ${count} 人，可以开始游戏`;
        } else {
          els.lobbyHint.textContent = `已加入 ${count} 人，请等待房主开始游戏`;
        }

        const lobbyWait = document.getElementById('room-lobby-wait');
        if (lobbyWait) lobbyWait.hidden = true;

        if (els.startBtn) {
          els.startBtn.disabled = count < minPlayers;
        }
        setLobbyRoleUI(state.isHost);
        setRoomActionBar(state.isHost, false);
      }

      function renderWatch(data) {
        if (els.watchTitle) els.watchTitle.textContent = '观战中';
        if (els.watchText) {
          els.watchText.textContent = `房间 ${data.room_id || state.roomId} · 阶段：${data.phase || 'lobby'}`;
        }
        if (els.watchList) {
          els.watchList.innerHTML = '';
          (data.players || []).forEach((player) => {
            const li = document.createElement('li');
            li.className = 'pg-room-player-item';
            li.textContent = player.name + (player.is_host ? ' · 房主' : '');
            els.watchList.appendChild(li);
          });
        }
      }

      function showRemoved(message) {
        stopPoll();
        const savedRoomId = state.roomId;
        clearSession();
        hideModePanel();
        hideRoomActionBar();
        if (els.removedText) els.removedText.textContent = message;
        if (els.joinCode && savedRoomId) els.joinCode.value = savedRoomId;
        showPanel('removed');
      }

      async function apiGet(params) {
        const res = await fetch(`api.php?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
          const err = new Error(data.error || 'request failed');
          err.code = data.error || '';
          throw err;
        }
        return data;
      }

      function baseParams() {
        return new URLSearchParams({ room: state.roomId, token: state.token });
      }

      async function refreshState() {
        try {
          const params = baseParams();
          params.set('action', 'room_state');
          const data = await apiGet(params);
          applyIncomingState(data);
          return data;
        } catch (err) {
          if (err.code === 'kicked') {
            showRemoved('你已被房主移出房间。');
            return null;
          }
          if (err.code === 'room not found') {
            showRemoved('房间已不存在或已被解散。');
            return null;
          }
          throw err;
        }
      }

      function applyIncomingState(data) {
        if (!data) return;
        state.isHost = !!(data.me && data.me.is_host);
        state.isSpectator = !!(data.me && data.me.is_spectator);
        state.name = (data.me && data.me.name) || state.name;
        setRoomActionBar(state.isHost, state.isSpectator);

        if (data.me && data.me.is_spectator) {
          renderWatch(data);
          showPanel('spectate');
          if (typeof onState === 'function') onState(data, { mode: 'spectate' });
          return;
        }

        if ((data.phase || '') === 'lobby') {
          renderLobby(data);
          showPanel('lobby');
          if (typeof onState === 'function') onState(data, { mode: 'lobby' });
          return;
        }

        hideModePanel();
        if (typeof onState === 'function') onState(data, { mode: 'playing' });
      }

      function stopPoll() {
        if (state.pollTimer) {
          clearInterval(state.pollTimer);
          state.pollTimer = null;
        }
      }

      function startPoll() {
        stopPoll();
        state.pollTimer = setInterval(() => {
          refreshState().catch(() => { /* ignore */ });
        }, POLL_MS);
      }

      async function createRoom() {
        const name = (els.createName && els.createName.value.trim()) || '';
        if (!name) {
          window.alert('请输入昵称。');
          return;
        }
        const extra = typeof config.getCreateExtra === 'function' ? config.getCreateExtra() : {};
        const params = new URLSearchParams({ action: 'room_create', name });
        Object.entries(extra).forEach(([key, value]) => {
          if (value !== undefined && value !== null) params.set(key, String(value));
        });
        const res = await fetch(`api.php?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'create failed');
        state.roomId = data.room_id;
        state.token = data.token;
        state.isHost = true;
        state.isSpectator = false;
        state.name = name;
        saveSession();
        startPoll();
        if (data.state) applyIncomingState(data.state);
        showPanel('lobby');
      }

      async function joinRoom() {
        const roomId = ((els.joinCode && els.joinCode.value) || '').replace(/\D/g, '');
        const name = (els.joinName && els.joinName.value.trim()) || '';
        const asSpectator = !!(els.joinSpectate && els.joinSpectate.checked);
        if (roomId.length !== 6) {
          window.alert('请输入 6 位房间号。');
          return;
        }
        if (!name) {
          window.alert('请输入昵称。');
          return;
        }
        const params = new URLSearchParams({ action: 'room_join', room: roomId, name });
        if (asSpectator) params.set('spectate', '1');
        const data = await apiGet(params);
        state.roomId = data.room_id || roomId;
        state.token = data.token;
        state.isHost = !!data.is_host;
        state.isSpectator = !!data.is_spectator;
        state.name = name;
        saveSession();
        startPoll();
        if (data.state) applyIncomingState(data.state);
      }

      async function kickMember(targetId, type) {
        const params = baseParams();
        params.set('action', 'room_kick');
        params.set('target', targetId);
        params.set('type', type || 'player');
        const data = await apiGet(params);
        if (data.state) applyIncomingState(data.state);
      }

      async function gameAction(action, extra = {}) {
        const params = baseParams();
        params.set('action', action);
        Object.entries(extra).forEach(([key, value]) => {
          if (value !== undefined && value !== null) params.set(key, String(value));
        });
        const data = await apiGet(params);
        if (data.state) applyIncomingState(data.state);
        return data;
      }

      function bindEvents() {
        bindRoomTabs();

        if (els.modeRoomBtn) {
          els.modeRoomBtn.addEventListener('click', () => {
            hideModePanel();
            hideLocalPanels();
            showPanel('entry');
          });
        }

        const backModeRoomBtn = document.getElementById('back-mode-room-btn');
        if (backModeRoomBtn) {
          backModeRoomBtn.addEventListener('click', showModePanel);
        }
        if (els.createBtn) {
          els.createBtn.addEventListener('click', () => {
            createRoom().catch((err) => window.alert(err.message));
          });
        }
        if (els.joinBtn) {
          els.joinBtn.addEventListener('click', () => {
            joinRoom().catch((err) => window.alert(err.message));
          });
        }
        if (els.playerList) {
          els.playerList.addEventListener('click', (event) => {
            const btn = event.target.closest('.pg-kick-btn');
            if (!btn) return;
            if (!window.confirm('确定踢出该成员吗？')) return;
            kickMember(btn.dataset.target, btn.dataset.type).catch((err) => window.alert(err.message));
          });
        }
        if (els.copyCodeBtn) {
          els.copyCodeBtn.addEventListener('click', async () => {
            try {
              await navigator.clipboard.writeText(state.roomId);
              window.alert('房间号已复制');
            } catch (_) {
              window.prompt('复制房间号', state.roomId);
            }
          });
        }
        if (els.leaveBtn) {
          els.leaveBtn.addEventListener('click', () => {
            leaveRoom().catch((err) => window.alert(err.message));
          });
        }
        if (els.dissolveBtn) {
          els.dissolveBtn.addEventListener('click', () => {
            dissolveRoom().catch((err) => window.alert(err.message));
          });
        }
        const rejoinBtn = document.getElementById('room-removed-rejoin-btn');
        if (rejoinBtn) rejoinBtn.addEventListener('click', () => showPanel('entry'));
        const homeBtn = document.getElementById('room-removed-home-btn');
        if (homeBtn) {
          homeBtn.addEventListener('click', showModePanel);
        }
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

      return {
        state,
        refreshState,
        gameAction,
        startPoll,
        stopPoll,
        showPanel,
        hideModePanel,
        tryResume,
        renderLobby,
        createRoom,
        joinRoom,
        leaveRoom,
        dissolveRoom,
      };
    },
  };
})();
