<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>タビガイド - 宿泊施設専用AIコンシェルジュ作成ツール</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/landing.css">
  <style>
    :root {
      --primary: #6B21FF;
      --primary-dark: #5B21E6;
      --text: #333333;
      --text-light: #666666;
      --background: #FFFFFF;
      --background-alt: #F5F5F5;
      --border: #EEEEEE;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Noto Sans JP', sans-serif;
      color: var(--text);
      line-height: 1.6;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
    }

    /* ヘッダー */
    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: white;
      padding: 16px 0;
      z-index: 100;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .header-inner {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo-container {
      display: flex;
      align-items: center;
    }

    .logo {
      height: 32px;
      width: auto;
    }

    .header-nav {
      display: flex;
      align-items: center;
      gap: 2rem;
    }

    .header-nav a {
      color: var(--text);
      text-decoration: none;
      font-size: 0.875rem;
      opacity: 0.9;
      transition: opacity 0.2s;
    }

    .header-nav a:hover {
      opacity: 1;
    }

    .header-cta {
      padding: 0.5rem 1.25rem;
      background: var(--primary);
      color: white !important;
      border-radius: 6px;
      font-weight: 500;
    }

    /* ヒーローセクション */
    .hero {
      padding: 160px 0 80px;
      background: var(--primary);
      text-align: center;
      color: white;
      min-height: 100vh;
      display: flex;
      align-items: center;
    }

    .hero::before,
    .hero::after {
      display: none;
    }

    .hero-container {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 4rem;
    }

    .hero-content {
      flex: 1;
      text-align: left;
      max-width: 600px;
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 24px;
      line-height: 1.2;
    }

    .hero h1 span {
      display: block;
      font-size: 1.25rem;
      opacity: 0.9;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }

    .hero h2 {
      font-size: 1rem;
      line-height: 1.6;
      opacity: 0.9;
      font-weight: 500;
      margin-bottom: 2rem;
    }

    .hero-image {
      width: 360px;
      margin-right: -24px;
    }

    .hero-image img {
      width: 100%;
      height: auto;
      border-radius: 24px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    /* 特徴セクション */
    .features {
      padding: 80px 0;
    }

    .section-title {
      text-align: center;
      font-size: 2rem;
      margin-bottom: 48px;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 32px;
      margin-bottom: 48px;
    }

    .feature-card {
      background: var(--background);
      padding: 32px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .feature-card::before {
      content: none;
    }

    .feature-icon {
      width: 64px;
      height: 64px;
      background: var(--primary);
      color: white;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 24px;
    }

    .feature-icon .material-symbols-outlined {
      font-size: 32px;
    }

    .feature-card h3 {
      font-size: 1.25rem;
      margin-bottom: 16px;
    }

    .feature-card p {
      color: var(--text-light);
    }

    /* メリットセクション */
    .benefits {
      padding: 80px 0;
      background: var(--background-alt);
    }

    .benefits-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 24px;
    }

    .benefit-card {
      text-align: center;
      padding: 24px;
    }

    .benefit-number {
      font-size: 3rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 16px;
    }

    .benefit-card h3 {
      margin-bottom: 12px;
    }

    /* 導入ステップ */
    .steps {
      padding: 80px 0;
    }

    .steps-list {
      max-width: 800px;
      margin: 0 auto;
    }

    .step-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 48px;
    }

    .step-number {
      width: 40px;
      height: 40px;
      background: var(--primary);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      flex-shrink: 0;
      margin-right: 24px;
    }

    .step-content h3 {
      margin-bottom: 12px;
    }

    /* CTA セクション */
    .cta-section {
      padding: 80px 0;
      background: var(--primary);
      color: white;
      text-align: center;
    }

    .cta-section h2 {
      font-size: 2rem;
      margin-bottom: 24px;
    }

    .cta-section p {
      margin-bottom: 40px;
      font-size: 1.25rem;
      opacity: 0.9;
    }

    .cta-button {
      display: inline-block;
      padding: 16px 40px;
      background: white;
      color: var(--primary);
      text-decoration: none;
      border-radius: 8px;
      font-weight: 700;
      font-size: 1.25rem;
      transition: transform 0.2s;
    }

    .cta-button:hover {
      transform: translateY(-2px);
    }

    /* フッター */
    footer {
      padding: 48px 0;
      background: var(--background-alt);
      text-align: center;
    }

    .footer-logo {
      height: 32px;
      width: auto;
      margin-bottom: 24px;
    }

    .footer-links {
      margin-bottom: 24px;
    }

    .footer-links a {
      color: var(--text-light);
      text-decoration: none;
      margin: 0 12px;
    }

    .footer-links a:hover {
      color: var(--primary);
    }

    .copyright {
      color: var(--text-light);
      font-size: 0.875rem;
    }

    @media (max-width: 768px) {
      .nav-link {
        display: none;
      }

      .logo {
        height: 40px;
      }

      .hero {
        padding: 120px 24px 60px;
        text-align: center;
      }

      .hero-container {
        flex-direction: column;
        gap: 3rem;
      }

      .hero-content {
        text-align: center;
      }

      .hero h1 {
        font-size: 2.5rem;
      }

      .hero-image {
        width: 280px;
        margin: 0 auto;
      }

      .section-title {
        font-size: 1.75rem;
      }

      .feature-card, .benefit-card {
        padding: 24px;
      }

      .step-item {
        margin-bottom: 32px;
      }

      .footer-logo {
        height: 24px;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="container header-inner">
      <div class="logo-container">
        <img src="/assets/images/cms_logo.png" alt="タビガイド" class="logo">
      </div>
      <nav class="header-nav">
        <a href="#features" class="nav-link">機能</a>
        <a href="#benefits" class="nav-link">メリット</a>
        <a href="#pricing" class="nav-link">料金</a>
        <a href="/login/" class="header-cta">ログイン</a>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container hero-container">
      <div class="hero-content">
        <h1>
          <span>宿泊施設向けAIコンシェルジュ</span>
          あなたの施設専用AIを、<br>たった5分で。
        </h1>
        <h2>URLを1本登録するだけ。<br>スタッフ負担-40%・顧客満足度+25%を実現</h2>
      </div>
      <div class="hero-image">
        <img src="/assets/images/phone.png" alt="タビガイドのチャットUIイメージ">
      </div>
    </div>
  </section>

  <section class="features" id="features">
    <div class="container">
      <h2 class="section-title">タビガイドの特徴</h2>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <span class="material-symbols-outlined">smart_toy</span>
          </div>
          <h3>AIが自動で学習</h3>
          <p>施設の情報を読み込んで、専用のAIチャットボットを自動生成。URLを登録するだけの簡単設定です。</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <span class="material-symbols-outlined">schedule</span>
          </div>
          <h3>24時間対応</h3>
          <p>予約確認、チェックイン方法、周辺案内など、ゲストからのよくある問い合わせに24時間自動で対応します。</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <span class="material-symbols-outlined">map</span>
          </div>
          <h3>地図連動ガイド</h3>
          <p>周辺のおすすめスポットや飲食店を、地図と連動して案内。ゲストの観光をサポートします。</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <span class="material-symbols-outlined">translate</span>
          </div>
          <h3>多言語対応</h3>
          <p>日本語、英語、中国語など多言語に対応。インバウンドゲストとのコミュニケーションもスムーズです。</p>
        </div>
      </div>
    </div>
  </section>

  <section class="benefits" id="benefits">
    <div class="container">
      <h2 class="section-title">導入のメリット</h2>
      <div class="benefits-grid">
        <div class="benefit-card">
          <div class="benefit-number">-30%</div>
          <h3>人件費削減</h3>
          <p>問い合わせ対応の自動化で、人件費を大幅カット</p>
        </div>
        <div class="benefit-card">
          <div class="benefit-number">+25%</div>
          <h3>満足度向上</h3>
          <p>24時間即レスで、ゲスト満足度アップ</p>
        </div>
        <div class="benefit-card">
          <div class="benefit-number">+40%</div>
          <h3>業務効率化</h3>
          <p>スタッフの作業負担を大幅に軽減</p>
        </div>
      </div>
    </div>
  </section>

  <section class="steps">
    <div class="container">
      <h2 class="section-title">導入の流れ</h2>
      <div class="steps-list">
        <div class="step-item">
          <div class="step-number">1</div>
          <div class="step-content">
            <h3>アカウント作成</h3>
            <p>メールアドレスで簡単登録。クレジットカードは不要です。</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-number">2</div>
          <div class="step-content">
            <h3>施設情報の登録</h3>
            <p>施設のウェブサイトURLを入力するだけ。AIが自動で情報を読み込みます。</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-number">3</div>
          <div class="step-content">
            <h3>チャットボット完成</h3>
            <p>あとは埋め込みコードを設置するだけ。即日から運用開始できます。</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="cta-section">
    <div class="container">
      <h2>今すぐタビガイドを試してみませんか？</h2>
      <p>14日間の無料トライアル付き。<br>設定も簡単、すぐに始められます。</p>
      <a href="/register/" class="cta-button">無料で始める</a>
    </div>
  </section>

  <footer>
    <div class="container">
      <img src="/assets/images/cms_logo.png" alt="タビガイド" class="footer-logo">
      <div class="footer-links">
        <a href="/terms/">利用規約</a>
        <a href="/privacy/">プライバシーポリシー</a>
        <a href="/contact/">お問い合わせ</a>
      </div>
      <p class="copyright">© 2024 TabiGuide. All rights reserved.</p>
    </div>
  </footer>
</body>
</html>
