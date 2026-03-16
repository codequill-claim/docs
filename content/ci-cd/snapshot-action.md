---
title: Snapshot Action
order: 2
---

# Snapshot Action

The Snapshot Action automates the `codequill snapshot` and `codequill publish` commands within a GitHub Actions workflow. It is published as `ophelios-studio/codequill-action-publish@v1` and runs on Node 20.

## Purpose

This action eliminates manual CLI invocations for the snapshot-and-publish phase of the pipeline. On every push to your target branch, it computes a deterministic Merkle root of the repository, publishes the manifest to IPFS, anchors the snapshot on-chain, and optionally creates an encrypted preservation archive.

## Inputs

| Input | Required | Default | Description |
|---|---|---|---|
| `token` | Yes | — | CodeQuill repo-scoped bearer token. |
| `github_id` | Yes | — | GitHub repository numeric ID. Use `github.repository_id`. |
| `api_base_url` | No | `""` | Override CodeQuill API base URL. |
| `cli_version` | No | `""` | Specific npm version for the `codequill` CLI. |
| `working_directory` | No | `"."` | Working directory for commands. |
| `extra_args` | No | `""` | Extra arguments appended to commands. |
| `preserve` | No | `"false"` | Whether to preserve code after publish. |

## Outputs

No outputs are defined. The action succeeds or fails based on the outcome of the CLI commands.

## Execution Flow

The action performs the following steps in sequence:

### 1. Install CLI

Installs the `codequill` CLI globally via npm. If `cli_version` is specified, it installs that exact version (`npm i -g codequill@<version>`). Otherwise, it installs the latest release (`npm i -g codequill`).

### 2. Set Environment Variables

Exports the following environment variables for the CLI:

- `CODEQUILL_TOKEN` — from the `token` input.
- `CODEQUILL_GITHUB_ID` — from the `github_id` input.
- `CODEQUILL_API_BASE_URL` — from the `api_base_url` input, if provided.

### 3. Snapshot

Runs `codequill snapshot` in the configured working directory. This computes the Merkle root of the repository at the current commit and writes the manifest to `.codequill/snapshots/`.

### 4. Publish

Runs `codequill publish --no-confirm --json --no-wait`. The `--json` flag causes the CLI to emit structured output, which the action parses to extract `tx_hash` and `snapshot_id`.

### 5. Wait

Runs `codequill wait <tx_hash>` to block until the on-chain transaction is confirmed. This ensures the snapshot is fully anchored before the action completes.

### 6. Preserve (Optional)

If `preserve` is set to `"true"`:

1. Runs `codequill preserve <snapshot_id> --no-confirm --json --no-wait` to create an encrypted archive of the source code.
2. Parses the JSON output to extract the preservation transaction hash.
3. Runs `codequill wait <preserve_tx_hash>` to block until the preservation transaction is confirmed.

## Workflow Example

A complete, copy-pasteable workflow that runs on every push to `main`:

```yaml
name: CodeQuill Snapshot & Publish

on:
  push:
    branches:
      - main

permissions:
  contents: read

jobs:
  snapshot:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Snapshot and publish
        uses: ophelios-studio/codequill-action-publish@v1
        with:
          token: ${{ secrets.CODEQUILL_TOKEN }}
          github_id: ${{ github.repository_id }}
          preserve: "true"
```

### Without Preservation

If you do not need encrypted source archives, omit the `preserve` input (it defaults to `"false"`):

```yaml
      - name: Snapshot and publish
        uses: ophelios-studio/codequill-action-publish@v1
        with:
          token: ${{ secrets.CODEQUILL_TOKEN }}
          github_id: ${{ github.repository_id }}
```

### With Custom CLI Version

To pin a specific CLI version for reproducibility:

```yaml
      - name: Snapshot and publish
        uses: ophelios-studio/codequill-action-publish@v1
        with:
          token: ${{ secrets.CODEQUILL_TOKEN }}
          github_id: ${{ github.repository_id }}
          cli_version: "0.8.2"
```
