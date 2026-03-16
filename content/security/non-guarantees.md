---
title: Non-Guarantees
order: 3
---

# Non-Guarantees

CodeQuill makes specific, verifiable claims about source code evidence. It is equally important to be explicit about what it does not claim. This page enumerates five guarantees that CodeQuill does not provide, explains why each falls outside its scope, and describes what auditors can and cannot conclude from CodeQuill evidence.

Honest scoping is not a weakness. A system that is precise about its boundaries is more trustworthy than one that implies broader coverage than it delivers.

## 1. No Proof of Build Causality

A snapshot proves that a specific file tree, identified by its Merkle root, existed at the time of anchoring. An attestation records that a party asserted a relationship between a build artifact and a source release.

Neither of these constitutes proof that the artifact was built from that source.

The gap is fundamental. A build process is a transformation: source code goes in, an artifact comes out. CodeQuill operates at the input (source) and records claims at the output (attestation), but it does not observe, verify, or constrain the transformation itself. A snapshot says "this source existed." An attestation says "someone claims this artifact came from this release." Whether the claim is true depends on the integrity of the build process, which is outside CodeQuill's observation.

To bridge this gap, use CodeQuill in combination with reproducible builds or build provenance systems that can independently verify the transformation.

## 2. No Build Correctness Guarantee

CodeQuill does not verify that builds are correct, complete, or free from tampering. It does not inspect build scripts, validate dependency resolution, check compiler output, or audit build environments.

A build could be non-deterministic, depend on uncontrolled external inputs, or execute in a compromised environment. CodeQuill would record the source state and the attestation claim identically in all cases. The evidence is about what existed and what was asserted, not about whether the build process was faithful.

Build correctness is the domain of build systems, reproducible build frameworks, and build provenance attestation standards (such as SLSA). CodeQuill provides the source reference that these systems can point to, but it does not replace them.

## 3. No Replacement for Reproducible Builds

Reproducible builds prove build determinism: given the same source, the same build environment, and the same build instructions, the same artifact is produced. This is a property of the build process.

CodeQuill proves source state: this file tree, with this Merkle root, existed at this time. This is a property of the source code.

These are complementary, not substitutes. A reproducible build without a verified source reference proves that the build is deterministic but does not prove what source it was deterministic over. A CodeQuill snapshot without a reproducible build proves what source existed but does not prove that any given artifact was faithfully derived from it.

The strongest evidence combines both: a CodeQuill snapshot that anchors the source state, a reproducible build that proves determinism, and a CodeQuill attestation that links the artifact to the release. Together, they form a verifiable chain from source to artifact.

## 4. No End-to-End Supply-Chain Security

Software supply-chain security encompasses source integrity, build integrity, distribution integrity, and runtime integrity. CodeQuill addresses one layer: source integrity. It anchors evidence about what source code existed and who claimed authority over it.

A complete supply-chain security posture requires additional layers:

- **Artifact signing** -- Cryptographic signatures on build outputs (e.g., Sigstore, GPG).
- **Software Bills of Materials (SBOMs)** -- Inventories of components and dependencies.
- **Build provenance** -- Attestations about the build environment and process (e.g., SLSA provenance).
- **Runtime verification** -- Admission controllers, integrity monitoring, and policy enforcement at deployment time.

CodeQuill is designed to compose with these layers. Attestations can reference upstream dependencies, creating a supply-chain graph. Releases can be gated by governance. But CodeQuill alone does not constitute supply-chain security. It is the source layer in a multi-layer architecture.

## 5. No Code Quality or Safety Validation

CodeQuill does not analyze, audit, or assess source code. It does not scan for vulnerabilities, check for license compliance, evaluate test coverage, or measure code quality.

A snapshot of source code that contains a critical vulnerability is recorded with the same fidelity as a snapshot of pristine, well-tested code. CodeQuill preserves facts about existence and authorship. The quality, safety, and fitness of the code are determined by other tools and processes: static analysis, security audits, code review, and testing.

## Auditor Summary

When evaluating CodeQuill evidence, auditors should distinguish between what the evidence demonstrates and what it does not.

### What Auditors CAN Conclude

- **A file tree existed at a specific time.** A published snapshot, verified against its on-chain Merkle root, demonstrates that a set of files with specific content hashes existed at the time of anchoring.

- **A party claimed association with a repository.** An on-chain claim record demonstrates that a specific wallet, associated with a specific workspace, established an association with a specific repository.

- **A source state was designated as a version.** A published release demonstrates that an authority selected a specific snapshot as a named version.

- **Governance approval was recorded.** An accepted release demonstrates that the designated governance authority (or DAO executor) approved the release.

- **A party asserted artifact lineage.** An attestation demonstrates that a specific party claimed a specific artifact was built from a specific release.

- **An encrypted archive was stored at a specific time.** A preservation demonstrates that an encrypted archive with a known plaintext hash was created and stored, bound to a specific snapshot.

### What Auditors CANNOT Conclude

- **That an artifact was actually built from the attested source.** An attestation is a claim, not a proof of build causality.

- **That the build process was correct or untampered.** CodeQuill does not observe or validate builds.

- **That the code is free of vulnerabilities, backdoors, or defects.** CodeQuill does not perform code analysis.

- **That files NOT included in a proof are absent from the snapshot.** A Merkle proof demonstrates inclusion, not exclusion. The absence of a proof for a file does not mean the file was absent.

- **That the attesting party had direct knowledge of the build process.** An attestation records an assertion. Whether the asserter personally performed, observed, or merely trusts the build is not captured.

- **That the claimed association with a repository implies authorship.** A claim is an on-chain record of association, not a verified assertion of who wrote the code.

## Why This Matters

These non-guarantees are not disclaimers added as an afterthought. They are structural boundaries that define where CodeQuill's evidence is strong and where other tools must contribute.

A system that claims to solve everything invites misplaced trust. A system that is precise about its scope -- "we prove source state, not build correctness; we record claims, not proofs of causality" -- gives users and auditors the information they need to build appropriate trust models.

CodeQuill's guarantees are meaningful precisely because they are bounded. A snapshot's claim that "this file tree existed at this time" is strong because it does not overreach into claims about what was done with that file tree. An attestation's claim that "this party asserted this relationship" is useful because it does not pretend to be proof that the relationship is truthful.

Explicit scoping enables composition. When each layer in a security architecture is honest about its boundaries, the layers can be combined with confidence. CodeQuill provides the source layer. Reproducible builds provide the build layer. Signing frameworks provide the distribution layer. Together, they form a chain of evidence that is stronger than any single tool could offer alone.
