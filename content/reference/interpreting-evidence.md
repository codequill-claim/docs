---
title: Interpreting Evidence
order: 1
---

# Interpreting Evidence

CodeQuill produces several types of evidence records. Each type has specific properties that determine what conclusions can and cannot be drawn from it. This page is a reference for developers, auditors, and legal reviewers who need to evaluate CodeQuill evidence with precision.

The distinction between what an evidence record demonstrates and what it does not demonstrate is critical. Overinterpreting evidence leads to misplaced trust. Underinterpreting it discards value. The guidance below aims for accuracy.

## Interpreting a Snapshot

A snapshot is a Merkle root anchored on-chain, representing the cryptographic state of a repository's file tree at a specific git commit.

### You MAY Conclude

- A file tree with this Merkle root existed at the time of anchoring. The on-chain timestamp and the Merkle root together establish temporal existence.
- The snapshot was created by the wallet address recorded in the on-chain transaction, acting within a specific workspace context.
- The manifest (retrievable from IPFS via its CID) contains the per-file hashes that compose the Merkle tree. If the Merkle tree recomputed from the manifest matches the on-chain root, the manifest is authentic.

### You May NOT Conclude

- How the code was produced. A snapshot does not record authorship, development process, or provenance of individual files.
- That the snapshot represents the complete state of the project. The snapshot captures tracked files at a specific commit. Untracked files, build artifacts, and files excluded by `.codequillignore` are not included.
- That the code is correct, safe, or fit for any purpose. A snapshot is a record of existence, not an assessment of quality.
- That the source code still exists in its original location. The snapshot is independent of the repository's continued availability.

## Interpreting a Proof

A proof is a Merkle proof-of-inclusion demonstrating that a specific file was part of a specific snapshot.

### You MAY Conclude

- A file with a specific content hash was included in the snapshot identified by the Merkle root. This is a cryptographic certainty, assuming the hash functions are unbroken.
- If the proof includes a disclosed path (generated with `--disclose`), the file was located at that path in the repository at the time of the snapshot.
- The proof is independently verifiable. No access to CodeQuill, the original repository, or the snapshot author is required to verify it. The verification is a deterministic computation over the proof data and the on-chain Merkle root.

### You May NOT Conclude

- Anything about files not included in the proof. A Merkle proof demonstrates inclusion, not exclusion. The absence of a proof for a specific file does not demonstrate that the file was absent from the snapshot.
- That the file content is unchanged since the snapshot. The proof establishes what the content was at snapshot time, not what it is now.
- Who authored the file. The proof demonstrates inclusion in a snapshot created by a specific workspace, not authorship of the file's content.

## Interpreting an Attestation

An attestation is an on-chain record that a party asserted a relationship between a build artifact and a published release.

### You MAY Conclude

- A specific party (identified by wallet address) asserted that an artifact (identified by SHA-256 digest) was built from a specific release at a specific time.
- The attestation was created against an accepted release, meaning governance approval was in place at the time of attestation.
- The attestation manifest (on IPFS) contains additional metadata: artifact name, version, Package URL, artifact kind, and upstream dependency references.

### You May NOT Conclude

- That the assertion is truthful. An attestation records a claim, not a proof. The attesting party may have made an honest mistake, relied on a compromised build system, or acted in bad faith.
- That the build process was correct. CodeQuill does not observe, validate, or constrain how artifacts are built. See [Non-Guarantees](/security/non-guarantees).
- That the attesting party personally performed the build. The attestation records who submitted the claim, not who executed the build process.
- That the artifact is safe to use. An attestation establishes lineage claims, not security properties.

## Interpreting a Preservation

A preservation is an encrypted archive of source code stored on IPFS, tied to a specific published snapshot.

### You MAY Conclude

- An encrypted archive with a specific plaintext hash (SHA-256) was stored at a specific time, bound to a specific snapshot's Merkle root.
- If decrypted, the archive's SHA-256 hash can be compared against the on-chain plaintext hash to verify integrity. A match proves the decrypted content is identical to what was originally encrypted.
- The preservation was created by a specific wallet address within a specific workspace context.

### You May NOT Conclude

- What the archive contains without decrypting it. The encryption is zero-custody; only the workspace authority with the appropriate passkey can decrypt.
- That the archive will be decryptable indefinitely. If the workspace's passkey is lost, the preservation cannot be decrypted. There is no recovery mechanism.
- That the preserved source is different from or identical to the current state of the repository. The preservation captures the state at the snapshot's commit, which may or may not match the current state.

## Interpreting a Release

A release is a named, versioned designation of a specific snapshot as an official version, subject to governance.

### You MAY Conclude

- An authority selected a specific snapshot (identified by Merkle root) as a named version at a specific time.
- The release was anchored on-chain with its manifest stored on IPFS. The manifest contains the release name, associated repository, snapshot data, and governance configuration.
- If the release status is ACCEPTED, the designated governance authority (or DAO executor) approved the release. This approval is recorded on-chain.

### You MAY Additionally Conclude (for Accepted Releases)

- Governance approval was granted. The `accept()` transaction on the smart contract was executed by the designated governance authority.
- Attestations may exist against this release. Only accepted releases can be attested, so the existence of attestations implies governance approval preceded them.

### You May NOT Conclude

- That the release represents the only or most current version. Multiple releases can exist for the same repository, and newer snapshots may exist that have not been released.
- That governance approval implies code review. Governance records an on-chain decision. Whether that decision was informed by code review, testing, or audit is outside the on-chain record.
- That rejection or revocation implies the source is invalid. A rejected or revoked release reflects a governance decision, not a judgment about the source code itself.

## Verification Process

To verify CodeQuill evidence end-to-end:

1. **Retrieve the on-chain record.** Query the appropriate smart contract (`CodeQuillSnapshotRegistry`, `CodeQuillReleaseRegistry`, `CodeQuillAttestationRegistry`, or `CodeQuillPreservationRegistry`) for the record in question. Confirm the Merkle root, CID, author, and timestamp.

2. **Fetch the IPFS manifest.** Using the CID from the on-chain record, retrieve the manifest from IPFS. Decompress and parse the JSON.

3. **Verify cryptographic proofs.** For snapshots, recompute the Merkle tree from the manifest's file entries and confirm it matches the on-chain Merkle root. For proofs, fold the Merkle proof and confirm the computed root matches. For preservations, decrypt the archive and compare its SHA-256 hash against the on-chain plaintext hash.

4. **Cross-reference timestamps.** The on-chain block timestamp establishes when the record was anchored. Compare this against git commit timestamps, CI logs, or other external records as appropriate for your audit context.

5. **Evaluate claims in context.** Attestations are claims, not proofs. Assess them in the context of the attesting party's identity, the governance status of the release, and any corroborating evidence (reproducible build verification, signing, SBOM analysis).

Each step in this process is independently executable. No access to CodeQuill servers is required. The evidence is designed to be self-contained and verifiable by any party with access to the blockchain and IPFS.
