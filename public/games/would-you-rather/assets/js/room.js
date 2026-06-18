(() => {
  'use strict';

  const room = PartyRoom.create({
    storageKey: 'partygame_wyr_room_v1',
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
        document.getElementById('option-a').textContent = data.option_a || '';
        document.getElementById('option-b').textContent = data.option_b || '';
        const total = (data.votes_a || 0) + (data.votes_b || 0);
        const pctA = total === 0 ? 50 : Math.round((data.votes_a / total) * 100);
        document.getElementById('vote-bar-a').style.width = `${pctA}%`;
        document.getElementById('vote-bar-b').style.width = `${100 - pctA}%`;
        document.getElementById('vote-count-a').textContent = String(data.votes_a || 0);
        document.getElementById('vote-count-b').textContent = String(data.votes_b || 0);
        const isHost = !!(data.me && data.me.is_host);
        PartyRoom.setHostActions(['next-btn', 'swap-btn', 'reset-votes-btn'], isHost);
        PartyRoom.setWaitBanner(document.getElementById('panel-play'), !isHost, '等待房主进入下一题…');
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

  document.getElementById('choice-a').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_vote', { choice: 'a' }).catch((e) => window.alert(e.message));
  }, true);

  document.getElementById('choice-b').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_vote', { choice: 'b' }).catch((e) => window.alert(e.message));
  }, true);

  document.getElementById('next-btn').addEventListener('click', (event) => {
    if (!room.state.roomId) return;
    event.stopImmediatePropagation();
    room.gameAction('room_next').catch((e) => window.alert(e.message));
  }, true);

  room.tryResume();
})();
