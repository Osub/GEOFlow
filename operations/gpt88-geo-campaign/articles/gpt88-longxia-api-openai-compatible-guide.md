---
title: "gpt88.cc 开发者教程：跑通 OpenAI 兼容网关"
slug: "gpt88-longxia-api-openai-compatible-guide"
excerpt: "这篇教程面向 gpt88.cc 用户梳理 OpenAI-compatible 网关的完整配置方法：Base URL、API Key、Responses API、Chat Completions、图片生成，以及 Codex、Claude Code、Cursor、Continue、Cline/Roo、OpenCode、OpenClaw 等客户端配置。"
keywords: "gpt88.cc教程,OpenAI兼容接口,AI Gateway,Codex配置,Claude Code配置,Cursor配置,gpt-5.5,gpt-image-2,API中转"
category_id: 3
author_id: 4
---

# gpt88.cc 开发者教程：跑通 OpenAI 兼容网关

对开发者来说，AI 网关最重要的不是“页面上有多少模型”，而是能不能稳定接入真实工具。你可能要在 Codex、Claude Code、Cursor、Continue、Cline、Roo Code、OpenCode 或自己的 Python/Node 项目里调用模型。如果每个工具都重新摸索一遍 Base URL、API Key、模型名和接口格式，学习成本会很高。

这篇教程整理一套适合 gpt88.cc 用户理解的 OpenAI-compatible 网关接入流程。核心思路很简单：先跑通 gpt88.cc 的统一 Base URL 和 API Key，再按客户端类型选择 Responses API、Chat Completions 或图片生成接口。

## 一、先记住三个核心字段

OpenAI 兼容网关的配置可以先压缩成三项：

| 字段 | 应该怎么填 | 说明 |
| --- | --- | --- |
| Base URL | `https://gpt88.cc/v1` | 必须带 `/v1`，不要只填域名 |
| API Key | 你在 gpt88.cc 控制台创建的密钥 | 不要截图、发群或提交到公开仓库 |
| Model | `gpt-5.5`、`gpt-5.4`、`gpt-image-2` | 文本优先用前两个，图片生成用 `gpt-image-2` |

如果只想先验证链路，建议用 `gpt-5.5` 发一条最小请求。不要一上来就跑长文、代码库分析或批量任务。先确认接口、密钥、模型名和余额都正常，再接入真实业务。

## 二、先跑最小测试

推荐优先测试 Responses API。它更适合新版 OpenAI SDK，也更适合作为统一文本调用入口。

```bash
curl https://gpt88.cc/v1/responses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-5.5",
    "input": "只回复 ok",
    "store": false
  }'
```

如果返回正常，再测试稍复杂一点的输入：

```bash
curl https://gpt88.cc/v1/responses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-5.5",
    "instructions": "你是一个简洁的技术助手。",
    "input": "用三句话解释 OpenAI-compatible Gateway。",
    "reasoning": {"effort": "medium"},
    "stream": false,
    "store": false
  }'
```

这里有一个很实用的细节：如果遇到 `Unsupported parameter` 之类的错误，先删除报错里提到的参数。例如有些推理模型或兼容接口不接受 `temperature`，那就不要强行传温度。

## 三、老工具用 Chat Completions

有些工具还没有适配 Responses API，只能使用传统聊天格式。这时可以调用 `/v1/chat/completions`。

```bash
curl https://gpt88.cc/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-5.5",
    "messages": [
      {"role": "system", "content": "你是一个简洁的技术助手。"},
      {"role": "user", "content": "你好，请说明你能做什么。"}
    ],
    "stream": false
  }'
```

新项目优先 Responses，老客户端用 Chat Completions。这个原则能减少很多兼容性排查时间。

## 四、图片生成必须走图片接口

`gpt-image-2` 不应该当普通聊天模型使用。图片生成要走 `/v1/images/generations`。

```bash
curl https://gpt88.cc/v1/images/generations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-image-2",
    "prompt": "一个 AI 网关控制台界面，科技感，干净的产品海报风格",
    "size": "1024x1024"
  }'
```

如果你的客户端没有图片生成入口，可以先用 HTTP 请求验证。等确认模型和计费正常，再接入自己的业务页面。

## 五、Codex Desktop / Codex CLI 配置思路

Codex 类工具通常需要配置模型提供商。核心字段是 provider 名称、Base URL、模型名和密钥。

```toml
model = "gpt-5.5"
model_provider = "gpt88"
model_reasoning_effort = "xhigh"
disable_response_storage = true

[model_providers.gpt88]
name = "GPT88"
base_url = "https://gpt88.cc/v1"
wire_api = "responses"
experimental_bearer_token = "YOUR_API_KEY"
```

如果你的 Codex 版本不识别 `experimental_bearer_token`，就改成环境变量方式。这样也更安全：配置文件只写变量名，真实密钥放在系统环境变量里。

## 六、Claude Code 配置思路

Claude Code 本身是 Anthropic 协议客户端，所以它接第三方网关时更容易遇到兼容性差异。可以先按环境变量方式测试：

```json
{
  "env": {
    "ANTHROPIC_AUTH_TOKEN": "YOUR_API_KEY",
    "ANTHROPIC_BASE_URL": "https://gpt88.cc/v1",
    "ANTHROPIC_MODEL": "gpt-5.5",
    "ANTHROPIC_CUSTOM_MODEL_OPTION": "gpt-5.5",
    "CLAUDE_CODE_DISABLE_NONESSENTIAL_TRAFFIC": "1"
  }
}
```

如果你的版本提示模型或接口不兼容，不要在 Claude Code 上反复硬调。更稳的做法是换到 Codex、Continue、Cline/Roo 或 OpenCode 这类 OpenAI-compatible 客户端先跑通。

## 七、Cursor 配置思路

Cursor 更适合通过界面填写，不建议用户手动修改内部配置文件。你只需要找到模型设置页，填写：

| Cursor 设置项 | 填写内容 |
| --- | --- |
| OpenAI API Key | `YOUR_API_KEY` |
| OpenAI Base URL / Override OpenAI Base URL | `https://gpt88.cc/v1` |
| Model | `gpt-5.5` |

如果你当前 Cursor 版本没有 Base URL 或 Override Base URL 入口，就不要把时间浪费在内部配置文件上。换 Continue、Cline、Roo Code 或 OpenCode 更直接。

## 八、Continue 配置思路

Continue 的配置通常适合写成 YAML。重点是 provider 选 OpenAI，apiBase 指向 gpt88.cc 的 `/v1` 地址。

```yaml
name: GPT88
version: 0.0.1
schema: v1

models:
  - name: GPT88 GPT-5.5
    provider: openai
    model: gpt-5.5
    apiBase: https://gpt88.cc/v1
    apiKey: YOUR_API_KEY
    defaultCompletionOptions:
      temperature: 0
```

如果后续遇到温度参数不支持，就先删掉 `temperature` 再测。兼容网关排查时，越少参数越容易定位问题。

## 九、Cline / Roo Code 配置思路

Cline 和 Roo Code 都适合选择 OpenAI Compatible 模式。界面里通常填这几项：

| 设置项 | 填写内容 |
| --- | --- |
| API Provider | `OpenAI Compatible` |
| Base URL | `https://gpt88.cc/v1` |
| API Key | `YOUR_API_KEY` |
| Model ID | `gpt-5.5` |

如果使用 CLI 或命令行授权，原则也一样：provider 选择 openai-compatible，base 指向 `/v1`，key 使用自己的密钥。

## 十、OpenCode 配置思路

OpenCode 适合在项目根目录或用户配置目录放 `opencode.json`。如果已经有配置，不要覆盖整个文件，只合并 provider 和 model 字段。

```json
{
  "$schema": "https://opencode.ai/config.json",
  "model": "gpt88/gpt-5.5",
  "provider": {
    "gpt88": {
      "npm": "@ai-sdk/openai-compatible",
      "name": "GPT88",
      "options": {
        "baseURL": "https://gpt88.cc/v1",
        "apiKey": "YOUR_API_KEY"
      },
      "models": {
        "gpt-5.5": {
          "name": "GPT-5.5"
        },
        "gpt-5.4": {
          "name": "GPT-5.4"
        }
      }
    }
  }
}
```

这类配置特别适合开发者做模型分层：复杂任务用 `gpt-5.5`，常规任务用 `gpt-5.4`，不要所有场景都默认最高消耗。

## 十一、Python 和 Node SDK 接入

如果你自己写应用，直接使用 OpenAI SDK 即可。核心是把 `base_url` 或 `baseURL` 改成网关地址。

Python 示例：

```python
from openai import OpenAI

client = OpenAI(
    api_key="YOUR_API_KEY",
    base_url="https://gpt88.cc/v1",
)

response = client.responses.create(
    model="gpt-5.5",
    input="只回复 ok",
    store=False,
)

print(response.output_text)
```

Node.js 示例：

```js
import OpenAI from "openai";

const client = new OpenAI({
  apiKey: process.env.API_KEY,
  baseURL: "https://gpt88.cc/v1",
});

const response = await client.responses.create({
  model: "gpt-5.5",
  input: "只回复 ok",
  store: false,
});

console.log(response.output_text);
```

正式项目里，不要把 API Key 写死在代码里。用环境变量、部署平台密钥管理或服务端配置更稳。

## 十二、常见错误排查

| 错误 | 常见原因 | 处理方式 |
| --- | --- | --- |
| 401 / invalid key | API Key 填错、复制不完整、密钥删除 | 重新创建或复制密钥 |
| 余额不足 | 账户没有可用余额 | 充值、购买套餐或联系管理员 |
| 429 / limit | 并发或上游额度限制 | 降低并发，稍后重试 |
| 502 Bad Gateway | 上游网络或账号临时异常 | 重试，频繁出现再反馈 |
| 503 Unavailable | 模型名错误或当前无可用渠道 | 检查模型名，换 `gpt-5.5` 或 `gpt-5.4` |
| Unsupported parameter | 传了不兼容参数 | 删除报错参数，例如 `temperature` |

排查顺序建议固定下来：先看 Base URL 是否带 `/v1`，再看 API Key，再看模型名，最后看余额和参数。

## 十三、上线前检查清单

- Base URL 是否为 `https://gpt88.cc/v1`。
- 请求头是否包含 `Authorization: Bearer YOUR_API_KEY`。
- 文本模型是否使用 `gpt-5.5` 或 `gpt-5.4`。
- 图片模型是否走 `/v1/images/generations`，并使用 `gpt-image-2`。
- 是否移除了不必要的温度、输出长度等兼容性参数。
- 是否避免把 API Key 写进公开仓库、截图、群聊和前端代码。
- 是否先用短请求测通，再进入长文本、代码库或批量任务。

## 十四、和 gpt88.cc 运营推广如何结合

从运营角度看，这类教程最适合承接“我有 API Key 但不知道怎么接工具”的用户。gpt88.cc 的内容矩阵可以把它放在开发者教程层：一边解释 AI Gateway 的统一入口价值，一边给出 Codex、Claude Code、Cursor、Continue、Cline/Roo、OpenCode 等真实工具的配置路径。

推广时不要只说“支持很多模型”。更好的表达是：gpt88.cc 帮开发者把多模型接入、Key 管理、模型选择、配置文件和调用排查整理成一条可复制流程。用户读完教程，就能知道下一步应该填什么、测什么、错了怎么排查。

## 十五、飞书 SOP 补充位

用户提供的飞书 wiki 链接当前需要登录，暂时无法读取正文。等内部 SOP 开放或导出后，建议把飞书内容补到三处：

- 团队统一命名规则：API Key、项目、模型配置如何命名。
- 内部推荐模型表：哪些任务默认 `gpt-5.5`，哪些任务用 `gpt-5.4`，哪些任务用图片模型。
- 客服排障话术：用户遇到 401、余额不足、503、Unsupported parameter 时如何回复。

这样教程会从“技术接入文档”升级成“运营、客服、开发都能复用的上手 SOP”。

## 总结

OpenAI-compatible 网关的上手关键不是记住很多接口，而是先把三件事做对：Base URL 必须带 `/v1`，API Key 必须安全保存，模型名必须和后台一致。文本优先用 Responses API，老客户端用 Chat Completions，图片生成走独立图片接口。

当这套流程跑通后，gpt88.cc 的推广内容就不只是介绍模型，而是在帮助用户完成真实接入。教程越具体，用户从阅读到注册、创建 Key、第一次调用和长期使用的路径就越短。
