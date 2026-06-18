(() => {
  'use strict';

  const els = {
    choiceA: document.getElementById('choice-a'),
    choiceB: document.getElementById('choice-b'),
    optionA: document.getElementById('option-a'),
    optionB: document.getElementById('option-b'),
    voteBarA: document.getElementById('vote-bar-a'),
    voteBarB: document.getElementById('vote-bar-b'),
    voteCountA: document.getElementById('vote-count-a'),
    voteCountB: document.getElementById('vote-count-b'),
    voteProgress: document.getElementById('vote-progress'),
    roundHint: document.getElementById('round-hint'),
    playPanel: document.getElementById('panel-play'),
  };

  const room = PartyRoom.create({
    storageKey: 'partygame_wyr_room_v1',
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
        syncPlayUI(data);
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

  function syncPlayUI(data) {
    const isHost = !!(data.me && data.me.is_host);
    const myVote = data.my_vote || null;
    const votesCast = data.votes_cast ?? ((data.votes_a || 0) + (data.votes_b || 0));
    const voteTotal = data.vote_total ?? data.player_count ?? 0;
    const allVoted = !!data.all_voted;

    els.optionA.textContent = data.option_a || '';
    els.optionB.textContent = data.option_b || '';

    const total = (data.votes_a || 0) + (data.votes_b || 0);
    const pctA = total === 0 ? 50 : Math.round((data.votes_a / total) * 100);
    els.voteBarA.style.width = `${pctA}%`;
    els.voteBarB.style.width = `${100 - pctA}%`;
    els.voteCountA.textContent = String(data.votes_a || 0);
    els.voteCountB.textContent = String(data.votes_b || 0);

    els.choiceA.classList.toggle('is-selected', myVote === 'a');
    els.choiceB.classList.toggle('is-selected', myVote === 'b');

    if (els.voteProgress) {
      els.voteProgress.hidden = false;
      els.voteProgress.textContent = allVoted
        ? `全员 ${voteTotal} 人已投票`
        : `已有 ${votesCast} / ${voteTotal} 人投票`;
    }

    if (els.roundHint) {
      if (isHost) {
        els.roundHint.textContent = allVoted
          ? '全员已投票，可讨论结果后点击「下一题」'
          : `等待玩家投票（${votesCast}/${voteTotal}），你也可点击 A/B 参与投票`;
      } else if (myVote) {
        els.roundHint.textContent = '你已投票，可点击另一选项更改；等待其他人投票';
      } else {
        els.roundHint.textContent = '请点击 A 或 B 投出你的选择';
      }
    }

    PartyRoom.setHostActions(['next-btn', 'swap-btn', 'reset-votes-btn', 'back-setup-btn'], isHost);
    PartyRoom.setWaitBanner(els.playPanel, false);
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

  function bindVoteChoice(id, choice) {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('click', (event) => {
      if (!room.state.roomId || room.state.isSpectator) return;
      event.stopImmediatePropagation();
      room.gameAction('room_vote', { choice }).catch((e) => window.alert(e.message));
    }, true);
  }

  function bindRoomGameActions() {
    document.getElementById('room-start-btn').addEventListener('click', () => {
      room.gameAction('room_start').catch((e) => window.alert(e.message));
    });

    bindVoteChoice('choice-a', 'a');
    bindVoteChoice('choice-b', 'b');

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

    document.getElementById('reset-votes-btn').addEventListener('click', (event) => {
      if (!room.state.roomId) return;
      event.stopImmediatePropagation();
      room.gameAction('room_reset_votes').catch((e) => window.alert(e.message));
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
