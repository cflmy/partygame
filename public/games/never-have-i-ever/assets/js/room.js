(() => {
  'use strict';

  const room = PartyRoom.create({
    storageKey: 'partygame_nhie_room_v1',
    minPlayers: 2,
    getCreateExtra() {
      const levelBtn = document.querySelector('.game-level-btn.is-active');
      return { level: levelBtn ? levelBtn.dataset.level : 'normal' };
    },
    onState(data, ctx) {
      if ((data.phase || '') === 'lobby' || (ctx && ctx.mode === 'lobby')) {
        hideGamePanels();
        return;
      }
      if ((data.phase || '') === 'play') {
        document.getElementById('statement-text').textContent = data.statement || '—';
        document.getElementById('round-hint').textContent = `第 ${data.round || 1} 轮 · 做过的人请放下手指`;
        const isHost = !!(data.me && data.me.is_host);
        PartyRoom.setHostActions(['next-btn', 'swap-btn'], isHost);
        PartyRoom.setWaitBanner(document.getElementById('panel-play'), !isHost, '等待房主切换下一条…');
        showGamePanel('play');
      }
    },
  });

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

  document.getElementById('room-start-btn').addEventListener('click', () => {
    room.gameAction('room_start').catch((e) => window.alert(e.message));
  });

  document.getElementById('next-btn').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_next').catch((e) => window.alert(e.message));
  }, true);

  room.tryResume();
})();
