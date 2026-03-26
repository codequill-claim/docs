---
title: FAQ
description: "Frequently asked questions about CodeQuill: what problem it solves, how it compares to Sigstore, whether it guarantees build security, and more."
order: 3
---

# FAQ

## What problem does CodeQuill solve?

CodeQuill creates durable, verifiable evidence about source code. It answers questions that existing tools leave unaddressed: "What source code existed at this moment?", "Who claimed authority over this repository?", "Was this artifact attested against a governed release?"

Source code is mutable. Repositories are deleted, commits are amended, platforms change their terms or shut down. CodeQuill produces cryptographic records -- anchored on a blockchain, stored on IPFS -- that survive these events. The evidence is independently verifiable by anyone, without requiring CodeQuill's continued availability.

This is useful for open-source maintainers who need to prove authorship, organizations with audit obligations, DAOs that govern software releases, and anyone who needs a durable record of what source code existed and who spoke for it.

## Is CodeQuill a single point of trust?

No. CodeQuill's architecture is designed so that no single system -- including CodeQuill itself -- must remain trustworthy for past evidence to remain valid.

**On-chain records are independent.** Snapshot Merkle roots, release anchors, attestation records, and preservation hashes are stored on the blockchain. They can be read by anyone with access to the chain, without interacting with CodeQuill.

**IPFS manifests are retrievable.** Snapshot manifests, release manifests, and attestation manifests are stored on IPFS. They can be fetched from any IPFS gateway, not only through CodeQuill's infrastructure.

**Proofs are verifiable offline.** A Merkle proof-of-inclusion can be verified with nothing more than the proof file and the on-chain Merkle root. No network request to CodeQuill is required.

**Encryption is zero-custody.** Preserved source code is encrypted with keys that CodeQuill never possesses. Even full server compromise does not expose preserved data.

If CodeQuill were to cease operating, all previously anchored evidence would remain accessible and verifiable through the blockchain and IPFS directly.

## How does CodeQuill compare to Sigstore?

Sigstore and CodeQuill operate at different layers and are complementary.

**Sigstore** signs artifacts at the output layer. It answers: "Who signed this artifact, and when?" Sigstore provides keyless signing, a transparency log (Rekor), and certificate-based identity binding. It is focused on the artifact -- the binary, container image, or package that is distributed.

**CodeQuill** preserves evidence at the input layer. It answers: "What source code existed, who claimed authority over it, and what release was this artifact attested against?" CodeQuill is focused on the source -- the code that the artifact was supposedly built from.

Together, they create a chain: CodeQuill anchors the source state, a build process produces an artifact, Sigstore signs the artifact, and a CodeQuill attestation links the signed artifact back to the governed source release. Neither tool alone provides this full chain.

## Does CodeQuill guarantee my builds are secure?

No. CodeQuill does not observe, validate, or constrain build processes. It does not verify that a build is correct, deterministic, or free from tampering.

A CodeQuill snapshot proves what source code existed. A CodeQuill attestation records that someone claimed an artifact was built from a specific release. Whether that claim is truthful depends on the integrity of the build process, which is outside CodeQuill's scope.

For build security, consider reproducible build frameworks, build provenance standards (SLSA), and CI pipeline hardening. CodeQuill provides the source reference that these tools can point to. See [Non-Guarantees](/security/non-guarantees) for a full discussion.

## Do I need reproducible builds to use CodeQuill?

No. CodeQuill operates independently of how your software is built.

Reproducible builds and CodeQuill are complementary. Reproducible builds prove that a build process is deterministic -- given the same inputs, the same output is produced. CodeQuill proves what the inputs were -- which source code existed at the time of the snapshot.

You can use CodeQuill without reproducible builds to establish a durable record of source state, govern releases, and attest artifacts. You can use reproducible builds without CodeQuill to verify build determinism. Using both together provides the strongest evidence chain: verified source state, verified build determinism, and a recorded link between them.

## Is source code stored on the blockchain?

No. Only cryptographic hashes and identifiers are stored on-chain.

Specifically, the blockchain stores: Merkle roots (representing file tree state), manifest CIDs (pointers to IPFS-stored manifests), commit hashes, wallet addresses, timestamps, and governance decisions. No file contents, file paths, or source code are written to the blockchain.

Snapshot manifests -- which contain per-file hashes and salted path hashes, but not file contents -- are stored on IPFS. The actual source code remains on your local machine or in your repository. If you create a [preservation](/concepts/preservations), an encrypted copy of the source archive is stored on IPFS, but it is encrypted with keys that only the workspace authority possesses.

## Is preservation mandatory?

No. Preservation is an optional feature. Snapshots, releases, attestations, and proofs all function independently of preservation.

A snapshot captures the cryptographic state of your source code without storing the source itself. This is sufficient for most evidence needs: proving what existed, governing releases, and attesting artifacts.

Preservation adds long-term source survival. It encrypts the full source archive and stores it on IPFS, tied to a specific snapshot. This is useful for organizations with archival obligations, disaster recovery requirements, or a need to ensure source availability decades from now. But it is not required for any other CodeQuill operation.

## Why is the web app separate from the CLI?

The separation reflects a deliberate architectural boundary between authority management and evidence production.

**The web application** ([app.codequill.xyz](https://app.codequill.xyz)) handles authority: workspace configuration, wallet connections, collaborator management, governance settings, encryption key registration, release creation, and governance decisions. These are administrative functions that require an interactive interface and wallet interactions.

**The CLI** handles evidence: computing file hashes, building Merkle trees, creating snapshots, publishing to IPFS and the blockchain, generating proofs, encrypting preservations, and creating attestations. These are computational functions that run against your local source code.

This separation has a security property. The CLI produces evidence locally and submits it to the blockchain. Even if the web application were compromised, previously anchored evidence would remain valid and verifiable. An attacker who controls the web application cannot forge Merkle roots, alter on-chain records, or decrypt preservations. The worst case is disruption of future operations, not corruption of past evidence.

## Can I use CodeQuill in CI?

Yes. CodeQuill provides a GitHub Action (`codequill-claim/actions-snapshot`) for automated snapshot and attestation workflows.

A typical CI integration:

1. On push to `main`, the GitHub Action creates a snapshot and publishes it on-chain.
2. A release is created in the web application (or triggered by a workflow).
3. When the release is accepted by governance, a GitHub Issue is created by the CodeQuill bot, which can trigger a CI workflow.
4. The CI workflow builds the artifact and runs `codequill attest` to create an on-chain attestation linking the artifact to the release.

The CLI supports non-interactive operation (`--no-confirm`, `--json`) for CI environments. Authentication in CI uses session tokens obtained through the `codequill auth` flow. See [CI/CD Integration](/ci-cd/overview) for detailed setup instructions.

## Who is CodeQuill for?

CodeQuill is designed for anyone who needs durable, verifiable evidence about source code:

**Developers** who want a tamper-evident record of what they built and when. Snapshots provide proof of prior art, and attestations create a verifiable link between source and artifact.

**Open-source maintainers** who need to establish authorship and protect against disputes. Claims create platform-independent records of repository association. Releases with governance provide a transparent approval process.

**DAOs and decentralized organizations** that govern software releases through on-chain voting. CodeQuill's governance model integrates with DAO executors (Aragon, Governor contracts, multisigs), enabling release approval through collective decision-making.

**Organizations with compliance or audit obligations** that need to demonstrate source integrity over time. Snapshots, releases, and preservations create an evidence trail that auditors can independently verify without relying on internal systems that may change.

**Security-conscious teams** building software supply-chain transparency. CodeQuill provides the source layer that complements artifact signing, build provenance, and runtime policy enforcement.
