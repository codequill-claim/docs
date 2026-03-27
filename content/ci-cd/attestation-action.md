---
title: Attestation Action
description: "The Attestation Action handles release events from GitHub Issues, verifies bot identity and HMAC signatures, and runs codequill attest."
order: 3
---

# Attestation Action

The Attestation Action handles release events triggered by GitHub Issues. It is published as `codequill-claim/actions-attest@v1` and runs on Node 20. It requires the `GITHUB_TOKEN` environment variable for issue management (commenting, closing).

## Purpose

This action enables a **two-phase conformity pipeline** for software releases. When you anchor or approve a release in CodeQuill, the platform creates a GitHub Issue on your repository. This action listens for those issues, verifies their authenticity, and provides outputs so your workflow can react to each phase of the release lifecycle.

The two phases are:

1. **Release Anchored** — The release has been submitted to the blockchain. Use this event to build your project and deploy to a **staging** environment. This gives you the opportunity to validate the release before governance accepts it.

2. **Release Approved** — Governance has accepted the release. This is when you deploy to **production** and run `codequill attest` to create a verifiable, on-chain link between your build artifacts and the approved source release.

Attestation only runs during the approval phase. The anchoring phase is informational -- it signals that a release exists and is pending governance, giving your CI pipeline a head start on building and validating.

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

Use these outputs in subsequent workflow steps to conditionally run build, deploy, or attestation logic based on the release phase.

## Execution Flow

### 1. Issue Verification

When the triggering GitHub event is `issues`, the action performs verification checks:

1. **Bot identity** — Verifies that the issue was created by `codequill-authorship[bot]` (exact login match). If the issue author is any other user or bot, the action exits silently.

2. **Label verification** — Verifies that the issue carries the `codequill:release` label.

3. **Payload parsing** — Parses the issue body as JSON. The body contains an object with two fields: `payload` (the event data) and `signature` (the HMAC-SHA256 digest).

4. **HMAC verification** — If `hmac_secret` is provided, the action computes the HMAC-SHA256 of the `payload` field using the shared secret and compares it against the `signature` field. If verification fails, the action exits with an error.

### 2. Set Outputs

The action sets two outputs from the parsed payload:

- `event_type` — either `release_anchored` or `release_approved`.
- `release_id` — the unique identifier of the release.

These outputs are available to subsequent workflow steps for conditional logic. This is how you differentiate between the two phases in your pipeline.

### 3. Handle `release_anchored`

If the event type is `release_anchored`, the action:

1. Logs the event details.
2. Comments on the issue with a success message.
3. Closes the issue.
4. Returns early. **No attestation is performed.**

This is the staging phase. The action itself does not build or deploy anything -- it simply sets the `event_type` output so your downstream workflow steps can react. Use this event to build your project, run tests, and deploy to a staging environment for validation.

### 4. Handle `release_approved`

If the event type is `release_approved`, the action:

1. Validates that `build_path` is provided and points to an existing file or directory.
2. Validates that `release_id` is available (from the issue body or the input).
3. Installs the `codequill` CLI.
4. Runs `codequill attest <build_path> <release_id> --no-confirm --json --no-wait`.
5. Parses the JSON output to extract `tx_hash`.
6. Runs `codequill wait <tx_hash>` to block until on-chain confirmation.

This is the production phase. Attestation creates an on-chain record linking your build artifact to the governance-approved source release. Only run production deployments on this event.

### 5. Issue Lifecycle

- **On success** — The action comments on the issue with a success message and closes it.
- **On failure** — The action comments on the issue with the error details but does **not** close it. The issue remains open for investigation.

## Workflow Example

The recommended workflow implements the two-phase conformity pipeline. Your CI reacts differently depending on whether the release was just anchored (pending governance) or approved (governance accepted):

```yaml
name: CodeQuill Release Pipeline

on:
  issues:
    types: [labeled]

permissions:
  issues: write

jobs:
  release-pipeline:
    if: github.event.issue.user.login == 'codequill-authorship[bot]' && github.event.label.name == 'codequill:release'
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      # Step 1: Verify the issue and detect the event type
      - name: CodeQuill Attestation
        id: codequill
        uses: codequill-claim/actions-attest@v1
        env:
          GITHUB_TOKEN: ${{ github.token }}
        with:
          token: ${{ secrets.CODEQUILL_TOKEN }}
          hmac_secret: ${{ secrets.CODEQUILL_HMAC_SECRET }}
          github_id: ${{ github.repository_id }}
          build_path: "./dist"

      # Step 2: Build and deploy to STAGING when the release is anchored
      - name: Build and deploy to staging
        if: steps.codequill.outputs.event_type == 'release_anchored'
        run: |
          npm ci
          npm run build
          npm run deploy:staging

      # Step 3: Deploy to PRODUCTION when governance approves
      - name: Deploy to production
        if: steps.codequill.outputs.event_type == 'release_approved'
        run: |
          npm ci
          npm run build
          npm run deploy:production
```

The `permissions: issues: write` grant is required for the action to comment on and close issues via the `GITHUB_TOKEN`. Without it, the action will process the event correctly but fail when attempting to update the issue, producing a `Resource not accessible by integration` error.

The `if` condition on the job ensures the workflow only runs for issues created by the CodeQuill bot with the correct label. Without this condition, every issue event on the repository would trigger the workflow.

### How It Works

1. **On `release_anchored`** — The attestation step verifies the issue, sets the outputs, closes the issue, and returns without performing attestation. The "Build and deploy to staging" step runs, giving you the chance to validate the release in a staging environment before governance decides.

2. **On `release_approved`** — The attestation step verifies the issue, runs `codequill attest` to create the on-chain attestation, and closes the issue. The "Deploy to production" step runs, deploying the governance-approved release to production.

This two-phase pattern ensures that:

- You can **validate builds before governance approval** by deploying to staging on `release_anchored`.
- **Production deployments only happen after explicit governance approval** on `release_approved`.
- **Attestation only runs for approved releases**, creating a verifiable on-chain link between your build artifacts and the accepted source state.
