import fs from 'node:fs/promises';
import path from 'node:path';

const ROOT = new URL('.', import.meta.url);
const manifestPath = new URL('./article-manifest.json', ROOT);
const shouldPublish = process.argv.includes('--publish');
const tokenFile = process.env.GEO_API_TOKEN_FILE || '/private/tmp/gpt88_geo_login.json';
const apiBase = process.env.GEO_API_BASE || 'https://geo.gpt88.cc/api/v1';

function parseFrontmatter(markdown) {
  if (!markdown.startsWith('---\n')) {
    return [{}, markdown.trim()];
  }

  const end = markdown.indexOf('\n---\n', 4);
  if (end === -1) {
    return [{}, markdown.trim()];
  }

  const raw = markdown.slice(4, end);
  const body = markdown.slice(end + 5).trim();
  const meta = {};

  for (const line of raw.split('\n')) {
    const match = line.match(/^([a-zA-Z0-9_]+):\s*(.*)$/);
    if (!match) {
      continue;
    }

    let value = match[2].trim();
    if (
      (value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }

    meta[match[1]] = value;
  }

  return [meta, body];
}

async function loadToken() {
  if (process.env.GEO_API_TOKEN) {
    return process.env.GEO_API_TOKEN;
  }

  const loginJson = JSON.parse(await fs.readFile(tokenFile, 'utf8'));
  const token = loginJson?.data?.token;
  if (!token) {
    throw new Error(`No token found in ${tokenFile}. Set GEO_API_TOKEN instead.`);
  }

  return token;
}

async function request(pathname, payload, token) {
  const response = await fetch(`${apiBase}${pathname}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Idempotency-Key': `gpt88-campaign-${payload.slug}`,
    },
    body: JSON.stringify(payload),
  });

  const json = await response.json().catch(() => ({}));
  if (!response.ok || json.success === false) {
    throw new Error(`${response.status} ${JSON.stringify(json)}`);
  }

  return json.data;
}

const manifest = JSON.parse(await fs.readFile(manifestPath, 'utf8'));
const token = shouldPublish ? await loadToken() : null;
const results = [];

for (const item of manifest.articles) {
  const articlePath = new URL(item.file, ROOT);
  const markdown = await fs.readFile(articlePath, 'utf8');
  const [meta, content] = parseFrontmatter(markdown);

  const payload = {
    ...manifest.default_payload,
    title: item.title,
    slug: item.slug,
    content,
    excerpt: item.excerpt,
    keywords: item.keywords,
    category_id: Number(meta.category_id || manifest.category_id),
    author_id: Number(meta.author_id || manifest.author_id),
  };

  if (!shouldPublish) {
    results.push({
      mode: 'dry-run',
      title: payload.title,
      slug: payload.slug,
      chars: payload.content.length,
      status: payload.status,
      review_status: payload.review_status,
    });
    continue;
  }

  const created = await request('/articles', payload, token);
  results.push({
    mode: 'published',
    id: created.id,
    title: created.title,
    slug: created.slug,
    url: `${manifest.site}/article/${created.slug}`,
  });
}

console.log(JSON.stringify(results, null, 2));
