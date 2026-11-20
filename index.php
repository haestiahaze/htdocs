<?php
$products = [
  'Windows' => [
    [
      'name' => 'Sliding Window',
      'description' => 'Smooth-sliding window built for a clean, space-saving fit.',
      'price' => ['min' => 4200, 'max' => 15000],
      'options' => ['2 panels', '3 panels', '4 panels'],
    ],
    [
      'name' => 'Awning Window',
      'description' => 'Top-hinged window that lets in fresh air while shielding from rain.',
      'price' => ['min' => 6000, 'max' => 18000],
    ],
    [
      'name' => 'Jalousie',
      'description' => 'Adjustable glass louvers for airflow and natural light.',
      'price' => ['min' => 3800, 'max' => 9500],
    ],
    [
      'name' => 'French type window',
      'description' => 'Classic multi-panel window with an elegant, open feel.',
      'price' => ['min' => 8000, 'max' => 22000],
    ],
  ],
  'Doors' => [
    [
      'name' => 'Standard Swing Door',
      'description' => 'A practical swing door made to suit your opening.',
      'price' => ['min' => 6500, 'max' => 18000],
    ],
    [
      'name' => 'Screen doors',
      'description' => 'A screen door that brings in airflow while keeping bugs out.',
      'price' => ['min' => 3500, 'max' => 9000],
    ],
    [
      'name' => 'Hanger door',
      'description' => 'A smooth-sliding hanging door available in several configurations.',
      'price' => ['min' => 7000, 'max' => 20000],
      'options' => ['Single (pocket type)', 'Double', 'Triple slide', '3 panels'],
    ],
  ],
  'Fixed Glass' => [
    [
      'name' => 'Table top glass',
      'description' => 'Custom-cut glass for a polished, protective tabletop surface.',
      'price' => ['min' => 1200, 'max' => 5500],
    ],
    [
      'name' => 'Counter enclosure',
      'description' => 'Glass counter panels for a clear, practical enclosure.',
      'price' => ['min' => 3000, 'max' => 14000],
    ],
    [
      'name' => 'Shower enclosure',
      'description' => 'Made-to-measure glass for a clean, bright shower space.',
      'price' => ['min' => 5000, 'max' => 22000],
    ],
    [
      'name' => 'Aquarium',
      'description' => 'Custom glass panels for a clear, tailored aquarium.',
      'price' => ['min' => 9000, 'max' => 40000],
    ],
    [
      'name' => 'Showcase',
      'description' => 'A glass showcase that presents products with a clean finish.',
      'price' => ['min' => 4500, 'max' => 18000],
    ],
    [
      'name' => 'Car windshield',
      'description' => 'Replacement windshield glass tailored to your vehicle.',
      'price' => ['min' => 6500, 'max' => 18000],
    ],
    [
      'name' => 'Car door glass',
      'description' => 'Replacement door glass for a precise vehicle fit.',
      'price' => ['min' => 4000, 'max' => 12000],
    ],
    [
      'name' => 'Modular cabinet',
      'description' => 'Glass cabinet panels for a sleek, functional storage solution.',
      'price' => ['min' => 3500, 'max' => 16000],
    ],
  ],
];

function escapeHtml($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatPrice($value)
{
  return '₱' . number_format((float) $value, 0);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Glass & Aluminum | Custom Fabrication</title>
  <meta name="description" content="Custom glass and aluminum fabrication, windows, doors, partitions, mirrors, and installation services.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --primary:#174a72;
      --primary-dark:#103653;
      --primary-soft:#eaf5ff;
      --accent:#36a6c9;
      --accent-soft:#e8f6fb;
      --background:#f5f8fb;
      --surface:#ffffff;
      --text:#213447;
      --muted:#647886;
      --border:#dfe9f1;
      --shadow:0 18px 45px rgba(20, 50, 75, 0.08);
      --radius:18px;
      --radius-lg:28px;
      --radius-pill:999px;
    }

    *{box-sizing:border-box}
    html{scroll-behavior:smooth;scroll-padding-top:24px}
    body{
      margin:0;
      font-family:"Poppins",Arial,sans-serif;
      background:var(--background);
      color:var(--text);
      line-height:1.7;
      overflow-x:hidden;
    }
    a{color:inherit;text-decoration:none}
    img{max-width:100%;display:block}
    input,select,textarea,button{font:inherit}
    button{border:0;cursor:pointer}
    [hidden]{display:none!important}

    .container{
      width:min(1180px,calc(100% - 32px));
      margin:0 auto;
    }

    .site-header{
      position:sticky;
      top:0;
      z-index:20;
      background:rgba(255,255,255,.94);
      backdrop-filter:blur(8px);
      border-bottom:1px solid var(--border);
    }
    .header-inner{
      display:flex;
      gap:24px;
      align-items:center;
      justify-content:space-between;
      min-height:82px;
    }
    .brand{
      display:flex;
      align-items:center;
      gap:12px;
      font-weight:800;
      letter-spacing:-.04em;
      font-size:20px;
      color:var(--primary);
    }
    .brand-mark{
      width:38px;
      height:38px;
      border-radius:12px;
      display:grid;
      place-items:center;
      background:linear-gradient(135deg,var(--primary),var(--accent));
      color:#fff;
      font-size:12px;
      font-weight:700;
    }
    .brand span{
      display:inline-flex;
      align-items:baseline;
      gap:4px;
      font-size:19px;
    }
    .brand span span{
      color:var(--accent);
      font-size:12px;
      letter-spacing:.14em;
      text-transform:uppercase;
    }
    .nav{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }
    .nav a{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:42px;
      padding:0 14px;
      border-radius:var(--radius-pill);
      font-size:14px;
      font-weight:600;
      color:var(--text);
      transition:.2s ease;
    }
    .nav a:hover{background:var(--accent-soft);color:var(--primary-dark)}
    .nav .btn{
      background:var(--primary);
      color:#fff;
      padding:0 20px;
      min-height:46px;
      border-radius:var(--radius-pill);
      font-weight:700;
    }
    .nav .btn:hover{background:var(--primary-dark)}

    .topbar-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:18px;
      padding:10px 0 14px;
    }
    .search{
      display:flex;
      align-items:center;
      width:min(430px,100%);
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:var(--radius-pill);
      padding:8px 12px 8px 16px;
      box-shadow:0 8px 20px rgba(23,74,114,.03);
    }
    .search input{
      flex:1;
      border:0;
      outline:0;
      background:transparent;
      color:var(--text);
      font-size:14px;
    }
    .search button{
      width:36px;
      height:36px;
      border-radius:50%;
      background:var(--primary-soft);
      color:var(--primary);
      display:grid;
      place-items:center;
    }

    .tag{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:38px;
      padding:0 14px;
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:var(--radius-pill);
      color:var(--muted);
      font-size:13px;
      font-weight:600;
    }
    .tag.active{
      background:var(--primary);
      border-color:var(--primary);
      color:#fff;
    }
    .category-bar{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      padding-bottom:18px;
    }
    .category-tab{
      transition:background .2s ease,color .2s ease,border-color .2s ease;
    }

    .hero{
      padding:60px 0 30px;
      background:
        radial-gradient(circle at 85% 20%,rgba(54,166,201,.15),transparent 28%),
        linear-gradient(135deg,#f9fbfd 0%,#edf6fa 100%);
    }
    .hero-grid{
      display:grid;
      grid-template-columns:1.25fr .75fr;
      align-items:center;
      gap:42px;
    }
    .eyebrow{
      display:inline-block;
      margin-bottom:18px;
      font-size:12px;
      font-weight:800;
      letter-spacing:.18em;
      text-transform:uppercase;
      color:var(--accent);
    }
    .hero h1{
      margin:0 0 20px;
      font-size:clamp(38px,5vw,60px);
      line-height:1.1;
      letter-spacing:-1.6px;
      color:var(--primary-dark);
    }
    .hero p{
      max-width:620px;
      font-size:18px;
      color:var(--muted);
      line-height:1.7;
    }
    .hero-actions{
      display:flex;
      flex-wrap:wrap;
      gap:14px;
      margin-top:26px;
    }
    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:48px;
      padding:12px 22px;
      border-radius:var(--radius-pill);
      font-weight:700;
      font-size:15px;
      transition:.2s ease;
    }
    .btn.primary{
      background:var(--primary);
      color:#fff;
      box-shadow:0 10px 24px rgba(23,74,114,.12);
    }
    .btn.primary:hover{background:var(--primary-dark)}
    .btn.secondary{
      background:transparent;
      color:var(--primary);
      border:1.5px solid var(--primary);
    }
    .btn.secondary:hover{background:var(--primary);color:#fff}

    .hero-card{
      background:rgba(255,255,255,.9);
      border:1px solid rgba(255,255,255,.9);
      border-radius:var(--radius-lg);
      padding:30px 28px;
      box-shadow:0 25px 70px rgba(22,66,96,.1);
      position:relative;
      overflow:hidden;
    }
    .hero-card::before{
      content:"";
      position:absolute;
      left:30px;
      top:0;
      width:80px;
      height:5px;
      background:var(--accent);
      border-radius:0 0 6px 6px;
    }
    .glass-icon{
      width:82px;
      height:82px;
      display:grid;
      place-items:center;
      background:linear-gradient(135deg,#dff4ff,#eefafc);
      border-radius:18px;
      font-size:52px;
      color:var(--primary);
      margin-bottom:20px;
    }
    .hero-card h3{margin:0 0 10px;font-size:24px;color:var(--primary-dark)}
    .hero-card p{margin:0;font-size:15px;color:var(--muted)}
    .mini-metrics{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:12px;
      margin-top:24px;
    }
    .metric{
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:18px;
      padding:16px 14px;
    }
    .metric strong{
      display:block;
      font-size:27px;
      line-height:1.2;
      color:var(--primary-dark);
    }
    .metric span{display:block;margin-top:2px;font-size:12px;color:var(--muted)}

    .section{padding:42px 0}
    .section-head{
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:20px;
      margin-bottom:24px;
    }
    .section-head h2{
      margin:0;
      font-size:clamp(28px,3vw,42px);
      line-height:1.1;
      letter-spacing:-1px;
      color:var(--primary-dark);
    }
    .section-head p{
      max-width:620px;
      margin:0;
      color:var(--muted);
      font-size:17px;
      line-height:1.7;
    }

    .product-grid{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:20px;
    }
    .product-card{
      overflow:hidden;
      padding:0;
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:22px;
      box-shadow:0 12px 25px rgba(15,43,62,.04);
    }
    .product-art{
      height:200px;
      background:linear-gradient(135deg,#edf8fb 0%,#d9f3f4 40%,#f6fbff 100%);
      border-bottom:1px solid var(--border);
      position:relative;
      overflow:hidden;
    }
    .product-art .shape{
      position:absolute;
      inset:18px 20px;
      border:1px solid rgba(23,74,114,.18);
      border-radius:20px;
      background:rgba(255,255,255,.4);
    }
    .product-art .shape::before,
    .product-art .shape::after{
      content:"";
      position:absolute;
      inset:0;
      border-radius:inherit;
    }
    .product-art .shape::before{
      background:
        linear-gradient(to right,transparent 49%,rgba(54,166,201,.75) 49%,rgba(54,166,201,.75) 51%,transparent 51%),
        linear-gradient(to bottom,transparent 49%,rgba(54,166,201,.75) 49%,rgba(54,166,201,.75) 51%,transparent 51%);
    }
    .product-art .shape::after{
      inset:12% 14%;
      border:1px solid rgba(23,74,114,.12);
      background:rgba(255,255,255,.25);
    }
    .product-body{padding:18px 18px 20px}
    .product-meta{
      display:inline-block;
      margin-bottom:8px;
      color:var(--primary);
      font-size:11px;
      letter-spacing:.12em;
      font-weight:700;
      text-transform:uppercase;
    }
    .product-body h3{margin:0 0 10px;font-size:18px;color:var(--primary-dark)}
    .product-body p{
      margin:0 0 12px;
      color:var(--muted);
      font-size:14px;
      line-height:1.7;
    }
    .product-price{
      display:block;
      margin:0 0 14px;
      color:var(--primary-dark);
      font-size:14px;
      font-weight:700;
    }
    .product-option{margin:0 0 14px}
    .product-option label{
      display:block;
      margin-bottom:6px;
      font-size:13px;
      font-weight:700;
      color:var(--text);
    }

    .band{
      background:#eaf5f7;
      border-top:1px solid var(--border);
      border-bottom:1px solid var(--border);
      margin-top:22px;
    }
    .band-inner{padding:44px 0 30px}
    .steps{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:20px;
      list-style:none;
      margin:22px 0 0;
      padding:0;
    }
    .steps li{
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:var(--radius);
      padding:22px 20px;
    }
    .steps .num{
      width:38px;
      height:38px;
      display:grid;
      place-items:center;
      background:var(--primary);
      color:#fff;
      border-radius:12px;
      font-weight:800;
      margin-bottom:14px;
    }
    .steps h3{margin:0 0 8px;font-size:18px;color:var(--primary-dark)}
    .steps p{margin:0;color:var(--muted);font-size:14px;line-height:1.7}

    input,select,textarea{
      width:100%;
      border:1px solid var(--border);
      border-radius:12px;
      background:#fff;
      padding:12px 14px;
      color:var(--text);
      outline:none;
      transition:border-color .2s ease,box-shadow .2s ease;
    }
    input:focus,select:focus,textarea:focus{
      border-color:var(--primary);
      box-shadow:0 0 0 4px rgba(23,74,114,.08);
    }
    .product-option select{padding:10px 12px;font-size:14px}
    .btn-sm{min-height:38px;padding:0 14px;font-size:13px}

    .site-footer{padding:26px 0 40px;margin-top:26px}
    .footer-inner{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:20px;
      border-top:1px solid var(--border);
      padding-top:18px;
      color:var(--muted);
      font-size:14px;
    }
    .footer-links{display:flex;gap:18px;flex-wrap:wrap}

    .category-tab:focus-visible,
    .btn:focus-visible,
    button:focus-visible,
    a:focus-visible,
    select:focus-visible{
      outline:3px solid var(--accent);
      outline-offset:3px;
    }

    @media (max-width:980px){
      .hero-grid{grid-template-columns:1fr}
      .product-grid,.steps{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media (max-width:720px){
      .header-inner{
        flex-wrap:wrap;
        justify-content:center;
        padding:14px 0;
      }
      .nav{justify-content:center}
      .topbar-row{flex-direction:column;align-items:stretch}
      .search{width:100%}
      .section-head{display:block}
      .section-head p{margin-top:12px}
      .product-grid,.steps{grid-template-columns:1fr}
      .footer-inner{flex-direction:column;align-items:flex-start}
    }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container">
      <div class="header-inner">
        <a href="#" class="brand" aria-label="Glass & Aluminum home">
          <span class="brand-mark">GA</span>
          <span>Glass <span>Aluminum</span></span>
        </a>

        <nav class="nav" aria-label="Main navigation">
          <a href="#home">Home</a>
          <a href="#products">Products</a>
          <a href="#services">Services</a>
          <a href="register.php" class="btn">Inquire now</a>
        </nav>
      </div>

      <div class="topbar-row">
        <form class="search" method="get" action="#products" role="search">
          <input type="search" name="q" placeholder="Search products" aria-label="Search products">
          <button type="submit" aria-label="Search">
            <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="8.5" cy="8.5" r="6"></circle>
              <path d="M13 13L18 18"></path>
            </svg>
          </button>
        </form>
      </div>
    </div>
  </header>

  <main id="home">
    <section class="hero">
      <div class="container hero-grid">
        <div>
          <div class="eyebrow">Custom fabrication • installation</div>
          <h1>Made-to-measure glass for the rooms you use every day.</h1>
          <p>
            We design and install shower enclosures, glass panels, windows, doors,
            partitions, and mirrors with a clean finish and dependable site service.
          </p>

          <div class="hero-actions">
            <a href="#available" class="btn primary" id="hero-available">Products Available</a>
            <a href="#products" class="btn secondary">Browse products</a>
          </div>
        </div>

        <div class="hero-card">
          <div class="glass-icon" aria-hidden="true">▣</div>
          <h3>From concept to installation</h3>
          <p>Free quotation, measurements, fabrication, and professional installation.</p>

          <div class="mini-metrics">
            <div class="metric">
              <strong>2 months</strong>
              <span>workmanship warranty</span>
            </div>
            <div class="metric">
              <strong>Free</strong>
              <span>site inspection</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="products">
      <div class="container">
        <div class="section-head">
          <h2>Popular products</h2>
          <p>Practical, durable, and built for everyday use across homes, offices, and commercial spaces.</p>
        </div>

        <div class="product-grid">
          <article class="product-card">
            <div class="product-art" aria-hidden="true"><div class="shape"></div></div>
            <div class="product-body">
              <span class="product-meta">Windows</span>
              <h3>Windows</h3>
              <p>Space-saving designs with clean lines and smooth operation for residential and commercial use.</p>
              <button type="button" class="btn primary btn-sm popular-category-button" data-category="Windows">View products</button>
            </div>
          </article>

          <article class="product-card">
            <div class="product-art" aria-hidden="true"><div class="shape"></div></div>
            <div class="product-body">
              <span class="product-meta">Doors</span>
              <h3>Doors</h3>
              <p>Modern glass access solutions for interior and exterior openings with a premium finish.</p>
              <button type="button" class="btn primary btn-sm popular-category-button" data-category="Doors">View products</button>
            </div>
          </article>

          <article class="product-card">
            <div class="product-art" aria-hidden="true"><div class="shape"></div></div>
            <div class="product-body">
              <span class="product-meta">Glass</span>
              <h3>Fixed Glass</h3>
              <p>Custom fixed glass panels and enclosures that bring light and openness to any space.</p>
              <button type="button" class="btn primary btn-sm popular-category-button" data-category="Fixed Glass">View products</button>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="section" id="available" aria-labelledby="available-title">
      <div class="container">
        <div class="section-head">
          <div>
            <h2 id="available-title">Available products</h2>
            <p id="available-category-heading" aria-live="polite">Select a category to view available products.</p>
          </div>
        </div>

        <div class="category-bar" role="tablist" aria-label="Available product categories">
          <?php foreach (array_keys($products) as $index => $category): ?>
            <button
              type="button"
              class="tag category-tab"
              role="tab"
              id="tab-<?= escapeHtml((string) $index) ?>"
              aria-selected="false"
              aria-controls="panel-<?= escapeHtml((string) $index) ?>"
              tabindex="-1"
              data-category="<?= escapeHtml($category) ?>"
            ><?= escapeHtml($category) ?></button>
          <?php endforeach; ?>
        </div>

        <p id="available-placeholder">Select a category to view available products.</p>

        <?php foreach ($products as $category => $categoryProducts): ?>
          <?php $categoryIndex = array_search($category, array_keys($products), true); ?>
          <div
            class="product-grid available-grid"
            id="panel-<?= escapeHtml((string) $categoryIndex) ?>"
            role="tabpanel"
            aria-labelledby="tab-<?= escapeHtml((string) $categoryIndex) ?>"
            data-category-panel="<?= escapeHtml($category) ?>"
            hidden
          >
            <?php foreach ($categoryProducts as $productIndex => $product): ?>
              <?php $optionId = 'option-' . $categoryIndex . '-' . $productIndex; ?>
              <article class="product-card">
                <div class="product-art" aria-hidden="true"><div class="shape"></div></div>
                <div class="product-body">
                  <span class="product-meta"><?= escapeHtml($category) ?></span>
                  <h3><?= escapeHtml($product['name']) ?></h3>
                  <p><?= escapeHtml($product['description']) ?></p>

                  <span class="product-price">
                    <?= escapeHtml(formatPrice($product['price']['min'])) ?>
                    &ndash;
                    <?= escapeHtml(formatPrice($product['price']['max'])) ?>
                  </span>

                  <?php if (!empty($product['options'])): ?>
                    <div class="product-option">
                      <label for="<?= escapeHtml($optionId) ?>">Choose type or panels</label>
                      <select id="<?= escapeHtml($optionId) ?>" required>
                        <option value="">Select type/panels</option>
                        <?php foreach ($product['options'] as $option): ?>
                          <option value="<?= escapeHtml($option) ?>"><?= escapeHtml($option) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  <?php endif; ?>

                  <button
                    type="button"
                    class="btn primary btn-sm inquire-product"
                    data-product="<?= escapeHtml($product['name']) ?>"
                    <?php if (!empty($product['options'])): ?>
                      data-select-id="<?= escapeHtml($optionId) ?>"
                    <?php endif; ?>
                  >Inquire</button>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="band" id="services">
      <div class="container band-inner">
        <div class="section-head">
          <h2>How an order works</h2>
        </div>

        <ol class="steps">
          <li>
            <div class="num">1</div>
            <h3>Consult</h3>
            <p>Share your space, measurements, and project needs with our team.</p>
          </li>
          <li>
            <div class="num">2</div>
            <h3>Inspect</h3>
            <p>We assess the area and recommend the best materials and fitting approach.</p>
          </li>
          <li>
            <div class="num">3</div>
            <h3>Fabricate</h3>
            <p>We custom-cut, process, and prepare the components for installation.</p>
          </li>
          <li>
            <div class="num">4</div>
            <h3>Install</h3>
            <p>Our team handles installation and the final quality check for a clean finish.</p>
          </li>
        </ol>
      </div>
    </section>
  </main>

  <footer class="site-footer" id="contact">
    <div class="container footer-inner">
      <div>
        <strong>Glass & Aluminum</strong><br>
        <span>Custom fabrication and installation</span>
      </div>

      <div class="footer-links">
        <a href="#products">Products</a>
        <a href="#services">Services</a>
      </div>

      <div>
        09479852349<br>
        glassandaluminum@gmail.com
      </div>
    </div>
  </footer>

  <script>
    (() => {
      const availableSection = document.getElementById('available');
      const placeholder = document.getElementById('available-placeholder');
      const categoryHeading = document.getElementById('available-category-heading');
      const tabs = Array.from(document.querySelectorAll('.category-tab'));
      const panels = Array.from(document.querySelectorAll('[data-category-panel]'));

      function openCategory(category, shouldScroll = true) {
        const activeTab = tabs.find((tab) => tab.dataset.category === category);
        if (!activeTab) return;

        tabs.forEach((tab) => {
          const isActive = tab === activeTab;
          tab.classList.toggle('active', isActive);
          tab.setAttribute('aria-selected', String(isActive));
          tab.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
          panel.hidden = panel.dataset.categoryPanel !== category;
        });

        placeholder.hidden = true;
        categoryHeading.textContent = category;

        if (shouldScroll) {
          availableSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }

      document.querySelectorAll('.popular-category-button').forEach((button) => {
        button.addEventListener('click', () => openCategory(button.dataset.category));
      });

      tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => openCategory(tab.dataset.category, false));

        tab.addEventListener('keydown', (event) => {
          let nextIndex = index;

          if (event.key === 'ArrowRight') {
            nextIndex = (index + 1) % tabs.length;
          } else if (event.key === 'ArrowLeft') {
            nextIndex = (index - 1 + tabs.length) % tabs.length;
          } else if (event.key === 'Home') {
            nextIndex = 0;
          } else if (event.key === 'End') {
            nextIndex = tabs.length - 1;
          } else {
            return;
          }

          event.preventDefault();
          tabs[nextIndex].focus();
          openCategory(tabs[nextIndex].dataset.category, false);
        });
      });

      document.getElementById('hero-available').addEventListener('click', (event) => {
        event.preventDefault();
        const selectedTab = tabs.find((tab) => tab.getAttribute('aria-selected') === 'true');
        openCategory(selectedTab ? selectedTab.dataset.category : 'Windows');
      });

      document.querySelectorAll('.inquire-product').forEach((button) => {
        button.addEventListener('click', () => {
          const productName = button.dataset.product;
          const selectId = button.dataset.selectId;
          let inquiryItem = productName;

          if (selectId) {
            const select = document.getElementById(selectId);

            if (!select.value) {
              select.setCustomValidity('Please select a type or number of panels.');
              select.reportValidity();
              select.addEventListener('change', () => {
                select.setCustomValidity('');
              }, { once: true });
              return;
            }

            inquiryItem += ' - ' + select.value;
          }

          const destination = new URL('register.php', window.location.href);
          destination.searchParams.set('product', inquiryItem);
          window.location.href = destination.toString();
        });
      });
    })();
  </script>

</body>
</html>