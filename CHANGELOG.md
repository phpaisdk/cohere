# Changelog

Notable changes to `aisdk/cohere`. Release notes are also published at https://github.com/phpaisdk/cohere/releases.

## Unreleased (v0.10.2)

- Verify that chat usage includes all input tokens rather than billed units in responses and streams.

## v0.10.1 — 2026-10-29

- Successful responses without a message raise `InvalidResponseException`.

## v0.10.0 — 2026-10-29

### What's new

- Unset `maxTokens` and `temperature` are omitted so the provider's own defaults apply.
- Works with the 0.10 SDK, including the new agent, failover and caching APIs in `aisdk/core`.

### Compatibility

- Works with `aisdk/core ^0.10.0` and `aisdk/openai-compatible ^0.10.0` where applicable. Upgrade SDK packages together.

## Earlier releases

See the [GitHub releases](https://github.com/phpaisdk/cohere/releases) for versions before 0.10.0.
