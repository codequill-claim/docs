---
title: Attestations
order: 5
---

# Attestations

An attestation is a recorded statement that a build artifact claims lineage from a specific published release. It creates a verifiable link between a compiled output and the source code it was supposedly built from.

## Why Attestations Exist

When you download a binary, a Docker image, or an npm package, you trust that it was built from the source code you reviewed. But trust is not verification. Without attestations, there is no durable record connecting the artifact you run to the source it claims to originate from.

CodeQuill attestations record three things:

1. **Who** made the claim (which workspace member).
2. **What** artifact was referenced (its SHA-256 digest).
3. **When** the claim was made (on-chain timestamp).

An attestation does not prove the artifact was correctly built from the source. It records the claim that it was. The difference matters -- see [Non-Guarantees](/security/non-guarantees).

## What an Attestation Contains

When you attest an artifact, the following is recorded on the `CodeQuillAttestationRegistry` smart contract:

- **Release ID** -- The release this artifact claims to originate from.
- **Artifact Digest** -- The SHA-256 hash of the build artifact.
- **Attestation CID** -- IPFS CID of the full attestation manifest.
- **Author** -- The wallet address that created the attestation.
- **Timestamp** -- When the attestation was anchored.

The attestation manifest (stored on IPFS) additionally contains:

- **Subject** -- The artifact's name, version, and Package URL (purl).
- **Artifact Kind** -- The type of artifact (file, Docker image, npm package, binary).
- **Upstream Dependencies** -- Other CodeQuill attestations referenced as upstream (for supply-chain graph building).

## Prerequisites

Attestations have strict prerequisites:

1. The release must be **accepted** (governance status = ACCEPTED). You cannot attest against a pending, rejected, or revoked release.
2. The author must be a workspace member with the appropriate delegation scope (SCOPE_ATTEST).

## Using the CLI

### Create an Attestation

```bash
codequill attest <build-artifact> <release-id>
```

**Arguments:**

| Argument | Required | Description |
|---|---|---|
| `<build-artifact>` | Yes | Path to the build artifact file |
| `<release-id>` | Yes | The CodeQuill release ID to attest against |

**Options:**

| Option | Type | Default | Description |
|---|---|---|---|
| `--subject-name <name>` | string | auto | Artifact name (e.g. `my-app`) |
| `--subject-version <ver>` | string | auto | Artifact version (e.g. `1.0.0`) |
| `--upstream <purl>` | string | -- | Upstream dependency purl (repeatable) |
| `--no-confirm` | boolean | false | Skip confirmation prompt |
| `--confirmations <n>` | integer | 1 | On-chain confirmations to wait for |
| `--timeout <ms>` | integer | 300000 | Timeout for confirmation |
| `--no-wait` | boolean | false | Submit and return immediately |
| `--json` | boolean | false | Output in JSON format |

### Example: Attest a Build Artifact

```bash
codequill attest dist/my-app.tar.gz rel_abc123 \
  --subject-name my-app \
  --subject-version 1.0.0
```

### Example: Attest with Upstream Dependencies

The `--upstream` flag links your attestation to upstream CodeQuill attestations by Package URL (purl). This builds the supply-chain dependency graph:

```bash
codequill attest dist/my-app.tar.gz rel_abc123 \
  --upstream "pkg:npm/lodash@4.17.21" \
  --upstream "pkg:npm/express@4.18.2"
```

### Example: CI-Friendly Attestation

In CI pipelines, use `--no-confirm` and `--json`:

```bash
codequill attest dist/my-app.tar.gz rel_abc123 --no-confirm --json
```

When running non-interactively (no TTY), the CLI auto-derives the subject name from the artifact filename and the subject version from the commit hash.

## The Supply-Chain Graph

Attestations support upstream and downstream dependency references. When you specify `--upstream` purls, the CLI resolves them against existing CodeQuill attestations, creating a verifiable dependency graph.

This graph enables questions like:

- "What source releases contributed to this artifact?"
- "Are there any revoked attestations in the dependency chain?"
- "Who attested each dependency?"

## Attestation Revocation

Attestations can be revoked if they are found to be incorrect or compromised. Revocation is recorded on-chain and marks the attestation as no longer valid.

## Auditor Context

For auditors and legal reviewers: an attestation is a **claim**, not a **proof**. It records that a specific party asserted a relationship between an artifact and a source release. Whether that assertion is truthful depends on the integrity of the build process, which is outside CodeQuill's scope. See [Interpreting Evidence](/reference/interpreting-evidence) for guidance.
