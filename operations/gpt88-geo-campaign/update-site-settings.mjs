const baseUrl = process.env.GEO_ADMIN_BASE_URL || 'https://geo.gpt88.cc/geo_admin';
const username = process.env.GEO_ADMIN_USERNAME;
const password = process.env.GEO_ADMIN_PASSWORD;
const disableAds = process.argv.includes('--disable-ads');
const enableFallbackScript = process.argv.includes('--fallback-script');

if (!username || !password) {
  throw new Error('Set GEO_ADMIN_USERNAME and GEO_ADMIN_PASSWORD.');
}

const cookieJar = new Map();

function storeCookies(response) {
  const setCookie = response.headers.get('set-cookie');
  if (!setCookie) {
    return;
  }

  for (const part of setCookie.split(/,(?=\s*[^;,=\s]+=[^;,]+)/)) {
    const [pair] = part.split(';');
    const index = pair.indexOf('=');
    if (index > 0) {
      cookieJar.set(pair.slice(0, index).trim(), pair.slice(index + 1).trim());
    }
  }
}

function cookieHeader() {
  return Array.from(cookieJar.entries())
    .map(([key, value]) => `${key}=${value}`)
    .join('; ');
}

async function request(url, options = {}) {
  const response = await fetch(url, {
    redirect: 'manual',
    ...options,
    headers: {
      ...(cookieJar.size ? { Cookie: cookieHeader() } : {}),
      ...(options.headers || {}),
    },
  });
  storeCookies(response);
  return response;
}

function csrf(html) {
  const match = html.match(/name="_token"\s+value="([^"]+)"/);
  if (!match) {
    throw new Error('CSRF token not found.');
  }

  return match[1];
}

function decodeHtml(value) {
  return value
    .replaceAll('&quot;', '"')
    .replaceAll('&#039;', "'")
    .replaceAll('&amp;', '&')
    .replaceAll('&lt;', '<')
    .replaceAll('&gt;', '>');
}

function inputValue(html, name, fallback = '') {
  const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = html.match(new RegExp(`<input[^>]*name="${escapedName}"[^>]*value="([^"]*)"`, 'i'))
    || html.match(new RegExp(`<input[^>]*value="([^"]*)"[^>]*name="${escapedName}"`, 'i'));

  return match ? decodeHtml(match[1]) : fallback;
}

function textareaValue(html, name, fallback = '') {
  const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = html.match(new RegExp(`<textarea[^>]*name="${escapedName}"[^>]*>([\\s\\S]*?)</textarea>`, 'i'));

  return match ? decodeHtml(match[1].trim()) : fallback;
}

function checkboxChecked(html, name) {
  const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = html.match(new RegExp(`<input[^>]*name="${escapedName}"[^>]*>`, 'i'))
    || html.match(new RegExp(`<input[^>]*>[^<]*name="${escapedName}"`, 'i'));

  return Boolean(match && /\bchecked\b/i.test(match[0]));
}

function withArticleCtaFallback(analyticsCode) {
  const start = '<!-- GPT88 GEO article CTA fallback start -->';
  const end = '<!-- GPT88 GEO article CTA fallback end -->';
  const withoutOldFallback = analyticsCode
    .replace(new RegExp(`${start}[\\s\\S]*?${end}`, 'g'), '')
    .trim();

  const fallback = `${start}
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!/^\\/article\\//.test(window.location.pathname) || document.getElementById('articleStickyAd')) {
    return;
  }

  var storageKey = 'articleStickyAdDismissed:gpt88_api_quickstart_cta';
  if (window.localStorage && localStorage.getItem(storageKey) === '1') {
    return;
  }

  var ad = document.createElement('aside');
  ad.id = 'articleStickyAd';
  ad.className = 'article-sticky-ad';
  ad.dataset.adId = 'gpt88_api_quickstart_cta';
  ad.innerHTML = '<div class="article-sticky-ad__inner">' +
    '<button type="button" class="article-sticky-ad__close" id="articleStickyAdClose" aria-label="关闭广告">×</button>' +
    '<div class="article-sticky-ad__content">' +
      '<div class="article-sticky-ad__badge">开发者推荐</div>' +
      '<h3 class="article-sticky-ad__title">把 Claude Code、Cursor、Codex 接到 gpt88.cc</h3>' +
      '<p class="article-sticky-ad__copy">使用 gpt88.cc 的 OpenAI 兼容接口，创建 API Key 后复制 Base URL，即可把常用 AI 编程工具跑起来。</p>' +
    '</div>' +
    '<a href="https://gpt88.cc/" class="article-sticky-ad__button">立即接入</a>' +
  '</div>';

  document.body.appendChild(ad);
  var closeButton = document.getElementById('articleStickyAdClose');
  if (closeButton) {
    closeButton.addEventListener('click', function () {
      if (window.localStorage) {
        localStorage.setItem(storageKey, '1');
      }
      ad.remove();
    });
  }
});
</script>
${end}`;

  return `${withoutOldFallback}${withoutOldFallback ? '\n' : ''}${fallback}`;
}

async function login() {
  const loginPage = await request(`${baseUrl}/login`);
  const loginHtml = await loginPage.text();
  const loginToken = csrf(loginHtml);

  const loginBody = new URLSearchParams({
    _token: loginToken,
    username,
    password,
    remember: '1',
  });

  const loginResponse = await request(`${baseUrl}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: loginBody.toString(),
  });

  if (![302, 303].includes(loginResponse.status)) {
    throw new Error(`Login failed with status ${loginResponse.status}.`);
  }
}

function collectCarouselFields(html, body) {
  for (let index = 0; index < 3; index += 1) {
    const prefix = `home_carousel_slides[${index}]`;
    const imageUrl = inputValue(html, `${prefix}[image_url]`, '');
    const title = inputValue(html, `${prefix}[title]`, '');
    const linkUrl = inputValue(html, `${prefix}[link_url]`, '');

    if (!imageUrl && !title && !linkUrl) {
      continue;
    }

    body.set(`${prefix}[image_url]`, imageUrl);
    body.set(`${prefix}[title]`, title);
    body.set(`${prefix}[link_url]`, linkUrl);
    if (checkboxChecked(html, `${prefix}[enabled]`)) {
      body.set(`${prefix}[enabled]`, '1');
    }
  }
}

async function updateBasicSettings() {
  const settingsPage = await request(`${baseUrl}/site-settings`);
  const settingsHtml = await settingsPage.text();
  const token = csrf(settingsHtml);

  let analyticsCode = textareaValue(settingsHtml, 'analytics_code', '');
  if (enableFallbackScript) {
    analyticsCode = withArticleCtaFallback(analyticsCode);
  }

  const body = new URLSearchParams({
    _token: token,
    site_name: 'GPT88 GEO',
    site_subtitle: 'gpt88.cc 多模型 API 中转与 AI Gateway 运营知识库',
    site_description: 'GPT88 GEO 聚焦 gpt88.cc 的 AI API 中转、OpenAI 兼容接口、Claude Code、Cursor、Codex 工具接入、Token 成本控制、社群分销和企业服务运营。',
    site_keywords: 'gpt88.cc,AI中转站,OpenAI API,Claude API,Claude Code,Cursor,Codex,API Key,Base URL,AI Gateway,Token成本控制,国内AI接口',
    copyright_info: '© 2026 GPT88 GEO. Powered by gpt88.cc.',
    site_logo: inputValue(settingsHtml, 'site_logo', ''),
    site_favicon: inputValue(settingsHtml, 'site_favicon', ''),
    analytics_code: analyticsCode,
    seo_title_template: '{title} - GPT88 GEO',
    seo_description_template: '{description}',
    featured_limit: '6',
    per_page: '12',
    admin_base_path: inputValue(settingsHtml, 'admin_base_path', 'geo_admin'),
  });

  collectCarouselFields(settingsHtml, body);

  const response = await request(`${baseUrl}/site-settings`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
  });

  if (![302, 303].includes(response.status)) {
    const text = await response.text();
    throw new Error(`Basic settings update failed with status ${response.status}: ${text.slice(0, 800)}`);
  }

  return {
    status: response.status,
    location: response.headers.get('location') || '',
  };
}

async function updateArticleDetailAds() {
  const settingsPage = await request(`${baseUrl}/site-settings`);
  const settingsHtml = await settingsPage.text();
  const token = csrf(settingsHtml);

  const ads = [
    {
      id: 'gpt88_api_quickstart_cta',
      name: 'gpt88 API 接入主 CTA',
      badge: '开发者推荐',
      title: '把 Claude Code、Cursor、Codex 接到 gpt88.cc',
      copy: '使用 gpt88.cc 的 OpenAI 兼容接口，创建 API Key 后复制 Base URL，即可把常用 AI 编程工具跑起来。',
      button_text: '立即接入',
      button_url: 'https://gpt88.cc/',
      enabled: !disableAds,
    },
    {
      id: 'gpt88_team_cost_control_cta',
      name: '团队成本控制备用 CTA',
      badge: '团队方案',
      title: '让团队 AI 调用成本可看、可控、可复盘',
      copy: '按开发、运营、自动化拆分 API Key，结合调用日志和套餐管理，降低 Token 成本失控风险。',
      button_text: '查看方案',
      button_url: 'https://gpt88.cc/',
      enabled: false,
    },
    {
      id: 'gpt88_partner_growth_cta',
      name: '分销合作备用 CTA',
      badge: '合作伙伴',
      title: '有 AI 工具社群？用返佣把信任链变成增长',
      copy: '邀请好友注册和充值可获得持续奖励，KOL 与社群主可申请更高合作比例。',
      button_text: '了解合作',
      button_url: 'https://gpt88.cc/',
      enabled: false,
    },
  ];

  const body = new URLSearchParams({ _token: token });
  ads.forEach((ad, index) => {
    for (const [key, value] of Object.entries(ad)) {
      if (key === 'enabled') {
        if (value) {
          body.set(`ads[${index}][enabled]`, '1');
        }
        continue;
      }

      body.set(`ads[${index}][${key}]`, String(value));
    }
  });

  const response = await request(`${baseUrl}/site-settings/article-detail-ads`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
  });

  if (![302, 303].includes(response.status)) {
    const text = await response.text();
    throw new Error(`Article ads update failed with status ${response.status}: ${text.slice(0, 800)}`);
  }

  return {
    status: response.status,
    location: response.headers.get('location') || '',
    enabledAdId: ads.find((ad) => ad.enabled)?.id || null,
    totalAds: ads.length,
  };
}

async function verify() {
  const homeResponse = await fetch('https://geo.gpt88.cc/');
  const homeHtml = await homeResponse.text();
  const articleResponse = await fetch('https://geo.gpt88.cc/article/gpt88-api-key-quickstart');
  const articleHtml = await articleResponse.text();

  return {
    homeStatus: homeResponse.status,
    articleStatus: articleResponse.status,
    siteDescriptionUpdated: homeHtml.includes('GPT88 GEO 聚焦 gpt88.cc'),
    siteKeywordsUpdated: homeHtml.includes('Claude Code') && homeHtml.includes('Token成本控制'),
    adRendered: articleHtml.includes('把 Claude Code、Cursor、Codex 接到 gpt88.cc')
      && articleHtml.includes('立即接入')
      && articleHtml.includes('https://gpt88.cc/'),
  };
}

await login();
const basic = await updateBasicSettings();
const ads = await updateArticleDetailAds();
const verification = await verify();

console.log(JSON.stringify({ basic, ads, verification }, null, 2));
