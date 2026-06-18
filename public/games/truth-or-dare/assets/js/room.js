(() => {
  'use strict';

  const room = PartyRoom.create({
    storageKey: 'partygame_tod_room_v1',
    minPlayers: 2,
    getCreateExtra() {
      const levelBtn = document.querySelector('.game-level-btn.is-active');
      return { level: levelBtn ? levelBtn.dataset.level : 'normal' };
    },
    onState(data) {
      if ((data.phase || '') === 'lobby') return;

      const isHost = !!(data.me && data.me.is_host);
      const phase = data.phase || 'spin';

      if (phase === 'spin') {
        document.getElementById('current-player').textContent = data.current_player || '准备开始';
        document.getElementById('current-hint').textContent = isHost
          ? '点击按钮转动瓶子，随机选出本轮玩家'
          : '等待房主转动瓶子…';
        PartyRoom.setHostActions(['spin-btn'], isHost);
        PartyRoom.setWaitBanner(document.getElementById('panel-spin'), !isHost, '等待房主转动瓶子…');
        showGamePanel('spin');
        return;
      }

      if (phase === 'choose') {
        document.getElementById('choose-player-label').textContent = `当前玩家：${data.current_player || '—'}`;
        const choosePanel = document.getElementById('panel-choose');
        const grid = choosePanel ? choosePanel.querySelector('.game-choice-grid') : null;
        if (grid) grid.hidden = !isHost;
        PartyRoom.setWaitBanner(choosePanel, !isHost, '等待房主选择真心话或大冒险…');
        showGamePanel('choose');
        return;
      }

      if (phase === 'reveal') {
        const type = data.current_type || 'truth';
        document.getElementById('reveal-badge').textContent = type === 'truth' ? '真心话' : '大冒险';
        document.getElementById('reveal-badge').className = `game-reveal__badge game-reveal__badge--${type}`;
        document.getElementById('reveal-text').textContent = data.current_text || '—';
        document.getElementById('reveal-player').textContent = `${data.current_player || '玩家'}，请诚实回答或完成挑战`;
        PartyRoom.setHostActions(['next-btn'], isHost);
        PartyRoom.setWaitBanner(document.getElementById('panel-reveal'), !isHost, '等待房主进入下一轮…');
        showGamePanel('reveal');
      }
    },
  });

  function showGamePanel(name) {
    ['spin', 'choose', 'reveal'].forEach((p) => {
      const node = document.getElementById(`panel-${p}`);
      if (node) node.classList.toggle('is-active', p === name);
    });
    ['panel-room-entry', 'panel-room-lobby', 'panel-room-spectate', 'panel-room-removed', 'panel-mode', 'panel-setup']
      .forEach((id) => {
        const node = document.getElementById(id);
        if (node) node.classList.remove('is-active');
      });
  }

  document.getElementById('room-start-btn').addEventListener('click', () => {
    room.gameAction('room_start').catch((err) => window.alert(err.message));
  });

  document.getElementById('spin-btn').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_spin').catch((err) => window.alert(err.message));
  }, true);

  document.getElementById('truth-card').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_choose', { type: 'truth' }).catch((err) => window.alert(err.message));
  }, true);

  document.getElementById('dare-card').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_choose', { type: 'dare' }).catch((err) => window.alert(err.message));
  }, true);

  document.getElementById('next-btn').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_next').catch((err) => window.alert(err.message));
  }, true);

  room.tryResume();
})();
