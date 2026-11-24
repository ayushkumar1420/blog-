<?php
$postsFile = __DIR__ . '/../blog-cms-staging/deepshitech-blog-admin/data/posts.json';
$allPosts = [];
if (is_file($postsFile) && is_readable($postsFile)) {
    $decoded = json_decode(file_get_contents($postsFile), true);
    if (is_array($decoded)) {
        $allPosts = isset($decoded['posts']) && is_array($decoded['posts']) ? $decoded['posts'] : $decoded;
    }
}

function escapeHtml($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$slug = (string) ($_GET['slug'] ?? '');
$post = null;
$publishedPosts = [];
foreach ($allPosts as $item) {
    if (!is_array($item) || strtolower((string) ($item['status'] ?? '')) !== 'published') {
        continue;
    }
    $publishedPosts[] = $item;
    if ((string) ($item['slug'] ?? '') === $slug) {
        $post = $item;
    }
}

if (!$post) {
    http_response_code(404);

    $seoTitle = 'Blog Not Found | DeepshiTech';
    $metaDescription = 'The requested blog article could not be found.';
    $canonicalUrl = 'https://deepshitech.com/blog/';
} else {
    $seoTitle = trim((string) ($post['seo_title'] ?? ''));

    if ($seoTitle === '') {
        $seoTitle = (string) ($post['title'] ?? 'Blog') . ' | DeepshiTech';
    }

    $metaDescription = trim((string) ($post['meta_description'] ?? ''));

    if ($metaDescription === '') {
        $metaDescription = trim(strip_tags((string) ($post['excerpt'] ?? '')));
    }

    if ($metaDescription === '') {
        $metaDescription = trim(strip_tags((string) ($post['content'] ?? '')));
    }

    $metaDescription = preg_replace('/\s+/', ' ', $metaDescription);
    $metaDescription = mb_substr($metaDescription, 0, 160, 'UTF-8');

    $canonicalUrl = trim((string) ($post['canonical_url'] ?? ''));

    if (
        $canonicalUrl === '' ||
        !filter_var($canonicalUrl, FILTER_VALIDATE_URL) ||
        !in_array(strtolower((string) parse_url($canonicalUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
    ) {
        $canonicalUrl = 'https://deepshitech.com/blog/post.php?slug=' . rawurlencode((string) ($post['slug'] ?? ''));
    }
}

$pageTitle = $seoTitle;

function postImageUrl($item): string {
    $image = (string) ($item['featured_image'] ?? '');

    if ($image === '') return '';

    if (strpos($image, 'images/blog/') === 0) {
        return '/blog-cms-staging/deepshitech-blog-admin/' . $image;
    }

    if (strpos($image, 'img/') === 0) {
        return '../' . $image;
    }

    return '';
}

$imageUrl = $post ? postImageUrl($post) : '';
$ogImageUrl = '';

if ($imageUrl !== '') {
    if (preg_match('~^https?://~i', $imageUrl)) {
        $ogImageUrl = $imageUrl;
    } elseif (strpos($imageUrl, '/') === 0) {
        $ogImageUrl = 'https://deepshitech.com' . $imageUrl;
    } else {
        $ogImageUrl = 'https://deepshitech.com/' . ltrim(str_replace('../', '', $imageUrl), '/');
    }
}

$ogType = $post ? 'article' : 'website';

$imageAlt = $post ? trim((string) ($post['image_alt'] ?? '')) : '';

if ($imageAlt === '' && $post) {
    $imageAlt = (string) ($post['title'] ?? 'Blog image');
}
$category = (string) ($post['category'] ?? '');
$author = (string) ($post['author'] ?? 'admin');
$rawDate = (string) ($post['created_at'] ?? '');
$displayDate = $rawDate !== '' ? date('d M Y', strtotime($rawDate) ?: time()) : '';
$similarPosts = [];
if ($post) {
    foreach ($publishedPosts as $candidate) {
        if (($candidate['slug'] ?? '') === $slug) continue;
        $similarPosts[] = $candidate;
        if (count($similarPosts) >= 2) break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Basic SEO -->
<meta name="description" content="<?= escapeHtml($metaDescription) ?>">
<link rel="canonical" href="<?= escapeHtml($canonicalUrl) ?>">

<!-- Open Graph SEO -->
<meta property="og:title" content="<?= escapeHtml($seoTitle) ?>">
<meta property="og:description" content="<?= escapeHtml($metaDescription) ?>">
<meta property="og:url" content="<?= escapeHtml($canonicalUrl) ?>">
<meta property="og:type" content="<?= escapeHtml($ogType) ?>">
<meta property="og:site_name" content="DeepshiTech">

<?php if ($ogImageUrl !== ''): ?>
<meta property="og:image" content="<?= escapeHtml($ogImageUrl) ?>">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= escapeHtml($seoTitle) ?>">
<meta name="twitter:description" content="<?= escapeHtml($metaDescription) ?>">

<?php if ($ogImageUrl !== ''): ?>
<meta name="twitter:image" content="<?= escapeHtml($ogImageUrl) ?>">
<?php endif; ?>

<?php
$schemaImage = $ogImageUrl !== ''
    ? $ogImageUrl
    : '';

$schemaAuthor = trim((string)($post['author'] ?? 'DeepshiTech'));

$schemaPublished = $post['created_at'] ?? '';
$schemaModified = $post['updated_at'] ?? $schemaPublished;

$publishedDate = strtotime($schemaPublished);
$modifiedDate = strtotime($schemaModified);
?>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $canonicalUrl
    ],
    'headline' => $seoTitle,
    'description' => $metaDescription,
    'image' => $schemaImage !== '' ? [$schemaImage] : [],
    'author' => [
        '@type' => 'Organization',
        'name' => 'Deepshitech'
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'DeepshiTech'
    ],
    'datePublished' => $publishedDate
        ? date('c', $publishedDate)
        : null,
    'dateModified' => $modifiedDate
        ? date('c', $modifiedDate)
        : null
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>

    <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <!-- bootstrap grid css -->
<link rel="stylesheet" href="../../../css/plugins/bootstrap-grid.css">

<!-- font awesome css -->
<link rel="stylesheet" href="../../../css/plugins/font-awesome.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    integrity="sha512-SnH5WK+bzXgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- swiper css -->
<link rel="stylesheet" href="../../../css/plugins/swiper.min.css">

<!-- fancybox css -->
<link rel="stylesheet" href="../../../css/plugins/fancybox.min.css">

<!-- ashley css -->
<link rel="stylesheet" href="../../../css/style.css">
<link rel="stylesheet" href="../../../css/custom.css">
<style>
/* Blog article table styling */
.blog-article-content {
    max-width: 100%;
    overflow-wrap: break-word;
}

.blog-article-content table {
    display: table !important;
    width: 100% !important;
    max-width: 100%;
    border-collapse: collapse !important;
    border-spacing: 0;
    margin: 25px 0;
    table-layout: auto;
    background: #fff;
}

.blog-article-content table th,
.blog-article-content table td {
    display: table-cell !important;
    border: 1px solid #d5d5d5 !important;
    padding: 12px 15px !important;
    text-align: left;
    vertical-align: middle;
    min-width: 100px;
    line-height: 1.6;
}

.blog-article-content table th {
    background: #f2f2f2;
    font-weight: 700;
}

.blog-article-content table tr:nth-child(even) {
    background: #fafafa;
}

.blog-article-content table tr:hover {
    background: #f5f7fa;
}

@media (max-width: 768px) {
    .blog-article-content {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .blog-article-content table {
        min-width: 550px;
    }
}
</style>

    <link rel="stylesheet" type="text/css" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.19.1/ui/trumbowyg.min.css">
    <!-- page name -->
    <title><?= escapeHtml($pageTitle) ?></title>
    <link rel="icon" href="../img/Fev_icon.png" sizes="16x16">
</head>

<body>
    <script>window.MIL_PATH_PREFIX = "../";</script>
    <div class="mil-wrapper" id="top">
        <div id="site-header">
            <!-- cursor -->
            <div class="mil-ball">
                <span class="mil-icon-1">
                    <svg viewBox="0 0 128 128">
                        <path
                            d="M106.1,41.9c-1.2-1.2-3.1-1.2-4.2,0c-1.2,1.2-1.2,3.1,0,4.2L116.8,61H11.2l14.9-14.9c1.2-1.2,1.2-3.1,0-4.2	c-1.2-1.2-3.1-1.2-4.2,0l-20,20c-1.2,1.2-1.2,3.1,0,4.2l20,20c0.6,0.6,1.4,0.9,2.1,0.9s1.5-0.3,2.1-0.9c1.2-1.2,1.2-3.1,0-4.2	L11.2,67h105.5l-14.9,14.9c-1.2,1.2-1.2,3.1,0,4.2c0.6,0.6,1.4,0.9,2.1,0.9s1.5-0.3,2.1-0.9l20-20c1.2-1.2,1.2-3.1,0-4.2L106.1,41.9	z" />
                    </svg>
                </span>
                <div class="mil-more-text">More</div>
                <div class="mil-choose-text">Сhoose</div>
            </div>
            <!-- cursor end -->

            <!-- scrollbar progress -->
            <div class="mil-progress-track">
                <div class="mil-progress"></div>
            </div>
            <!-- scrollbar progress end -->

            <!-- language switcher -->
            <div class="mil-lang-switcher" id="langSwitcher">
                <button type="button" class="mil-lang-current" id="langCurrentBtn" aria-haspopup="true"
                    aria-expanded="false">  
                    <span class="mil-lang-flag" id="langCurrentFlag">🇬🇧</span>
                    <span class="mil-lang-code" id="langCurrentCode">EN</span>
                </button>
                <ul class="mil-lang-list" id="langList">
                    <li><button type="button" data-lang="en"><span class="mil-lang-flag">🇬🇧</span> English</button>
                    </li>
                    <li><button type="button" data-lang="hi"><span class="mil-lang-flag">🇮🇳</span> हिन्दी</button>
                    </li>
                    <li><button type="button" data-lang="es"><span class="mil-lang-flag">🇪🇸</span> Español</button>
                    </li>
                    <li><button type="button" data-lang="fr"><span class="mil-lang-flag">🇫🇷</span> Français</button>
                    </li>
                    <li><button type="button" data-lang="ar"><span class="mil-lang-flag">🇸🇦</span> العربية</button>
                    </li>
                    <li><button type="button" data-lang="zh"><span class="mil-lang-flag">🇨🇳</span> 中文</button></li>
                </ul>
            </div>
            <!-- language switcher end -->

            <!-- menu -->
            <!-- =========================================================
     FULLSCREEN NAVIGATION MENU
========================================================= -->
            <div class="mil-menu-frame">
                <!-- TOP BAR -->
                <div class="mil-frame-top">
                    <!-- LOGO -->
                    <a href="/index.php" class="mil-logo">
                        <img src="../img/logo.png" class="logo-d show-logo" alt="DeepshiTech">
                        <img src="../img/logo2.png" class="logo-d show-logo-mob" alt="DeepshiTech">
                    </a>
                    <!-- MENU CLOSE / TOGGLE -->
                    <div class="mil-menu-btn">
                        <span></span>
                    </div>
                </div>
                <!-- =====================================================
         MENU CONTENT
    ====================================================== -->
                <div class="mil-menu-content">
                    <div class="container">
                        <div class="mil-menu-layout">
                            <nav class="mil-main-menu" id="swupMenu">
                                <ul class="mil-menu-list">
                                    <!-- =========================
                             HOMEPAGE
                        ========================== -->
                                    <li class="mil-has-children mil-active">
                                        <a href="../index.php">
                                            Homepage
                                        </a>
                                        <ul class="mil-submenu">
                                            <li>
                                                <a href="../index.php">
                                                    Landing page
                                                </a>
                                            </li>
                                            <li>
                                                <a href="../personal/index.html">
                                                    Personal
                                                </a>
                                            </li>
                                            <li>
                                                <a href="../portfolio-slider/index.html">
                                                    Portfolio slider
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <!-- =========================
                             PORTFOLIO
                        ========================== -->
                                    <li class="mil-has-children">
                                        <a href="../portfolio/index.html">
                                            Portfolio
                                        </a>
                                        <ul class="mil-submenu">
                                            <li>
                                                <a href="../portfolio/index.html">
                                                    All projects
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <!-- =========================
                             SERVICES
                        ========================== -->
                                    <li class="mil-has-children mil-services-menu-item">
                                        <a href="../services/index.html">
                                            Services
                                        </a>
                                        <ul class="mil-submenu" id="servicesMenuList">
                                            <!--
                                    Services will continue to be
                                    populated by components.js
                                -->
                                        </ul>
                                    </li>
                                    <!-- =========================
                             NEWSLETTER
                        ========================== -->
                                    <li class="mil-has-children">
                                        <a href="#.">
                                            Newsletter
                                        </a>
                                        <ul class="mil-submenu">
                                            <li>
                                                <a href="index.php">
                                                    Blog List
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <!-- =========================
                             OTHER PAGES
                        ========================== -->
                                    <li class="mil-has-children">
                                        <a href="#.">
                                            Other pages
                                        </a>
                                        <ul class="mil-submenu">
                                            <li>
                                                <a href="../team/index.html">
                                                    Team
                                                </a>
                                            </li>
                                            <li>
                                                <a href="../contact/index.html">
                                                    Contact
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <!-- menu -->

            <!-- curtain -->
            <div class="mil-curtain"></div>
            <!-- curtain end -->

            <!-- frame -->
            <div class="mil-frame">
                <div class="mil-frame-top">
                    <!-- NORMAL HEADER LOGO -->
                    <a href="../index.php" class="mil-logo mil-normal-header-logo">
                        <!-- DESKTOP: D ICON ONLY -->
                        <img src="../img/logo.png" class="mil-header-logo-desktop" alt="DeepshiTech">
                        <!-- MOBILE: FULL DEEPSHITECH LOGO -->
                        <img src="../img/logo2.png" class="mil-header-logo-mobile" alt="DeepshiTech">
                    </a>
                    <!-- MENU BUTTON -->
                    <div class="mil-menu-btn">
                        <span></span>
                    </div>
                </div>
                <div class="mil-frame-bottom">
                    <div class="mil-current-page" id="milCurrentPage">
                    </div>
                    <div class="mil-back-to-top">
                        <a href="#top" class="mil-link mil-dark mil-arrow-place">
                            <span>Back to top</span>
                        </a>
                    </div>
                </div>
            </div>
            <!-- frame end -->

            <!-- content -->
            <div class="mil-content">
                <div id="swupMain" class="mil-main-transition">
                    <!-- banner -->
                    <div class="mil-inner-banner">
                        <div class="mil-banner-content mil-center mil-up">
                            <div class="container">
                                <ul class="mil-breadcrumbs mil-center mil-mb-60">
                                    <li><a href="../index.php">Homepage</a></li>
                                    <li><a href="index.php">Blog</a></li>
                                    <li><?= $post ? escapeHtml($post['title'] ?? 'Publication') : 'Publication' ?></li>
                                </ul>
                                <?php if ($post): ?>
                                    <h2><?= escapeHtml($post['title'] ?? '') ?></h2>
                                <?php else: ?>
                                    <h2>Blog not found</h2>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- banner end -->

                    <!-- Dynamic publication: original DeepshiTech article layout -->
                    <?php if ($post): ?>
                    <section id="blog">
                        <div class="container mil-p-120-90">
                            <div class="row justify-content-center">
                                <div class="col-lg-12">
                                    <?php if ($imageUrl !== ''): ?>
                                    <div class="mil-image-frame mil-horizontal mil-up">
                                        <img src="<?= escapeHtml($imageUrl) ?>" alt="<?= escapeHtml($post['title'] ?? '') ?>" class="mil-scale" data-value-1=".90" data-value-2="1.15">
                                </div>
                                    <?php endif; ?>
                                    <div class="mil-info mil-up mil-mb-90">
                                        <div>Category: &nbsp;<span class="mil-dark"><?= escapeHtml($category) ?></span></div>
                                        <?php if ($displayDate !== ''): ?><div>Date: &nbsp;<span class="mil-dark"><?= escapeHtml($displayDate) ?></span></div><?php endif; ?>
                                        <div>Author: &nbsp;<span class="mil-dark"><?= escapeHtml($author) ?></span></div>
                                    </div>
                                </div>
                                <div class="col-lg-8">
                                    <?php if (!empty($post['excerpt'])): ?>
                                        <p class="mil-up mil-mb-30"><?= escapeHtml($post['excerpt']) ?></p>
                                    <?php endif; ?>
                                    <article class="blog-article-content mil-up">
                                        <?php
                                        // Content is HTML authored by trusted admin users in the CMS.
                                        // Restrict admin access and do not accept article HTML from public users.
                                        echo $post['content'] ?? '';
                                        ?>
                                    </article>
                                    <div class="mil-mt-60 mil-up">
                                        <!-- <a href="index.php" class="mil-link mil-dark mil-arrow-place"><span>Back to all blogs</span></a> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php else: ?>
                    <section id="blog">
                        <div class="container mil-p-120-90">
                            <div class="row justify-content-center"><div class="col-lg-8">
                                <h2>Blog not found</h2>
                                <p>This article may have been removed or is not published.</p>
                                <a href="index.php" class="mil-link mil-dark mil-arrow-place"><span>Return to Blog</span></a>
                            </div></div>
                        </div>
                    </section>
                    <?php endif; ?>
                    <!-- publication end -->

                    <!-- similar -->
                    <?php if (!empty($similarPosts)): ?>
                    <section class="mil-soft-bg">
                        <div class="container mil-p-120-60">
                            <div class="row align-items-center mil-mb-30">
                                <div class="col-lg-6 mil-mb-30"><h3 class="mil-up">Similar Publications:</h3></div>
                                <div class="col-lg-6 mil-mb-30"><div class="mil-adaptive-right mil-up"><a href="index.php" class="mil-link mil-dark mil-arrow-place"><span>View all</span></a></div></div>
                            </div>
                            <div class="row">
                                <?php foreach ($similarPosts as $similar): ?>
                                <?php
                                       $similarImage = postImageUrl($similar);
                                       $similarImageAlt = trim((string) ($similar['image_alt'] ?? ''));

                                       if ($similarImageAlt === '') {
                                            $similarImageAlt = (string) ($similar['title'] ?? 'Blog cover');
                                       }
                                   ?>
                                <div class="col-lg-6">
                                    <a href="post.php?slug=<?= rawurlencode((string) ($similar['slug'] ?? '')) ?>" class="mil-blog-card mil-mb-60">
                                        <?php if ($similarImage !== ''): ?>
                                        <div class="mil-cover-frame mil-up"><img src="<?= escapeHtml($similarImage) ?>" alt="<?= escapeHtml($similarImageAlt) ?>"></div>
                                        <?php endif; ?>
                                        <div class="mil-post-descr">
                                            <div class="mil-labels mil-up mil-mb-30">
                                                <div class="mil-label mil-upper mil-accent"><?= escapeHtml($similar['category'] ?? '') ?></div>
                                                <?php if (!empty($similar['created_at'])): ?><div class="mil-label mil-upper"><?= escapeHtml(date('d M Y', strtotime((string) $similar['created_at']) ?: time())) ?></div><?php endif; ?>
                                            </div>
                                            <h4 class="mil-up mil-mb-30"><?= escapeHtml($similar['title'] ?? '') ?></h4>
                                            <p class="mil-post-text mil-up mil-mb-30"><?= escapeHtml($similar['excerpt'] ?? '') ?></p>
                                            <div class="mil-link mil-dark mil-arrow-place mil-up"><span>Read more</span></div>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>
                    <!-- similar end -->

                    <!-- footer -->
                    <div id="site-footer">
                        <footer class="mil-dark-bg">
                            <div class="mi-invert-fix">
                                <div class="container mil-p-120-60">
                                    <div class="row justify-content-between">
                                        <div class="col-md-4 col-lg-4 mil-mb-60">

                                            <div class="mil-muted mil-logo mil-up mil-mb-30"><img
                                                    src="../img/logo2.png" style="width:250px" alt="Deepshitech">
                                            </div>

                                            <p class="mil-light-soft mil-up mil-mb-30">Subscribe our newsletter:</p>

                                            <form class="mil-subscribe-form mil-up" onsubmit="return false;">
                                                <input type="email" placeholder="Enter your email" required>
                                                <button type="submit"
                                                    class="mil-button mil-icon-button-sm mil-arrow-place"></button>
                                            </form>

                                        </div>
                                        <div class="col-md-7 col-lg-6">
                                            <div class="row justify-content-end">
                                                <div class="col-md-6 col-lg-7">

                                                    <nav class="mil-footer-menu mil-mb-60">
                                                        <ul>
                                                            <li class="mil-up mil-active">
                                                                <a href="../index.php">Home</a>
                                                            </li>
                                                            <li class="mil-up">
                                                                <a href="../portfolio/index.html">Portfolio</a>
                                                            </li>
                                                            <li class="mil-up">
                                                                <a href="../services/index.html">Services</a>
                                                            </li>
                                                            <li class="mil-up">
                                                                <a href="../contact/index.html">Contact</a>
                                                            </li>
                                                            <li class="mil-up">
                                                                <a href="/blog/index.php">Blog</a>
                                                            </li>
                                                        </ul>
                                                    </nav>

                                                </div>
                                                <div class="col-md-6 col-lg-5">

                                                    <ul class="mil-menu-list mil-up mil-mb-60">
                                                        <li><a href="../privacy-policy/index.html"
                                                                class="mil-light-soft">Privacy
                                                                Policy</a></li>
                                                        <li><a href="../terms-conditions/index.html"
                                                                class="mil-light-soft">Terms and
                                                                conditions</a></li>
                                                        <li><a href="../cookie-policy/index.html"
                                                                class="mil-light-soft">Cookie Policy</a>
                                                        </li>
                                                        <li><a href="../career/career.html" class="mil-light-soft">Careers</a></li>
                                                    </ul>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- office address carousel -->
                                    <!-- =========================================
     FOOTER BOTTOM INFO
========================================= -->
                                    <div class="footer-bottom-grid">
                                        <!-- LEFT : SOCIAL + COPYRIGHT -->
                                                         <!-- LEFT COLUMN -->
                        <div class="footer-bottom-left">

                            <!-- AI TOOLS -->
                            <div class="ai-section">
                                <h5>Ask AI About DeepshiTech</h5>

                                <div class="ai-footer">
                                    <button type="button" class="ai-card" onclick="openAI('chatgpt')"
                                        aria-label="Ask ChatGPT about our services">
                                        <img src="../img/ai/chatgpt.png" alt="ChatGPT">
                                    </button>

                                    <button type="button" class="ai-card" onclick="openAI('claude')"
                                        aria-label="Ask Claude about our services">
                                        <img src="../img/ai/claude.png" alt="Claude">
                                    </button>

                                    <button type="button" class="ai-card" onclick="openAI('perplexity')"
                                        aria-label="Ask Perplexity about our services">
                                        <img src="../img/ai/Perplexity.png" alt="Perplexity">
                                    </button>

                                    <button type="button" class="ai-card" onclick="openAI('gemini')"
                                        aria-label="Ask Gemini about our services">
                                        <img src="../img/ai/gemini.png" alt="Gemini">
                                    </button>
                                </div>
                            </div>
                                            <ul class="mil-social-icons mil-up footer-socials">
                                                <li>
                                                    <a href="https://twitter.com/deepshitech" target="_blank"
                                                        class="social-icon">
                                                        <i class="fab fa-twitter"></i>
                                                    </a>
                                                </li>

                                                <li>
                                                    <a href="https://www.facebook.com/profile.php?id=100084127384485"
                                                        target="_blank" class="social-icon">
                                                        <i class="fab fa-facebook"></i>
                                                    </a>
                                                </li>

                                                <li>
                                                    <a href="https://www.instagram.com/deepshitech/" target="_blank"
                                                        class="social-icon">
                                                        <i class="fab fa-instagram"></i>
                                                    </a>
                                                </li>

                                                <li>
                                                    <a href="https://www.linkedin.com/company/deepshitech/?viewAsMember=true"
                                                        target="_blank" class="social-icon">
                                                        <i class="fab fa-linkedin"></i>
                                                    </a>
                                                </li>

                                                <li>
                                                    <a href="https://www.youtube.com/channel/UC2zQGOj7kg3ojaA6m7xDsHg"
                                                        target="_blank" class="social-icon">
                                                        <i class="fab fa-youtube"></i>
                                                    </a>
                                                </li>

                                            </ul>

                                            <div class="footer-copyright">
                                                <p>Copyright 2026. All Rights Reserved.</p>

                                                <p>
                                                    Designed by
                                                    <a href="../index.php">
                                                        DeepshiTech Private Limited
                                                    </a>
                                                </p>
                                            </div>

                                        </div>


                                        <!-- RIGHT : OFFICE SLIDER -->
                                        <div class="footer-office-slider" id="footerOfficeSlider">


                                            <!-- Previous -->
                                            <button type="button" class="footer-office-arrow footer-office-prev"
                                                id="footerOfficePrev" aria-label="Previous office">

                                                <i class="fa-solid fa-arrow-left"></i>

                                            </button>


                                            <!-- Office Content -->
                                            <div class="footer-office-content">

                                                <!-- Flag + Location -->
                                                <div class="footer-office-location">

                                                    <h6 class="footer-office-heading">

                                                        <img id="footerOfficeFlag" src="../img/flags/india.png"
                                                            alt="India flag">

                                                        <span id="footerOfficeName">
                                                            India, Mumbai
                                                        </span>

                                                    </h6>

                                                </div>


                                                <!-- Address -->
                                                <p class="footer-office-address" id="footerOfficeAddress">

                                                    Time Square Building, Unit No:2,
                                                    A Wing, 4th Floor,
                                                    Andheri Kurla Road,
                                                    Andheri (E),
                                                    Mumbai, Maharashtra 400059

                                                </p>


                                                <!-- Phone -->
                                                <p class="footer-office-phone">

                                                    Call :
                                                    <a id="footerOfficePhone" href="tel:02269622225">

                                                        022 6962 2225

                                                    </a>

                                                </p>


                                                <!-- Google Map -->
                                                <a href="#" id="footerOfficeMap" class="footer-office-map"
                                                    target="_blank" rel="noopener">

                                                    VIEW ON MAP

                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>

                                                </a>

                                            </div>


                                            <!-- Next -->
                                            <button type="button" class="footer-office-arrow footer-office-next"
                                                id="footerOfficeNext" aria-label="Next office">

                                                <i class="fa-solid fa-arrow-right"></i>

                                            </button>

                                        </div>

                                    </div>
                                    <!-- FOOTER BOTTOM INFO END -->
                                </div>
                            </div>
                        </footer>
                    </div>
                    
                        <!-- footer end -->

                        <!-- hidden elements -->
                        <div class="mil-hidden-elements">
                            <div class="mil-dodecahedron">
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <div class="mil-pentagon">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                            </div>

                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="mil-arrow">
                                <path
                                    d="M 14 5.3417969 C 13.744125 5.3417969 13.487969 5.4412187 13.292969 5.6367188 L 13.207031 5.7226562 C 12.816031 6.1136563 12.816031 6.7467188 13.207031 7.1367188 L 17.070312 11 L 4 11 C 3.448 11 3 11.448 3 12 C 3 12.552 3.448 13 4 13 L 17.070312 13 L 13.207031 16.863281 C 12.816031 17.254281 12.816031 17.887344 13.207031 18.277344 L 13.292969 18.363281 C 13.683969 18.754281 14.317031 18.754281 14.707031 18.363281 L 20.363281 12.707031 C 20.754281 12.316031 20.754281 11.682969 20.363281 11.292969 L 14.707031 5.6367188 C 14.511531 5.4412187 14.255875 5.3417969 14 5.3417969 z" />
                            </svg>

                            <svg width="250" viewBox="0 0 300 1404" fill="none" xmlns="http://www.w3.org/2000/svg"
                                class="mil-lines">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M1 892L1 941H299V892C299 809.71 232.29 743 150 743C67.7096 743 1 809.71 1 892ZM0 942H300V892C300 809.157 232.843 742 150 742C67.1573 742 0 809.157 0 892L0 942Z"
                                    class="mil-move" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M299 146V97L1 97V146C1 228.29 67.7096 295 150 295C232.29 295 299 228.29 299 146ZM300 96L0 96V146C0 228.843 67.1573 296 150 296C232.843 296 300 228.843 300 146V96Z"
                                    class="mil-move" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M299 1H1V1403H299V1ZM0 0V1404H300V0H0Z" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M150 -4.37115e-08L150 1404L149 1404L149 0L150 -4.37115e-08Z" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M150 1324C232.29 1324 299 1257.29 299 1175C299 1092.71 232.29 1026 150 1026C67.7096 1026 1 1092.71 1 1175C1 1257.29 67.7096 1324 150 1324ZM150 1325C232.843 1325 300 1257.84 300 1175C300 1092.16 232.843 1025 150 1025C67.1573 1025 0 1092.16 0 1175C0 1257.84 67.1573 1325 150 1325Z"
                                    class="mil-move" />
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M300 1175H0V1174H300V1175Z"
                                    class="mil-move" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M150 678C232.29 678 299 611.29 299 529C299 446.71 232.29 380 150 380C67.7096 380 1 446.71 1 529C1 611.29 67.7096 678 150 678ZM150 679C232.843 679 300 611.843 300 529C300 446.157 232.843 379 150 379C67.1573 379 0 446.157 0 529C0 611.843 67.1573 679 150 679Z"
                                    class="mil-move" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M299 380H1V678H299V380ZM0 379V679H300V379H0Z" class="mil-move" />
                            </svg>
                        </div>
                    </div>
                    <!-- hidden elements end -->
                    <div id="google_translate_element" class="mil-goog-hidden"></div>
                    <script>
                        function googleTranslateElementInit() {
                            new google.translate.TranslateElement({
                                pageLanguage: 'en',
                                includedLanguages: 'en,hi,es,fr,ar,zh',
                                autoDisplay: false
                            }, 'google_translate_element');
                        }
                    </script>

                        <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async></script>
    <script src="../js/plugins/jquery.min.js"></script>
    <script src="../js/plugins/swup.min.js"></script>
    <script src="../js/plugins/swiper.min.js"></script>
    <script src="../js/plugins/fancybox.min.js"></script>
    <script src="../js/plugins/gsap.min.js"></script>
    <script src="../js/plugins/smooth-scroll.js"></script>
    <script src="../js/plugins/ScrollTrigger.min.js"></script>
    <script src="../js/plugins/ScrollTo.min.js"></script>
    <script src="../js/site-data.js"></script>
    <script src="../js/i18n.js"></script>
    <script src="../js/office-carousel.js"></script>
    <script src="../js/components.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/popup.js"></script>
    <script src="../js/Address.js"></script>
    <script src="../js/contact-form.js"></script>
    <script src="../js/auto-popup.js"></script>
    <script src="../js/ai-tools.js"></script>


</body>

</html>