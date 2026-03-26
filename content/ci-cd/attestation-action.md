---
title: Attestation Action
description: "The Attestation Action handles release events from GitHub Issues, verifies bot identity and HMAC signatures, and runs codequill attest."
order: 3
---

# Attestation Action

The Attestation Action handles attestation for release events triggered by GitHub Issues. It is published as `codequill-claim/actions-attest@v1` and runs on Node 20. It requires the `GITHUB_TOKEN` environment variable for issue management (commenting, closing).

## Purpose

This action is the second phase of the CI/CD pipeline. It listens for GitHub Issues created by the CodeQuill bot, verifies their authenticity, and -- when a release is approved -- runs `codequill attest` to record that a build artifact claims lineage from a specific published release.

## Inputs

| Input | Required | Default | Description |
|---|---|---|---|
| `token` | Yes | — | CodeQuill repo-scoped bearer token. |
| `github_id` | Yes | — | GitHub repository numeric ID. |
| `hmac_secret` | No | `""` | Shared secret for HMAC-SHA256 verification. Strongly recommended. |
| `build_path` | No | `""` | Path to build artifact or directory to attest. |
| `release_id` | No | `""` | Release ID. Extracted from the issue body if not provided. |
| `event_type` | No | `""` | Override event type (`release_anchored` or `release_approved`). |
| `api_base_url` | No | `""` | Override API base URL. |
| `cli_version` | No | `""` | Specific npm version for the `codequill` CLI. |
| `working_directory` | No | `"."` | Working directory. |
| `extra_args` | No | `""` | Extra arguments. |

## Outputs

| Output | Description |
|---|---|
| `event_type` | Detected event type (`release_anchored` or `release_approved`). |
| `release_id` | Extracted release ID. |

## Execution Flow

### 1. Issue Verification

When the triggering GitHub event is `issues`, the action performs three verification checks:

1. **Bot identity** — Verifies that the issue was created by `codequill-authorship[bot]` (exact login match). If the issue author is any other user or bot, the action exits.

2. **Label verification** — Verifies that the issue carries the `codequill:release` label.

3. **Payload parsing** — Parses the issue body as JSON. The body contains an object with two fields: `payload` (the event data) and `signature` (the HMAC-SHA256 digest).

4. **HMAC verification** — If `hmac_secret` is provided, the action computes the HMAC-SHA256 of the `payload` field using the shared secret and compares it against the `signature` field. If verification fails, the action exits with an error.

### 2. Set Outputs

The action sets two outputs from the parsed payload:

- `event_type` — either `release_anchored` or `release_approved`.
- `release_id` — the unique identifier of the release.

These outputs are available to subsequent workflow steps for conditional logic.

### 3. Handle `release_anchored`

If the event type is `release_anchored`, the action:

1. Logs the event details.
2. Comments on the issue with a success message.
3. Closes the issue.
4. Returns early. No attestation is performed.

This event is informational -- it confirms that a release was created and anchored on-chain. Downstream steps can read the `event_type` output to trigger notifications or other non-attestation workflows.

### 4. Handle `release_approved`

If the event type is `release_approved`, the action:

1. Validates that `build_path` is provided and points to an existing file or directory.
2. Validates that `release_id` is available (from the issue body or the input).
3. Installs the `codequill` CLI (same logic as the Snapshot Action).
4. Runs `codequill attest <build_path> <release_id> --no-confirm --json --no-wait`.
5. Parses the JSON output to extract `tx_hash`.
6. Runs `codequill wait <tx_hash>` to block until confirmation.

### 5. Issue Lifecycle

- **On success** — The action comments on the issue with a success message and closes it.
- **On failure** — The action comments on the issue with the error details but does **not** close it. The issue remains open for investigation.

## Workflow Example

A complete workflow that responds to CodeQuill bot issues:

```yaml
name: CodeQuill Attestation

on:
  issues:
    types: [labeled]

jobs:
  handle_release:
    if: github.event.issue.user.login == 'codequill-authorship[bot]' && github.event.label.name == 'codequill:release'
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: CodeQuill Attestation
        id: codequill # Required to access outputs
        uses: codequill-claim/actions-attest@v1
        env:
          GITHUB_TOKEN: ${{ github.token }} # Required to automatically close issues (optional)
        with:
          token: ${{ secrets.CODEQUILL_TOKEN }}
          hmac_secret: ${{ secrets.CODEQUILL_HMAC_SECRET }}
          github_id: ${{ github.repository_id }}
          build_path: "./dist/output.tar.gz"

      - name: Build and Deploy
        if: steps.codequill.outputs.event_type == 'release_anchored'
        run: |
          npm install
          npm run build
          # ... deploy your app ...
```

The `if` condition on the job is critical. It ensures the workflow only runs for issues created by the CodeQuill bot with the correct label. Without this condition, every issue event on the repository would trigger the workflow.

## Advanced Example: Attestation with Deployment

This workflow demonstrates how to combine attestation with conditional deployment based on the `event_type` output. The attestation step handles both event types internally, and deployment only proceeds when the release is anchored:

```yaml
name: CodeQuill Attest and Deploy

on:
  issues:
    types: [labeled]

jobs:
  attest-and-deploy:
    if: github.event.issue.user.login == 'codequill-authorship[bot]' && github.event.label.name == 'codequill:release'
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: CodeQuill Attestation
        id: codequill # Required to access outputs
        uses: codequill-claim/actions-attest@v1
        env:
          GITHUB_TOKEN: ${{ github.token }}
        with:
          token: ${{ secrets.CODEQUILL_TOKEN }}
          hmac_secret: ${{ secrets.CODEQUILL_HMAC_SECRET }}
          github_id: ${{ github.repository_id }}
          build_path: "./dist/output.tar.gz"

      - name: Build and Deploy
        if: steps.codequill.outputs.event_type == 'release_anchored'
        run: |
          npm install
          npm run build
          # ... deploy your app ...

      - name: Deploy to production
        if: steps.codequill.outputs.event_type == 'release_approved'
        run: |
          echo "Deploying release ${{ steps.codequill.outputs.release_id }}"
          # Replace with your deployment commands
          ./scripts/deploy.sh --artifact ./dist/output.tar.gz
```

In this workflow:

1. The **attestation step** handles both event types internally. For `release_anchored`, it closes the issue and sets the outputs without performing attestation. For `release_approved`, it attests the build artifact.
2. The **build step** uses a conditional (`if: steps.codequill.outputs.event_type == 'release_anchored'`) to run only on anchored events, allowing you to validate your build early.
3. The **deploy step** uses a conditional (`if: steps.codequill.outputs.event_type == 'release_approved'`) to run only when the release was approved by governance.

This pattern ensures that your CI pipeline can react differently to each event type while restricting production deployment to explicitly approved releases.
