(function () {
  'use strict';

  var canvas = document.getElementById('bgCanvas');
  if (!canvas) return;

  var ctx = canvas.getContext('2d');
  if (!ctx) return;

  var particles = [];
  var mouse = { x: 0.5, y: 0.5 };
  var count = window.innerWidth < 768 ? 48 : 80;
  var colors = ['rgba(255, 180, 199, 0.55)', 'rgba(145, 213, 255, 0.5)', 'rgba(177, 151, 252, 0.45)'];

  function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }

  function init() {
    particles = [];
    for (var i = 0; i < count; i++) {
      particles.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        vx: (Math.random() - 0.5) * 0.35,
        vy: (Math.random() - 0.5) * 0.35,
        r: 1.2 + Math.random() * 2,
        c: colors[i % colors.length],
      });
    }
  }

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    var mx = (mouse.x - 0.5) * 18;
    var my = (mouse.y - 0.5) * 18;

    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      p.x += p.vx + mx * 0.002;
      p.y += p.vy + my * 0.002;
      if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
      if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

      ctx.beginPath();
      ctx.fillStyle = p.c;
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }

    for (var a = 0; a < particles.length; a++) {
      for (var b = a + 1; b < particles.length; b++) {
        var dx = particles[a].x - particles[b].x;
        var dy = particles[a].y - particles[b].y;
        var dist = dx * dx + dy * dy;
        if (dist < 9000) {
          ctx.strokeStyle = 'rgba(255, 201, 217, 0.12)';
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(particles[a].x, particles[a].y);
          ctx.lineTo(particles[b].x, particles[b].y);
          ctx.stroke();
        }
      }
    }

    requestAnimationFrame(draw);
  }

  window.addEventListener('resize', function () {
    resize();
    init();
  });

  document.addEventListener('mousemove', function (e) {
    mouse.x = e.clientX / window.innerWidth;
    mouse.y = e.clientY / window.innerHeight;
  });

  resize();
  init();
  draw();
})();
