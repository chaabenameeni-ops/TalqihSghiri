<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TalqihSghiri — Suivi Vaccinal pour Bébé</title>
  <meta name="description" content="Protégez votre bébé avec amour. Suivez les vaccins de votre enfant facilement.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">

  <style>
  /* ════════════════════════════════════════════════════
     KEYFRAMES
  ════════════════════════════════════════════════════ */
  @keyframes fadeUp {
    from { opacity:0; transform:translateY(32px); }
    to   { opacity:1; transform:translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity:0; }
    to   { opacity:1; }
  }
  @keyframes slideRight {
    from { opacity:0; transform:translateX(-28px); }
    to   { opacity:1; transform:translateX(0); }
  }
  @keyframes scaleIn {
    from { opacity:0; transform:scale(.88); }
    to   { opacity:1; transform:scale(1); }
  }
  @keyframes float {
    0%,100% { transform:translateY(0); }
    50%      { transform:translateY(-12px); }
  }
  @keyframes shimmer {
    0%   { background-position:200% center; }
    100% { background-position:-200% center; }
  }
  @keyframes pulseRing {
    0%   { box-shadow:0 0 0 0 hsla(340,60%,65%,.5); }
    70%  { box-shadow:0 0 0 14px hsla(340,60%,65%,0); }
    100% { box-shadow:0 0 0 0 hsla(340,60%,65%,0); }
  }
  @keyframes borderGlow {
    0%,100% { border-color:hsla(340,60%,65%,.3); }
    50%     { border-color:hsla(340,60%,65%,.9); }
  }
  @keyframes spin {
    from { transform:rotate(0deg); }
    to   { transform:rotate(360deg); }
  }
  @keyframes spinLoader {
    from { transform:rotate(0deg); }
    to   { transform:rotate(360deg); }
  }
  @keyframes progressBar {
    0%   { width:0%; }
    50%  { width:70%; }
    100% { width:100%; }
  }
  @keyframes popIn {
    0%   { opacity:0; transform:scale(0.5); }
    70%  { transform:scale(1.1); }
    100% { opacity:1; transform:scale(1); }
  }
  @keyframes bounce {
    0%,100% { transform:translateY(0); }
    50%     { transform:translateY(-8px); }
  }
  @keyframes shake {
    0%,100% { transform:translateX(0); }
    25%     { transform:translateX(-6px); }
    75%     { transform:translateX(6px); }
  }
  @keyframes slideInCard {
    from { opacity:0; transform:translateX(-20px); }
    to   { opacity:1; transform:translateX(0); }
  }

  /* ════════════════════════════════════════════════════
     HEADER
  ════════════════════════════════════════════════════ */
  .header { animation: fadeIn .7s ease both; }

  /* ════════════════════════════════════════════════════
     HERO
  ════════════════════════════════════════════════════ */
  .hero { position: relative; }
  .hero .container { position: relative; z-index: 1; }

  .hero-emojis    { animation: fadeUp .7s .1s ease both; }
  .hero h1        { animation: fadeUp .75s .25s ease both; }
  .hero-sub       { animation: fadeUp .75s .4s ease both; }
  .hero-action-area { animation: fadeUp .75s .55s ease both; }

  /* Shimmer titre */
  .text-pink {
    background: linear-gradient(90deg,
      hsl(340,60%,65%) 0%,
      hsl(330,80%,75%) 30%,
      hsl(340,60%,65%) 60%,
      hsl(350,70%,60%) 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: shimmer 3.5s linear infinite;
  }

  .emoji { animation: float 3s ease-in-out infinite; }
  .emoji:nth-child(2) { animation-delay: .5s; }
  .emoji:nth-child(3) { animation-delay: 1s; }

  /* Particules */
  .hero-particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
  }
  .particle {
    position: absolute;
    border-radius: 50%;
    background: hsla(340,60%,65%,.12);
    animation: float var(--dur,4s) ease-in-out infinite;
    animation-delay: var(--del,0s);
  }

  /* ════════════════════════════════════════════════════
     BOUTONS
  ════════════════════════════════════════════════════ */
  .btn-primary {
    position: relative;
    overflow: hidden;
    transition: transform .25s, box-shadow .25s !important;
  }
  .btn-primary::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,.18);
    opacity: 0;
    transition: opacity .25s;
    border-radius: inherit;
  }
  .btn-primary:hover::after { opacity: 1; }
  .btn-primary:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 22px hsla(340,60%,65%,.38) !important;
    animation: pulseRing .7s ease;
  }

  /* ════════════════════════════════════════════════════
     FEATURE CARDS
  ════════════════════════════════════════════════════ */
  .feature-card {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity .55s ease, transform .55s ease, box-shadow .3s;
  }
  .feature-card.visible {
    opacity: 1;
    transform: translateY(0);
  }
  .feature-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 44px hsla(340,60%,65%,.22);
  }
  .features-grid .feature-card:nth-child(1) { transition-delay: .05s; }
  .features-grid .feature-card:nth-child(2) { transition-delay: .15s; }
  .features-grid .feature-card:nth-child(3) { transition-delay: .25s; }
  .features-grid .feature-card:nth-child(4) { transition-delay: .35s; }
  .feature-card:hover .feature-icon { animation: spin 1s ease; }

  /* ════════════════════════════════════════════════════
     STATS
  ════════════════════════════════════════════════════ */
  .stat {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity .5s ease, transform .5s ease;
  }
  .stat.visible { opacity:1; transform:translateY(0); }
  .stat:nth-child(1) { transition-delay: .0s; }
  .stat:nth-child(2) { transition-delay: .12s; }
  .stat:nth-child(3) { transition-delay: .24s; }
  .stat:nth-child(4) { transition-delay: .36s; }

  .stat-value {
    display: block;
    font-family: var(--font-heading);
    font-size: 2.4rem;
    font-weight: 700;
    background: linear-gradient(90deg,#fff 0%,hsla(340,60%,95%,1) 50%,#fff 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: shimmer 4s linear infinite;
  }

  /* ════════════════════════════════════════════════════
     CONTACT
  ════════════════════════════════════════════════════ */
  .contact-item {
    opacity: 0;
    transform: translateY(25px) scale(.97);
    transition: opacity .5s ease, transform .5s ease;
  }
  .contact-item.visible { opacity:1; transform:translateY(0) scale(1); }
  .contact-item:nth-child(1) { transition-delay: .05s; }
  .contact-item:nth-child(2) { transition-delay: .18s; }
  .contact-item:nth-child(3) { transition-delay: .31s; }
  .contact-item:hover .icon {
    animation: borderGlow 1.2s ease infinite, pulseRing .8s ease;
    box-shadow: 0 0 0 4px hsla(340,60%,65%,.2), 0 12px 30px rgba(0,0,0,.12);
    transform: scale(1.08);
    transition: transform .3s;
  }

  /* ════════════════════════════════════════════════════
     SECTION TITLES
  ════════════════════════════════════════════════════ */
  .section-title, .features-title {
    position: relative;
    display: inline-block;
  }
  .section-title::after, .features-title::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--pink), hsl(330,80%,75%));
    border-radius: 2px;
    transition: width .7s .3s ease;
  }
  .section-title.visible::after, .features-title.visible::after { width: 60%; }

  /* ════════════════════════════════════════════════════
     CHECKER & RÉSULTATS
  ════════════════════════════════════════════════════ */
  #smart-checker { transition: opacity .35s ease, transform .35s ease; }
  #smart-checker.shown { animation: scaleIn .35s ease both; }

  /* Input focus */
  #baby-age {
    transition: all .3s ease;
    border: 2px solid #fce7f3 !important;
  }
  #baby-age:focus {
    border-color: #db2777 !important;
    box-shadow: 0 0 0 3px rgba(219,39,119,.15) !important;
    transform: scale(1.03);
    outline: none;
  }
  #baby-age.shake { animation: shake .3s ease; }

  /* Spinner loading */
  .vaccine-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 30px;
  }
  .spinner-ring {
    width: 50px; height: 50px;
    border: 4px solid #fce7f3;
    border-top-color: #db2777;
    border-radius: 50%;
    animation: spinLoader .8s linear infinite;
  }
  .spinner-text {
    color: #db2777;
    font-weight: 700;
    font-size: .9rem;
    animation: bounce 1s ease infinite;
  }
  .progress-bar {
    width: 200px; height: 4px;
    background: #fce7f3;
    border-radius: 10px;
    overflow: hidden;
  }
  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #db2777, #f472b6);
    border-radius: 10px;
    animation: progressBar 1.2s ease infinite;
  }

  /* Résultats */
  #vaccine-results { animation: fadeUp .5s ease both; }

  .vaccine-card {
    transition: all .3s ease;
  }
  .vaccine-card:hover {
    transform: translateX(6px) scale(1.01);
    box-shadow: 0 6px 20px rgba(0,0,0,.08) !important;
  }

  .badge-anim { animation: popIn .4s ease both; }

  .vaccine-icon-bounce {
    display: inline-block;
    animation: bounce 2s ease infinite;
  }

  /* Empty state */
  .empty-sad {
    font-size: 3rem;
    display: block;
    animation: bounce 2s ease infinite;
    margin-bottom: 10px;
  }

  /* ════════════════════════════════════════════════════
     SCROLL PROGRESS BAR
  ════════════════════════════════════════════════════ */
  #scroll-bar {
    position: fixed;
    top: 0; left: 0;
    height: 3px;
    width: 0%;
    background: linear-gradient(90deg, var(--pink), hsl(330,80%,75%));
    z-index: 9999;
    transition: width .1s linear;
    border-radius: 0 2px 2px 0;
  }

  /* ════════════════════════════════════════════════════
     FOOTER
  ════════════════════════════════════════════════════ */
  .footer {
    opacity: 0;
    transform: translateY(16px);
    transition: opacity .6s ease, transform .6s ease;
  }
  .footer.visible { opacity:1; transform:translateY(0); }

  /* ════════════════════════════════════════════════════
     SCROLL HINT
  ════════════════════════════════════════════════════ */
  .scroll-hint {
    animation: fadeUp .8s 1.2s ease both;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
    color: var(--text-muted);
    font-size: .78rem;
    margin-top: 1.5rem;
    cursor: pointer;
  }
  .scroll-hint-arrow {
    width: 20px; height: 20px;
    border-right: 2px solid var(--pink);
    border-bottom: 2px solid var(--pink);
    transform: rotate(45deg);
    animation: float .9s ease-in-out infinite;
    opacity: .7;
  }

  /* Logo hover */
  .logo:hover .logo-icon { animation: float .6s ease; }
  </style>
</head>
<body>

  <!-- Barre progression scroll -->
  <div id="scroll-bar"></div>

  <header class="header">
    <div class="container header-inner">
      <a href="index.php" class="logo">
        <div class="logo-icon">💉</div>
        <span class="logo-text">Talqih<span class="text-pink">Sghiri</span></span>
      </a>
      <a href="loginp.php" class="btn btn-primary btn-round">Se Connecter</a>
    </div>
  </header>

  <section class="hero">
    <!-- Particules -->
    <div class="hero-particles">
      <div class="particle" style="width:70px;height:70px;top:15%;left:8%;--dur:5s;--del:0s;"></div>
      <div class="particle" style="width:40px;height:40px;top:25%;right:10%;--dur:4s;--del:.8s;"></div>
      <div class="particle" style="width:90px;height:90px;bottom:20%;left:15%;--dur:6s;--del:.3s;"></div>
      <div class="particle" style="width:55px;height:55px;bottom:30%;right:12%;--dur:4.5s;--del:1.2s;"></div>
      <div class="particle" style="width:30px;height:30px;top:55%;left:50%;--dur:3.5s;--del:.5s;"></div>
    </div>

    <div class="container hero-inner">
      <div class="hero-emojis">
        <span class="emoji float">🍼</span>
        <span class="emoji float" style="animation-delay:.5s">💉</span>
        <span class="emoji float" style="animation-delay:1s">🧸</span>
      </div>
      <h1>Protégez votre bébé<br><span class="text-pink">avec amour</span> 💕</h1>
      <p class="hero-sub">Suivez les vaccins de votre enfant facilement. Ne manquez plus aucune vaccination grâce à TalqihSghiri.</p>

      <div class="hero-action-area" style="margin-top:30px;display:flex;flex-direction:column;align-items:center;gap:20px;">
        <div class="hero-btns" style="display:flex;gap:15px;justify-content:center;">
          <a href="vaccines-public.php" class="btn btn-primary btn-lg">💉 Voir les vaccins</a>
          <button type="button" onclick="toggleChecker()" class="btn btn-primary btn-lg btn-round" style="min-width:200px;cursor:pointer;">🔍 Vérifier par âge</button>
        </div>

        <div id="smart-checker" style="display:none;background:#ffffff;padding:12px 25px;border-radius:50px;box-shadow:0 10px 25px rgba(219,39,119,0.1);border:2px solid #fce7f3;width:fit-content;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-weight:600;color:#db2777;">Âge (mois):</span>
            <input type="number" id="baby-age" min="0" placeholder="Ex: 2"
                   style="width:80px;padding:8px 12px;border-radius:50px;outline:none;">
            <button id="search-btn" onclick="checkVaccines()"
                    class="btn btn-primary"
                    style="padding:8px 20px;border-radius:50px;border:none;cursor:pointer;
                           background:linear-gradient(135deg,#db2777,#f472b6);
                           transition:all .3s ease;position:relative;overflow:hidden;">
              🔍 Chercher
            </button>
          </div>
        </div>

        <div id="result-area" style="width:100%;max-width:500px;text-align:left;"></div>
      </div>

      <div class="scroll-hint" onclick="document.querySelector('.features-section').scrollIntoView({behavior:'smooth'})">
        <span>Découvrir</span>
        <div class="scroll-hint-arrow"></div>
      </div>
    </div>
  </section>

  <section class="features-section">
    <div class="features-container">
      <h2 class="features-title section-title">Pourquoi TalqihSghiri ?</h2>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">🛡️</div>
          <h3>Rappels automatiques</h3>
          <p>Recevez des notifications pour ne jamais manquer un vaccin important.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📅</div>
          <h3>Calendrier personnalisé</h3>
          <p>Un planning adapté à l'âge et aux besoins de votre enfant.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📊</div>
          <h3>Suivi intelligent</h3>
          <p>Visualisez l'évolution et l'historique des vaccinations facilement.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🔒</div>
          <h3>Données sécurisées</h3>
          <p>Vos informations sont protégées avec un haut niveau de sécurité.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="features">
    <div class="container">
      <h2 class="section-title">Tout pour la santé de votre bébé 🌟</h2>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">💉</div>
          <h3>Suivi vaccinal</h3>
          <p>Suivez chaque vaccin de votre bébé avec un calendrier complet</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🔔</div>
          <h3>Rappels intelligents</h3>
          <p>Ne manquez plus aucun vaccin grâce aux alertes automatiques</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📅</div>
          <h3>Rendez-vous</h3>
          <p>Planifiez et gérez les rendez-vous de vaccination</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">💬</div>
          <h3>Chatbot IA</h3>
          <p>Posez vos questions sur la vaccination à tout moment</p>
        </div>
      </div>
    </div>
  </section>

  <section class="stats">
    <div class="container stats-grid">
      <div class="stat"><span class="stat-value">14+</span><span class="stat-label">Vaccins suivis</span></div>
      <div class="stat"><span class="stat-value">100%</span><span class="stat-label">Gratuit</span></div>
      <div class="stat"><span class="stat-value">24/7</span><span class="stat-label">Chatbot disponible</span></div>
      <div class="stat"><span class="stat-value">💕</span><span class="stat-label">Fait avec amour</span></div>
    </div>
  </section>

  <section class="contact-section">
    <div class="contact-container">
      <h2 class="section-title">Contactez<span>-nous</span></h2>
      <p class="section-subtitle">Avez-vous des questions ? Nous sommes là pour vous accompagner dans le parcours de vaccination de vos enfants.</p>
      <div class="contact-items">
        <div class="contact-item">
          <div class="icon email-icon">📧</div>
          <h3>Contact par e-mail</h3>
          <p>support@talqihsghiri.com</p>
          <p>aide@talqihsghiri.com</p>
        </div>
        <div class="contact-item">
          <div class="icon whatsapp-icon">📱</div>
          <h3>Contact par WhatsApp</h3>
          <p>+216 23 196 937</p>
          <p class="small-text">Disponible 8 AM - 6 PM</p>
        </div>
        <div class="contact-item">
          <div class="icon office-icon">🏢</div>
          <h3>Addresse</h3>
          <p>Tunisia</p>
        </div>
      </div>
    </div>
  </section>

  <footer class="footer">
    <div class="container footer-inner">
      <div class="logo">
        <span class="logo-text">💉 Talqih<span class="text-pink">Sghiri</span></span>
      </div>
      <p>© 2026 Talqihsghiri — Protégez votre bébé avec amour 💕</p>
    </div>
  </footer>

  <script>
  /* ════════════════════════════════════════════════
     SCROLL PROGRESS BAR
  ════════════════════════════════════════════════ */
  window.addEventListener('scroll', () => {
    const scrolled = window.scrollY;
    const total    = document.documentElement.scrollHeight - window.innerHeight;
    document.getElementById('scroll-bar').style.width = ((scrolled / total) * 100) + '%';
  });

  /* ════════════════════════════════════════════════
     INTERSECTION OBSERVER
  ════════════════════════════════════════════════ */
  const revealTargets = [
    { selector: '.feature-card',   threshold: 0.15 },
    { selector: '.stat',           threshold: 0.25 },
    { selector: '.contact-item',   threshold: 0.15 },
    { selector: '.section-title',  threshold: 0.35 },
    { selector: '.features-title', threshold: 0.35 },
    { selector: '.footer',         threshold: 0.1  },
  ];

  revealTargets.forEach(({ selector, threshold }) => {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold });
    document.querySelectorAll(selector).forEach(el => obs.observe(el));
  });

  /* ════════════════════════════════════════════════
     CHECKER TOGGLE
  ════════════════════════════════════════════════ */
  function toggleChecker() {
    const checker    = document.getElementById('smart-checker');
    const resultArea = document.getElementById('result-area');
    if (checker.style.display === 'none' || checker.style.display === '') {
      checker.style.display = 'block';
      checker.classList.add('shown');
      setTimeout(() => document.getElementById('baby-age').focus(), 350);
    } else {
      checker.style.opacity = '0';
      checker.style.transform = 'scale(.95)';
      setTimeout(() => {
        checker.style.display = 'none';
        checker.style.opacity = '';
        checker.style.transform = '';
        checker.classList.remove('shown');
        resultArea.innerHTML = '';
        const old = document.getElementById('vaccine-results');
        if (old) old.remove();
      }, 300);
    }
  }

  /* ════════════════════════════════════════════════
     CHECK VACCINES — API CALL
  ════════════════════════════════════════════════ */
  function checkVaccines() {
    const input = document.getElementById('baby-age');
    const age   = input.value.trim();

    if (!age || parseInt(age) < 0) {
      // Animation shake sur l'input
      input.classList.add('shake');
      input.style.borderColor = '#ef4444';
      setTimeout(() => {
        input.classList.remove('shake');
        input.style.borderColor = '';
      }, 400);
      return;
    }

    const btn = document.getElementById('search-btn');
    btn.innerHTML = '⏳';
    btn.disabled  = true;
    btn.style.opacity = '.7';

    // Supprimer anciens résultats
    const oldRes = document.getElementById('vaccine-results');
    if (oldRes) {
      oldRes.style.opacity  = '0';
      oldRes.style.transform = 'translateY(-10px)';
      oldRes.style.transition = 'all .3s ease';
      setTimeout(() => oldRes.remove(), 300);
    }

    // Afficher loader
    showLoading();

    fetch(`api.php?age=${age}`)
      .then(r => r.json())
      .then(data => {
        btn.innerHTML = '🔍 Chercher';
        btn.disabled  = false;
        btn.style.opacity = '1';
        hideLoading();
        setTimeout(() => showVaccineResults(data), 150);
      })
      .catch(err => {
        btn.innerHTML = '🔍 Chercher';
        btn.disabled  = false;
        btn.style.opacity = '1';
        hideLoading();
        console.error(err);
      });
  }

  /* ════════════════════════════════════════════════
     LOADER
  ════════════════════════════════════════════════ */
  function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'vaccine-results';
    loader.style.cssText = `
      margin-top:20px;
      background:#fff;
      border-radius:20px;
      padding:10px 25px;
      box-shadow:0 8px 25px rgba(219,39,119,.1);
      border:1px solid #fce7f3;
      max-width:500px;
      margin-left:auto;
      margin-right:auto;
      animation:fadeUp .4s ease both;
    `;
    loader.innerHTML = `
      <div class="vaccine-spinner">
        <div class="spinner-ring"></div>
        <div class="spinner-text">🔍 Recherche des vaccins...</div>
        <div class="progress-bar"><div class="progress-fill"></div></div>
      </div>
    `;
    const checker = document.getElementById('smart-checker');
    checker.parentNode.insertBefore(loader, checker.nextSibling);
  }

  function hideLoading() {
    const loader = document.getElementById('vaccine-results');
    if (loader) {
      loader.style.opacity   = '0';
      loader.style.transform = 'scale(.96)';
      loader.style.transition = 'all .2s ease';
      setTimeout(() => loader.remove(), 200);
    }
  }

  /* ════════════════════════════════════════════════
     AFFICHER RÉSULTATS
  ════════════════════════════════════════════════ */
  function showVaccineResults(data) {
    const div = document.createElement('div');
    div.id = 'vaccine-results';
    div.style.cssText = `
      margin-top:20px;
      background:#fff;
      border-radius:20px;
      padding:22px 28px;
      box-shadow:0 12px 35px rgba(219,39,119,.12);
      border:1px solid #fce7f3;
      max-width:500px;
      margin-left:auto;
      margin-right:auto;
      text-align:left;
      animation:fadeUp .5s ease both;
    `;

    if (!data.vaccins || data.vaccins.length === 0) {
      div.innerHTML = `
        <div style="text-align:center;padding:20px;">
          <span class="empty-sad">😕</span>
          <div style="color:#db2777;font-weight:700;font-size:1rem;">
            Aucun vaccin trouvé pour ${data.age_mois} mois
          </div>
          <div style="color:#6b7280;font-size:.83rem;margin-top:6px;">
            Essayez : 0, 2, 3, 4, 6, 11, 12 ou 18 mois
          </div>
        </div>`;
    } else {
      let html = `
        <div style="margin-bottom:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span class="vaccine-icon-bounce">💉</span>
          <span style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:800;color:#1a1a2e;">
            Vaccins pour <strong style="color:#db2777;">${data.age_label}</strong>
          </span>
          <span class="badge-anim" style="
            background:linear-gradient(135deg,#db2777,#f472b6);
            color:#fff;padding:3px 12px;border-radius:20px;
            font-size:.75rem;font-weight:800;">
            ${data.count} vaccin(s)
          </span>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
      `;

      data.vaccins.forEach((v, i) => {
        const isOblig = v.type_vacs === 'Obligatoire';
        html += `
          <div class="vaccine-card" style="
            background:${isOblig ? '#f0fdf4' : '#fffbf5'};
            border:1px solid ${isOblig ? '#86efac' : '#fde68a'};
            border-left:4px solid ${isOblig ? '#16a34a' : '#ea580c'};
            border-radius:12px;
            padding:12px 16px;
            animation:slideInCard .4s ease ${i * 0.08}s both;
          ">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px;">
              <span style="font-weight:800;font-size:.92rem;color:#1a1a2e;">
                💉 ${v.nom_complet}
              </span>
              <span class="badge-anim" style="
                background:${isOblig ? '#d1fae5' : '#fff3cd'};
                color:${isOblig ? '#065f46' : '#92400e'};
                padding:2px 10px;border-radius:20px;
                font-size:.7rem;font-weight:800;">
                ${isOblig ? '⭐ Obligatoire' : '💡 Recommandé'}
              </span>
            </div>
            <div style="font-size:.82rem;color:#374151;margin-bottom:4px;">
              🦠 <strong>Protège contre :</strong> ${v.maladie || '—'}
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:.77rem;color:#6b7280;">
              <span>💊 ${v.voie_administration || '—'}</span>
              <span>📍 ${v.site_injection || '—'}</span>
            </div>
          </div>
        `;
      });

      html += `
        </div>
        <div style="margin-top:14px;text-align:center;font-size:.75rem;color:#9ca3af;
                    border-top:1px solid #fce7f3;padding-top:10px;">
          📋 Source : Calendrier vaccinal Tunisien — Ministère de la Santé 2025
        </div>
      `;
      div.innerHTML = html;
    }

    const checker = document.getElementById('smart-checker');
    checker.parentNode.insertBefore(div, checker.nextSibling);
    setTimeout(() => div.scrollIntoView({ behavior:'smooth', block:'nearest' }), 100);
  }

  /* ════════════════════════════════════════════════
     TOUCHE ENTRÉE
  ════════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('baby-age');
    if (input) {
      input.addEventListener('keypress', e => {
        if (e.key === 'Enter') checkVaccines();
      });
    }
  });
  </script>

</body>
</html>