(() => {
  'use strict';

  const room = PartyRoom.create({
    storageKey: 'partygame_nb_room_v1',
    minPlayers: 2,
    getCreateExtra() {
      const active = document.querySelector('.nb-range-btn.is-active');
      return {
        min: active ? active.dataset.min : '1',
        max: active ? active.dataset.max : '100',
      };
    },
    onState(data) {
      if ((data.phase || '') === 'lobby') return;
      if ((data.phase || '') === 'ended') {
        document.getElementById('result-title').textContent = '游戏结束';
        document.getElementById('result-text').textContent = data.bomb != null
          ? `炸弹数字是 ${data.bomb}。`
          : '有人踩雷了！';
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
        applyGuessRole(data);
        showGamePanel('play');
      }
    },
  });

  function escapeHtml(text) {
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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

  function showGamePanel(name) {
    ['play', 'result'].forEach((p) => {
      const node = document.getElementById(`panel-${p}`);
      if (node) node.classList.toggle('is-active', p === name);
    });
  }

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

  room.tryResume();
})();
