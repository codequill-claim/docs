---
title: Overview
order: 1
---

# CI/CD Integration

CodeQuill provides two GitHub Actions that automate the evidence pipeline within your existing CI/CD workflows. Together, they implement a two-phase release pipeline: one action handles snapshotting and publishing, the other handles attestation in response to governance decisions.

## Two-Phase Release Pipeline

The pipeline is split into two distinct phases, each triggered by a different GitHub event.

### Phase 1: Snapshot & Publish

Triggered on push to `main` (or any branch you configure). This phase uses the **Snapshot Action** (`ophelios-studio/codequill-action-publish@v1`) to:

1. Run `codequill snapshot` -- compute a deterministic Merkle root of the repository at the current commit.
2. Run `codequill publish` -- upload the manifest to IPFS and anchor the Merkle root on-chain.
3. Run `codequill wait` -- block until the on-chain transaction is confirmed.
4. Optionally run `codequill preserve` -- create an encrypted archive of the source code tied to the published snapshot.

Phase 1 completes without human intervention. Every push to `main` produces a published snapshot that can be referenced by future releases.

### Phase 2: Attestation

Triggered by GitHub Issues created by the **codequill-authorship[bot]**. This phase uses the **Attestation Action** (`codequill-claim/actions-attest@v1`) to respond to governance events originating from the CodeQuill web application.

When a release is created or approved in the CodeQuill webapp, the bot opens a GitHub Issue on the repository with the label `codequill:release`. The issue body contains a JSON payload describing the event. The Attestation Action parses this payload, verifies its authenticity, and takes action based on the event type.

## Event Types

The bot creates issues with two possible event types:

### `release_anchored`

Informational. Indicates that a release has been created and anchored on-chain. The Attestation Action logs the event, closes the issue with a success comment, and returns early. No attestation is performed.

This event is useful for audit trails and notifications. You can add downstream steps (Slack alerts, dashboard updates) that trigger on this event type.

### `release_approved`

Actionable. Indicates that governance has accepted the release -- either a workspace member approved it directly, or a DAO vote concluded in favor. The Attestation Action validates the build artifact, runs `codequill attest`, and records the attestation on-chain.

This is the event that bridges governance to deployment. Once attestation succeeds, subsequent CI steps can deploy with confidence that the release was explicitly approved.

## Security Model

The communication channel between the CodeQuill platform and your CI pipeline relies on GitHub Issues, which are a public surface. Three verification layers protect against spoofing:

1. **Bot identity check** -- The action verifies that the issue was created by `codequill-authorship[bot]` (exact login match). Issues from any other actor are ignored.

2. **Label verification** -- The action verifies that the issue carries the `codequill:release` label. This prevents unrelated bot activity from triggering the workflow.

3. **HMAC-SHA256 payload verification** -- If an `hmac_secret` is configured, the action verifies the issue body's signature against the shared secret. The issue body contains a JSON object with `payload` and `signature` fields. The signature is an HMAC-SHA256 digest of the payload, computed with the shared secret. This is strongly recommended for production use.

## Pipeline Topologies

### Standard Pipeline

The most common configuration for teams without DAO governance:

```
Push code to main
  -> Phase 1: snapshot + publish
  -> Create release in CodeQuill webapp
  -> Workspace member approves release
  -> Phase 2: CI attests the build artifact
  -> Deploy to production
```

### DAO-Governed Pipeline

For repositories governed by a DAO:

```
Push code to main
  -> Phase 1: snapshot + publish
  -> Create DAO-only release in CodeQuill webapp
  -> DAO votes to approve the release
  -> Phase 2: CI attests the build artifact and deploys
```

The CI pipeline is identical in both cases. The difference is who approves the release -- a workspace member or a DAO vote. The Attestation Action does not distinguish between approval sources; it responds to `release_approved` regardless of origin.

## Environment Variables

Both actions require the following environment configuration:

| Variable | Source | Description |
|---|---|---|
| `CODEQUILL_TOKEN` | CodeQuill webapp | Repo-scoped bearer token generated in the repository settings of the CodeQuill web application. Store as a GitHub Actions secret. |
| `CODEQUILL_GITHUB_ID` | GitHub context | The numeric repository ID. Use `${{ github.repository_id }}` in workflow YAML. |

The token authenticates the CLI against the CodeQuill API. It is scoped to a single repository and can be revoked at any time from the webapp.
