import fs from 'node:fs/promises';

const baseUrl = process.env.GEO_ADMIN_BASE_URL || 'https://geo.gpt88.cc/geo_admin';
const username = process.env.GEO_ADMIN_USERNAME;
const password = process.env.GEO_ADMIN_PASSWORD;
const keywordFile = process.env.KEYWORD_FILE || 'operations/gpt88-geo-campaign/materials/midstation-acquisition-keywords.txt';
const titleFile = process.env.TITLE_FILE || 'operations/gpt88-geo-campaign/materials/midstation-acquisition-titles.txt';

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

function findLibraryId(listHtml, name, segment) {
  const index = listHtml.indexOf(name);
  if (index < 0) {
    return null;
  }

  const windowHtml = listHtml.slice(Math.max(0, index - 1500), index + 5000);
  const detailMatch = windowHtml.match(new RegExp(`${segment}/(\\d+)/detail`));
  return detailMatch?.[1] || null;
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

async function ensureLibrary({ segment, name, description }) {
  const listResponse = await request(`${baseUrl}/${segment}`);
  const listHtml = await listResponse.text();
  const existingId = findLibraryId(listHtml, name, segment);
  if (existingId) {
    return { id: existingId, created: false };
  }

  const createPage = await request(`${baseUrl}/${segment}/create`);
  const createHtml = await createPage.text();
  const token = csrf(createHtml);

  const body = new URLSearchParams({
    _token: token,
    name,
    description,
  });

  const createResponse = await request(`${baseUrl}/${segment}/create`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
  });

  if (![302, 303].includes(createResponse.status)) {
    const text = await createResponse.text();
    throw new Error(`Create ${segment} failed with status ${createResponse.status}: ${text.slice(0, 500)}`);
  }

  const afterList = await request(`${baseUrl}/${segment}`);
  const afterHtml = await afterList.text();
  const id = findLibraryId(afterHtml, name, segment);
  if (!id) {
    throw new Error(`Created ${segment}, but could not find detail id.`);
  }

  return { id, created: true };
}

async function importIntoLibrary({ segment, id, field, text }) {
  const detailPage = await request(`${baseUrl}/${segment}/${id}/detail`);
  const detailHtml = await detailPage.text();
  const token = csrf(detailHtml);

  const body = new URLSearchParams({
    _token: token,
    [field]: text,
  });

  const importResponse = await request(`${baseUrl}/${segment}/${id}/import`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
  });

  if (![302, 303].includes(importResponse.status)) {
    const responseText = await importResponse.text();
    throw new Error(`Import ${segment} failed with status ${importResponse.status}: ${responseText.slice(0, 500)}`);
  }

  const verifyPage = await request(`${baseUrl}/${segment}/${id}/detail`);
  const verifyHtml = await verifyPage.text();
  return {
    status: importResponse.status,
    location: importResponse.headers.get('location') || '',
    sampleFound: text.split(/\r?\n/).filter(Boolean).slice(0, 3).every((line) => {
      const sample = field === 'titles_text' ? line.split('|')[0] : line;
      return verifyHtml.includes(sample);
    }),
  };
}

await login();

const keywordText = await fs.readFile(keywordFile, 'utf8');
const titleText = await fs.readFile(titleFile, 'utf8');

const keywordLibrary = await ensureLibrary({
  segment: 'keyword-libraries',
  name: '中转站获客运营关键词库',
  description: '围绕搜索获客、工具场景获客、内容获客、社群获客、分销返佣、企业服务等中转站运营场景整理的长尾关键词库。',
});

const titleLibrary = await ensureLibrary({
  segment: 'title-libraries',
  name: '中转站获客推广标题库',
  description: '围绕 gpt88.cc 中转站推广、教程、短视频、社群、分销和企业服务场景整理的可直接用于生成文章的标题库。',
});

const keywordImport = await importIntoLibrary({
  segment: 'keyword-libraries',
  id: keywordLibrary.id,
  field: 'keywords_text',
  text: keywordText,
});

const titleImport = await importIntoLibrary({
  segment: 'title-libraries',
  id: titleLibrary.id,
  field: 'titles_text',
  text: titleText,
});

console.log(JSON.stringify({
  keywordLibrary,
  titleLibrary,
  keywordLines: keywordText.split(/\r?\n/).filter(Boolean).length,
  titleLines: titleText.split(/\r?\n/).filter(Boolean).length,
  keywordImport,
  titleImport,
}, null, 2));
