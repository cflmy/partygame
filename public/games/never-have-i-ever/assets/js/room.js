(() => {
  'use strict';

  const room = PartyRoom.create({
    storageKey: 'partygame_nhie_room_v1',
    minPlayers: 2,
    getCreateExtra() {
      const active = document.querySelector('#room-create-level-group .game-level-btn.is-active');
      return { level: active ? active.dataset.level : 'normal' };
    },
    onState(data, ctx) {
      if ((data.phase || '') === 'lobby' || (ctx && ctx.mode === 'lobby')) {
        hideGamePanels();
        syncLobbySettings(data);
        return;
      }
      if ((data.phase || '') === 'play') {
        document.getElementById('statement-text').textContent = data.statement || '—';
        document.getElementById('round-hint').textContent = `第 ${data.round || 1} 轮 · 做过的人请放下手指`;
        const isHost = !!(data.me && data.me.is_host);
        PartyRoom.setHostActions(['next-btn', 'swap-btn', 'back-setup-btn'], isHost);
        PartyRoom.setWaitBanner(document.getElementById('panel-play'), !isHost, '等待房主切换下一条…');
        showGamePanel('play');
      }
    },
  });

  function syncLobbySettings(data) {
    const group = document.getElementById('room-lobby-level-group');
    if (!group) return;
    const level = data.level || 'normal';
    const isHost = !!(data.me && data.me.is_host);
    group.querySelectorAll('.game-level-btn').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.level === level);
      btn.disabled = !isHost;
    });
    const hint = document.getElementById('room-lobby-level-hint');
    if (hint) {
      hint.textContent = isHost
        ? '可随时调整强度，确认后点击「开始游戏」'
        : '房主设置的题目强度';
    }
  }

  function hideGamePanels() {
    ['setup', 'play'].forEach((p) => {
      const node = document.getElementById(`panel-${p}`);
      if (node) node.classList.remove('is-active');
    });
  }

  function showGamePanel(name) {
    ['setup', 'play'].forEach((p) => {
      const node = document.getElementById(`panel-${p}`);
      if (node) node.classList.toggle('is-active', p === name);
    });
    ['panel-room-entry', 'panel-room-lobby', 'panel-room-spectate', 'panel-room-removed', 'panel-mode']
      .forEach((id) => {
        const node = document.getElementById(id);
        if (node) node.classList.remove('is-active');
      });
  }

  function bindLevelPicker(groupId, onSelect) {
    const group = document.getElementById(groupId);
    if (!group) return;
    group.addEventListener('click', (event) => {
      const btn = event.target.closest('.game-level-btn');
      if (!btn || btn.disabled) return;
      if (onSelect) {
        onSelect(btn);
        return;
      }
      group.querySelectorAll('.game-level-btn').forEach((node) => {
        node.classList.toggle('is-active', node === btn);
      });
    });
  }

  function bindRoomGameActions() {
    document.getElementById('room-start-btn').addEventListener('click', () => {
      room.gameAction('room_start').catch((e) => window.alert(e.message));
    });

    document.getElementById('next-btn').addEventListener('click', (event) => {
      if (!room.state.roomId) return;
      event.stopImmediatePropagation();
      room.gameAction('room_next').catch((e) => window.alert(e.message));
    }, true);

    document.getElementById('swap-btn').addEventListener('click', (event) => {
      if (!room.state.roomId) return;
      event.stopImmediatePropagation();
      room.gameAction('room_swap').catch((e) => window.alert(e.message));
    }, true);

    const backBtn = document.getElementById('back-setup-btn');
    if (backBtn) {
      backBtn.addEventListener('click', (event) => {
        if (!room.state.roomId) return;
        event.stopImmediatePropagation();
        room.gameAction('room_back').catch((e) => window.alert(e.message));
      }, true);
    }
  }

  bindLevelPicker('room-create-level-group');
  bindLevelPicker('room-lobby-level-group', (btn) => {
    if (!room.state.roomId || !room.state.isHost) return;
    room.gameAction('room_set_level', { level: btn.dataset.level })
      .catch((e) => window.alert(e.message));
  });
  bindRoomGameActions();
  room.tryResume();
})();
