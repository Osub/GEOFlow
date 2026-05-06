import fs from 'node:fs/promises';

const baseUrl = process.env.GEO_ADMIN_BASE_URL || 'https://geo.gpt88.cc/geo_admin';
const username = process.env.GEO_ADMIN_USERNAME;
const password = process.env.GEO_ADMIN_PASSWORD;
const contentPath = process.env.KNOWLEDGE_CONTENT_PATH || 'operations/gpt88-geo-campaign/knowledge-bases/midstation-acquisition-logic.md';
const checkOnly = process.argv.includes('--check');

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

let createLocation = '';

if (!checkOnly) {
  const createPage = await request(`${baseUrl}/knowledge-bases/create`);
  const createHtml = await createPage.text();
  if (createPage.status !== 200 || /login/i.test(createPage.url)) {
    throw new Error(`Create page unavailable with status ${createPage.status}.`);
  }

  const createToken = csrf(createHtml);
  const content = await fs.readFile(contentPath, 'utf8');
  const createBody = new URLSearchParams({
    _token: createToken,
    name: 'Token 中转站获客逻辑与运营知识库',
    description: '用于 gpt88.cc / GPT88 GEO 推广中转站、生成教程、短视频脚本、社群话术、分销话术与企业服务内容的运营知识库。',
    file_type: 'markdown',
    content,
  });

  const createResponse = await request(`${baseUrl}/knowledge-bases/create`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: createBody.toString(),
  });

  if (![302, 303].includes(createResponse.status)) {
    const text = await createResponse.text();
    throw new Error(`Create failed with status ${createResponse.status}: ${text.slice(0, 500)}`);
  }

  createLocation = createResponse.headers.get('location') || '';
}

const listPage = await request(`${baseUrl}/knowledge-bases`);
const listHtml = await listPage.text();
const found = listHtml.includes('Token 中转站获客逻辑与运营知识库');
const titleIndex = listHtml.indexOf('Token 中转站获客逻辑与运营知识库');
const windowHtml = titleIndex >= 0 ? listHtml.slice(Math.max(0, titleIndex - 1000), titleIndex + 5000) : '';
const detailMatch = windowHtml.match(/knowledge-bases\/(\d+)\/detail/);
const detailId = detailMatch?.[1] || null;
let chunkCount = null;

if (detailId) {
  const detailPage = await request(`${baseUrl}/knowledge-bases/${detailId}/detail`);
  const detailHtml = await detailPage.text();
  const chunkTextMatch = detailHtml.match(/(?:chunk_count|切块数量|Chunk Count|分块数量)[\s\S]{0,300}?([0-9]+)/i);
  const previewRows = [...detailHtml.matchAll(/knowledge_detail\.chunk_index|chunk-preview|<tr>/g)].length;
  chunkCount = chunkTextMatch?.[1] || (previewRows > 0 ? String(previewRows) : null);
}

console.log(JSON.stringify({
  created: !checkOnly,
  foundInList: found,
  location: createLocation,
  listStatus: listPage.status,
  detailId,
  chunkCount,
}, null, 2));
