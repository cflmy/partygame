(() => {
  'use strict';

  const room = PartyRoom.create({
    storageKey: 'partygame_nb_room_v1',
    minPlayers: 2,
    getCreateExtra() {
      const active = document.querySelector('#room-create-range-group .nb-range-btn.is-active');
      return {
        min: active ? active.dataset.min : '1',
        max: active ? active.dataset.max : '100',
      };
    },
    onState(data, ctx) {
      if ((data.phase || '') === 'lobby' || (ctx && ctx.mode === 'lobby')) {
        hideGamePanels();
        syncLobbySettings(data);
        return;
      }
      if ((data.phase || '') === 'ended') {
        document.getElementById('result-title').textContent = '游戏结束';
        document.getElementById('result-text').textContent = data.bomb != null
          ? `炸弹数字是 ${data.bomb}。`
          : '有人踩雷了！';
        const isHost = !!(data.me && data.me.is_host);
        PartyRoom.setHostActions(['back-setup-result-btn'], isHost);
        showGamePanel('result');
        return;
      }
      if ((data.phase || '') === 'play') {
        document.getElementById('range-low').textContent = String(data.low);
        document.getElementById('range-high').textContent = String(data.high);
        document.getElementById('current-player').innerHTML = `轮到 <strong>${escapeHtml(data.current_player || '玩家')}</strong> 猜数字`;
        const history = document.getElementById('history');
        if (history) {
          history.innerHTML = (data.history || [])
            .map((line) => `<div class="nb-history__item">${escapeHtml(line)}</div>`)
            .join('');
        }
        const isHost = !!(data.me && data.me.is_host);
        PartyRoom.setHostActions(['back-setup-btn'], isHost);
        applyGuessRole(data);
        showGamePanel('play');
      }
    },
  });

  function escapeHtml(text) {
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function syncLobbySettings(data) {
    const group = document.getElementById('room-lobby-range-group');
    if (!group) return;
    const min = String(data.min ?? 1);
    const max = String(data.max ?? 100);
    const isHost = !!(data.me && data.me.is_host);
    group.querySelectorAll('.nb-range-btn').forEach((btn) => {
      const active = btn.dataset.min === min && btn.dataset.max === max;
      btn.classList.toggle('is-active', active);
      btn.disabled = !isHost;
    });
    const hint = document.getElementById('room-lobby-range-hint');
    if (hint) {
      hint.textContent = isHost
        ? '可随时调整范围，确认后点击「开始游戏」'
        : `房主设置的范围：${min} ~ ${max}`;
    }
  }

  function applyGuessRole(data) {
    const me = data.me || {};
    const current = data.current_player || '';
    const isMyTurn = (me.name || '') === current;
    const guessRow = document.querySelector('#panel-play .nb-guess-row');
    if (guessRow) guessRow.hidden = !isMyTurn;
    PartyRoom.setWaitBanner(
      document.getElementById('panel-play'),
      !isMyTurn,
      `等待 ${current || '其他玩家'} 猜数字…`
    );
  }

  function hideGamePanels() {
    ['setup', 'play', 'result'].forEach((p) => {
      const node = document.getElementById(`panel-${p}`);
      if (node) node.classList.remove('is-active');
    });
  }

  function showGamePanel(name) {
    ['setup', 'play', 'result'].forEach((p) => {
      const node = document.getElementById(`panel-${p}`);
      if (node) node.classList.toggle('is-active', p === name);
    });
    ['panel-room-entry', 'panel-room-lobby', 'panel-room-spectate', 'panel-room-removed', 'panel-mode']
      .forEach((id) => {
        const node = document.getElementById(id);
        if (node) node.classList.remove('is-active');
      });
  }

  function bindRangePicker(groupId, onSelect) {
    const group = document.getElementById(groupId);
    if (!group) return;
    group.addEventListener('click', (event) => {
      const btn = event.target.closest('.nb-range-btn');
      if (!btn || btn.disabled) return;
      if (onSelect) {
        onSelect(btn);
        return;
      }
      group.querySelectorAll('.nb-range-btn').forEach((node) => {
        node.classList.toggle('is-active', node === btn);
      });
    });
  }

  function bindBackSetup(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('click', (event) => {
      if (!room.state.roomId) return;
      event.stopImmediatePropagation();
      room.gameAction('room_back').catch((e) => window.alert(e.message));
    }, true);
  }

  function bindRoomGameActions() {
    document.getElementById('room-start-btn').addEventListener('click', () => {
      room.gameAction('room_start').catch((e) => window.alert(e.message));
    });

    const origGuess = document.getElementById('guess-btn');
    if (origGuess) {
      origGuess.addEventListener('click', async (event) => {
        if (!room.state.roomId) return;
        event.stopImmediatePropagation();
        const guess = Number(document.getElementById('guess-input').value);
        await room.gameAction('room_guess', { guess });
      }, true);
    }

    bindBackSetup('back-setup-btn');
    bindBackSetup('back-setup-result-btn');
  }

  bindRangePicker('room-create-range-group');
  bindRangePicker('room-lobby-range-group', (btn) => {
    if (!room.state.roomId || !room.state.isHost) return;
    room.gameAction('room_set_range', { min: btn.dataset.min, max: btn.dataset.max })
      .catch((e) => window.alert(e.message));
  });
  bindRoomGameActions();
  room.tryResume();
})();
