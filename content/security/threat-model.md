---
title: Threat Model
order: 1
---

# Threat Model

CodeQuill is designed to produce durable evidence about source code. To be useful, that evidence must remain meaningful even when surrounding systems fail, change, or are deliberately subverted. This page describes the assumptions CodeQuill operates under, what it preserves in the face of those assumptions, and what falls outside its scope.

## Hostile Assumptions

CodeQuill assumes that the environments in which software is built and distributed are not inherently trustworthy. Specifically:

**CI systems can be compromised.** Build pipelines execute arbitrary code, pull dependencies from external sources, and run in environments controlled by third parties. A compromised CI system can produce tampered artifacts, inject malicious code, or falsify build logs. CodeQuill does not assume CI output is honest.

**Logs and registries can be altered or lost.** Platform logs, package registry metadata, and deployment records are controlled by their operators. They can be edited, deleted, or made unavailable through policy changes, infrastructure failures, or deliberate action. Evidence that depends solely on these systems is fragile.

**Platforms and organizations change over time.** GitHub may alter its API, change its terms of service, or cease to exist. Organizations restructure, lose access to accounts, or migrate between providers. Evidence tied exclusively to a platform's internal state does not survive these transitions.

**Build pipelines are trusted by default.** Most development workflows implicitly trust the pipeline: if CI says the build passed and the artifact was published, the artifact is assumed to be legitimate. This default trust is convenient but creates a gap -- there is no independent record of what source code the artifact was supposedly built from.

## What CodeQuill Preserves

Under these assumptions, CodeQuill provides:

**Durable, inspectable records about source code.** Snapshots capture the cryptographic state of a repository at a specific commit. The Merkle root, file hashes, and manifest are anchored on-chain and stored on IPFS. These records persist regardless of what happens to the original repository, the hosting platform, or the CodeQuill servers.

**Authority and release intent records.** Claims, releases, and governance decisions are recorded on-chain. They establish who claimed authority over a repository, who designated a specific source state as a version, and whether governance approved it. These records are independently retrievable from the blockchain.

**Verifiable anchoring independent of any single system.** On-chain records can be verified by anyone with access to the blockchain. IPFS manifests can be retrieved from any IPFS gateway. Merkle proofs can be validated offline. No single system -- including CodeQuill itself -- must remain available for verification to succeed.

## What CodeQuill Does Not Solve

CodeQuill's scope is deliberately narrow. It provides the source-centric evidence layer and does not extend into adjacent domains:

**Build correctness.** CodeQuill does not verify that a build process produces correct output, that dependencies are resolved faithfully, or that compilation is deterministic. A snapshot proves what source existed, not what was done with it.

**CI integrity.** CodeQuill does not monitor, audit, or validate CI pipelines. If a CI system is compromised, CodeQuill cannot detect or prevent the compromise. It can only provide an independent record of what source code existed at the time, which a forensic investigation could use as a reference point.

**Code quality.** CodeQuill does not analyze source code for bugs, vulnerabilities, or style violations. It preserves facts about existence and authorship, not assessments of merit.

**Platform security.** CodeQuill does not protect GitHub accounts, secure wallet private keys, or harden deployment infrastructure. It operates alongside these systems, not in place of them.

For a complete enumeration of what CodeQuill does not guarantee, see [Non-Guarantees](/docs/security/non-guarantees).

## Composability

CodeQuill is designed to be one layer in a larger security architecture, not a standalone solution. It provides the source-centric reference layer that other tools can point to:

**Reproducible builds** prove that a build process is deterministic -- that given the same source, the same artifact is produced. CodeQuill proves what the source was. Together, they answer both "what was built from?" and "is the build process faithful?"

**Signing frameworks** (such as Sigstore) attest that an artifact was produced by a specific identity at a specific time. CodeQuill attestations link that artifact back to a specific source release. Together, they connect the signed artifact to a verified source state.

**Policy tools** (such as OPA, Kyverno, or admission controllers) enforce rules about what software can run. CodeQuill provides the evidence records that policies can evaluate: was this artifact attested? Was the release accepted by governance? Does the source snapshot exist?

CodeQuill does not compete with these tools. It provides the input-layer evidence they need to reference.

## Trust Boundary

CodeQuill's architecture separates authority configuration from evidence production:

- **Authority is configured in the web application.** Workspace settings, wallet connections, collaborator management, governance configuration, and encryption key registration happen through [app.codequill.xyz](https://app.codequill.xyz). This is the administrative surface.

- **Evidence is produced locally.** The CLI runs on your machine (or in CI). It reads your source code, computes hashes, builds Merkle trees, encrypts archives, and submits transactions. Source code does not pass through CodeQuill servers.

This separation has a specific security property: **even if CodeQuill servers are compromised, the on-chain records remain independently verifiable.** An attacker who gains access to the CodeQuill web application cannot alter records that have already been anchored on the blockchain. They cannot forge Merkle roots, backdate timestamps, or tamper with IPFS manifests that have already been pinned. The worst case is that future operations are disrupted -- past evidence remains intact and verifiable by anyone.

The encryption model reinforces this boundary. CodeQuill never possesses the private key needed to decrypt preservations. The key is derived from the user's passkey via the WebAuthn PRF extension and never leaves the device. Even full server compromise does not expose preserved source code. See [Encryption Model](/docs/security/encryption-model) for details.
