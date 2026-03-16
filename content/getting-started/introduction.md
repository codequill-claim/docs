---
title: Introduction
order: 1
---

# Introduction

CodeQuill is memory infrastructure for software. It preserves verifiable, immutable evidence of what source code existed at a given point in time -- and under whose authority.

## Why CodeQuill Exists

The software ecosystem evolved faster than its ability to preserve evidence. Code can now be generated, modified, and assembled at machine speed. Shipping is easier than ever. But answering simple questions later is harder than it should be:

- What source code actually existed at a given moment?
- Who was authorized to publish it?
- Which release was intended?
- What artifacts were claimed to originate from it?

These are not hypothetical concerns. When incidents occur, when audits are performed, when legal questions arise -- the answers depend on evidence that often no longer exists. Logs rotate. CI systems are ephemeral. Platforms change ownership. Organizations restructure.

CodeQuill exists to preserve facts, not narratives.

## What CodeQuill Does

CodeQuill provides a set of **primitives** for recording durable, inspectable evidence about source code:

- **Claims** -- Explicit on-chain records linking a repository to a workspace authority (wallet). Who is allowed to speak for this repository.
- **Snapshots** -- Deterministic cryptographic fingerprints of a repository's source state at a specific commit. Created locally, never uploaded.
- **Releases** -- Named, versioned groupings of snapshots with a full governance lifecycle. Draft, publish, accept, revoke, supersede.
- **Attestations** -- Recorded statements that a build artifact claims lineage from a specific published release.
- **Preservations** -- Encrypted archives of source code tied to published snapshots. Client-side encryption only -- CodeQuill never accesses plaintext.
- **Proofs** -- Cryptographic Merkle proofs that a specific file was included in a preserved source state.

Each primitive is anchored on-chain using Ethereum smart contracts, creating append-only, tamper-evident records that outlive any single platform or organization.

## What CodeQuill Does Not Do

CodeQuill is evidence infrastructure. It is deliberately scoped:

- It does **not** prove how software was built.
- It does **not** guarantee build causality or correctness.
- It does **not** replace CI systems, artifact signing, or reproducible builds.
- It does **not** provide end-to-end supply-chain security.
- It does **not** validate code quality or safety.

CodeQuill preserves facts. What you do with those facts -- in audits, governance decisions, incident response, or compliance -- is up to you.

## Architecture

CodeQuill separates **authority** from **execution**:

- The **web application** ([app.codequill.xyz](https://app.codequill.xyz)) is where you configure trust: workspaces, wallets, collaborators, DAO governance, encryption, and subscription plans. It surfaces evidence and provides public views -- but it does not generate evidence itself.
- The **CLI** (`codequill`) is where evidence is produced. Snapshots, attestations, preservations, and proofs are all created locally -- on a developer's machine or inside CI runners.
- **Smart contracts** on Ethereum provide the on-chain layer: immutable records of claims, snapshots, releases, attestations, and preservations. No admin keys. No pause mechanism. Fully permissionless once deployed.
- **GitHub Actions** automate evidence production in CI pipelines. Snapshots on push, attestations on release approval.

This separation ensures that even if CodeQuill's servers were compromised, the evidence layer remains independently verifiable.

## Getting Started

If you are new to CodeQuill, the recommended path is:

1. Read the [Quickstart](/docs/getting-started/quickstart) to go from zero to a published snapshot.
2. Explore [Concepts](/docs/concepts/workspaces) to understand the building blocks.
3. Refer to the [CLI Reference](/docs/cli-reference/authentication) for complete command documentation.
4. Set up [CI/CD Integration](/docs/ci-cd/overview) for automated evidence production.

If you are evaluating CodeQuill for security or compliance purposes, start with the [Threat Model](/docs/security/threat-model) and [Explicit Non-Guarantees](/docs/security/non-guarantees).
