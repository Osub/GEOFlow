# gpt88.cc GEO 运营推广执行包

## 当前站点观察

- 中转站产品站 / 目标站：gpt88.cc。
- 运营文章发布系统：geo.gpt88.cc。
- gpt88.cc 主站定位：AI Gateway / 多模型中转 / Token 成本控制 / 自动供应商切换 / 故障转移 / 动态价格优化。
- 主站公开能力：注册登录、API Key、套餐、充值、模型定价、配置文件导出、调用日志、邀请奖励、中转站搭建。
- geo.gpt88.cc 发布系统现状：已经发布 6 篇文章，集中在模型对比、GEO 内容资产和 gpt88.cc 内容运营流程。
- 内容缺口：原有内容更偏教程和工具配置，还需要补足“中转站是什么 / API 中转站怎么用 / OpenAI 兼容网关怎么选”这类上游入口词素材。

## 远程 AI 生成文章任务

- 任务名称：`gpt88.cc 中转站主题 AI 生成文章任务`
- 任务配置：`tasks/gpt88-zhongzhuanzhan-ai-task.json`
- 当前绑定：作者 `GPT88 GEO 编辑部`（ID 2）、标题库 `gpt88.cc 中转站 SEO/GEO 标题库 2026-05-05`（ID 3，167 条标题）、关键词库 `gpt88.cc 中转站 SEO/GEO 关键词库 2026-05-05`（ID 2，248 个关键词）、知识库 `gpt88.cc sub-site 建站参考库 2026-05-05`（ID 1，4 个知识片段）。
- 绑定素材：中文信任型正文提示词、`GPT-5.4`、`AI中转站` 分类。
- 作者池已扩展：`GPT88 GEO 编辑部`、`GPT88 建站研究组`、`GPT88 API 实战组`、`GPT88 增长实验室`、`GPT88 成本优化实验室`、`GPT88 渠道与白标团队`。
- 补充素材：`materials/zhongzhuanzhan-title-import.txt`、`materials/zhongzhuanzhan-keyword-import.txt`、`materials/zhongzhuanzhan-seo-geo-keyword-map.csv`、`materials/zhongzhuanzhan-knowledge-base.md`、`materials/zhongzhuanzhan-theme-pack.md`、`materials/sub-site-build-reference-8864k.md`、`materials/sub-site-build-keyword-import.txt`、`materials/sub-site-build-keyword-import-v2.txt`、`materials/sub-site-build-title-import.txt`、`materials/sub-site-build-title-import-v2.txt`、`materials/sub-site-build-knowledge-base.md`、`materials/sub-site-build-theme-pack.md`、`materials/sub-site-build-faq-scenario-pack.md`。
- 审核策略：生成文章先进入待审核草稿，不直接公开发布。
- SEO/GEO 目标：覆盖定义型、教程型、工具配置型、团队成本型、选型对比型、FAQ 问答型六类搜索意图，标题优先使用可被 AI 搜索直接引用的问题句和清单句。

## 本轮内容矩阵

1. 中转站主题任务：`AI API 中转站是什么？用 gpt88.cc 做多模型统一入口的实用指南`
   - 目标：承接“AI 中转站 / API 中转站 / OpenAI 中转站 / 中转站怎么用”等定义型和选型型搜索需求。
   - 用户：刚开始了解中转站的开发者、运营人员和团队负责人。
   - 素材：`materials/zhongzhuanzhan-theme-pack.md` 已补充关键词池、标题库、FAQ、社媒钩子、评论回复和图片提示词。

2. 新手教程：`gpt88.cc 新手教程：从注册到创建 API Key 的完整上手流程`
   - 目标：承接“gpt88.cc 怎么用 / API Key 怎么创建 / AI 中转站教程”等搜索需求。
   - 用户：第一次接触 gpt88.cc 的个人开发者和运营人员。

3. 开发者教程：`Claude Code、Codex、Gemini 用户如何用 gpt88.cc 统一 AI 编程工作流`
   - 目标：承接 AI 编程工具用户，强调统一 Base URL、模型分层和调用日志。
   - 用户：开发者、小团队、AI 编程工具用户。

4. 团队成本指南：`团队如何用 gpt88.cc 控制 Token 成本：模型选择、套餐与调用日志指南`
   - 目标：承接团队管理和成本优化场景，强调 Key 拆分、任务分层、日志复盘。
   - 用户：团队负责人、运营负责人、自动化负责人。

5. 开发者接口教程：`gpt88.cc 开发者教程：跑通 OpenAI 兼容网关`
   - 目标：围绕 gpt88.cc 自有 `/v1` 接入地址，承接 Codex、Claude Code、Cursor、Continue、Cline/Roo、OpenCode、OpenClaw、Python/Node SDK 等工具配置需求。
   - 用户：已经有 API Key，但不知道如何填 Base URL、模型名和接口路径的开发者。
   - 备注：用户提供的飞书 wiki 当前跳转登录页，尚未读取正文；教程里保留了内部 SOP 补充位。

6. 视频脚本：
   - 60 秒新手上手。
   - 开发者统一接入。
   - 团队成本控制。

7. `sub-site` 建站分支：
   - 目标：承接“AI 中转站建站 / 分站建站 / 白标建站 / 代理招商 / 私有化方案”这类更偏商业化和建站方案的流量。
   - 素材：`materials/sub-site-build-knowledge-base.md`、`materials/sub-site-build-theme-pack.md`、`materials/sub-site-build-faq-scenario-pack.md`。
   - 方向：定义型、方案型、FAQ 型、价格型、代理型、场景型文章。

8. 社媒分发：
   - 小红书/视频号教程文案。
   - 开发者场景短文案。
   - 朋友圈/社群文案。
   - 知乎回答开头。

## 发布建议

- 第 1 天：发布中转站定义文章，先抢占“AI 中转站是什么 / API 中转站怎么用”入口词。
- 第 2 天：发布新手教程，同时用短视频 1 引流。
- 第 3 天：发布开发者教程，在开发者社群和朋友圈分发。
- 第 4 天：发布团队成本指南，作为团队用户转化内容。
- 第 5 天：发布 gpt88.cc / OpenAI 兼容网关教程，承接开发者工具配置流量。
- 第 6-7 天：把五篇文章互相加入相关阅读，并从旧文章内补充内链。
- 第 8 天起：启动 `sub-site` 建站分支，围绕价格、FAQ、代理、白标、私有化持续扩词。

## 内链建议

- 从“AI 模型对比全表”链接到团队成本指南，锚文本：`如何按任务控制 Token 成本`。
- 从“AI 模型对比全表”链接到中转站定义文章，锚文本：`AI API 中转站是什么`。
- 从“AI 产品推广怎么做”链接到新手教程，锚文本：`gpt88.cc 新用户上手教程`。
- 从“gpt88.cc 如何把文章生产、审核与发布串成增长链路”链接到开发者教程，锚文本：`开发者多模型接入流程`。
- 从新手教程链接到中转站定义文章，锚文本：`多模型统一入口的价值`。
- 从开发者教程链接到 gpt88.cc 网关教程，锚文本：`OpenAI-compatible 网关配置模板`。

## 对外 CTA

- 新手文章 CTA：先创建 API Key，用短请求跑通第一次调用。
- 中转站文章 CTA：先理解统一入口价值，再用 gpt88.cc 创建 Key、复制 Base URL、跑通短请求。
- 开发者文章 CTA：把 Claude Code / Codex / Gemini 纳入统一模型工作流。
- 团队文章 CTA：按开发、运营、自动化拆分 API Key，并每周复盘调用日志。

## 合规表达边界

- 不使用“绕过限制、无限免费、官方平替、永不封号、百分百稳定”等不可验证或高风险表达。
- 不暗示可盗用、共享或滥用 API Key。
- 价格、套餐、模型状态、可用线路和服务承诺均以 gpt88.cc 当前页面为准。
- 对外内容统一使用“合规接入、统一入口、配置管理、成本复盘、开发效率”这条主线。

## 发布前确认

对外公开发布属于站点内容变更。当前内容已经准备为待发布素材，确认后可通过 geo.gpt88.cc API 创建文章并发布。
