/**
 * Block leaving the site / switching games while a room session is active.
 */
(() => {
  'use strict';

  const ROOM_REGISTRY = [
    { key: 'partygame_tod_room_v1', slug: 'truth-or-dare', label: '真心话大冒险' },
    { key: 'partygame_nhie_room_v1', slug: 'never-have-i-ever', label: '从来没有' },
    { key: 'partygame_wyr_room_v1', slug: 'would-you-rather', label: '你宁愿' },
    { key: 'partygame_nb_room_v1', slug: 'number-bomb', label: '数字炸弹' },
    { key: 'partygame_uc_room_v1', slug: 'undercover', label: '谁是卧底' },
  ];

  function getActiveSession() {
    for (const entry of ROOM_REGISTRY) {
      try {
        const raw = localStorage.getItem(entry.key);
        if (!raw) continue;
        const data = JSON.parse(raw);
        if (data && data.roomId) {
          return {
            key: entry.key,
            slug: entry.slug,
            label: entry.label,
            roomId: String(data.roomId),
          };
        }
      } catch (_) {
        /* ignore */
      }
    }
    return null;
  }

  function currentGameSlug() {
    const match = window.location.pathname.match(/^\/games\/([^/]+)/);
    return match ? match[1] : '';
  }

  function sessionMessage(session) {
    return `你仍在「${session.label}」房间（${session.roomId}）中。\n请先解散或退出房间后再离开或切换游戏。`;
  }

  function isInternalNavLink(link) {
    if (!link || link.target === '_blank' || link.hasAttribute('download')) return false;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
      return false;
    }
    try {
      const url = new URL(href, window.location.href);
      return url.origin === window.location.origin;
    } catch (_) {
      return false;
    }
  }

  function shouldBlockNavigation(url) {
    const session = getActiveSession();
    if (!session) return false;

    const targetPath = url.pathname.replace(/\/+$/, '') || '/';
    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
    if (targetPath === currentPath) return false;

    const targetSlug = targetPath.match(/^\/games\/([^/]+)/);
    if (targetSlug && targetSlug[1] === session.slug) return false;

    return true;
  }

  function installNavigationGuard() {
    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[href]');
      if (!isInternalNavLink(link)) return;

      const url = new URL(link.getAttribute('href'), window.location.href);
      if (!shouldBlockNavigation(url)) return;

      event.preventDefault();
      event.stopPropagation();
      const session = getActiveSession();
      if (session) window.alert(sessionMessage(session));
    }, true);
  }

  function renderWrongGameBlock(session) {
    const slug = currentGameSlug();
    if (!slug || slug === session.slug) return;

    const overlay = document.createElement('div');
    overlay.className = 'pg-room-guard';
    overlay.setAttribute('role', 'alertdialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = `
      <div class="pg-room-guard__card card">
        <h2 class="pg-room-guard__title">请先退出当前房间</h2>
        <p class="pg-room-guard__text">
          你仍在「${session.label}」房间（${session.roomId}）中，无法直接进入其他游戏。
          请返回原游戏，点击「退出房间」或「解散房间」后再切换。
        </p>
        <div class="pg-room-guard__actions">
          <a class="btn" href="/games/${session.slug}/">返回「${session.label}」</a>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
  }

  function init() {
    const session = getActiveSession();
    if (session) {
      renderWrongGameBlock(session);
    }
    installNavigationGuard();
  }

  window.PartyRoomGuard = { getActiveSession, ROOM_REGISTRY };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
