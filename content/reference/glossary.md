---
title: Glossary
order: 2
---

# Glossary

This glossary defines terms as they are used within CodeQuill. Some terms have broader meanings in other contexts; the definitions here reflect their specific usage in this system.

---

**Anchoring**
The act of recording a cryptographic hash or identifier on a blockchain. Anchoring establishes a tamper-evident, timestamped reference point. In CodeQuill, snapshots, releases, attestations, and preservations are all anchored on-chain. Once anchored, a record cannot be altered or backdated without detection.

---

**Artifact**
A build output -- a compiled binary, a Docker image, an npm package, a tarball, or any other distributable file produced from source code. CodeQuill does not produce or validate artifacts. It records claims about them via [attestations](#attestation).

---

**Attestation**
An on-chain record that a specific party asserted a relationship between a build artifact and a published release. An attestation records who made the claim, what artifact was referenced (by SHA-256 digest), and when the claim was made. It is a recorded assertion, not a proof that the artifact was correctly built from the source. See [Attestations](/concepts/attestations).

---

**Authority**
The wallet address that controls a workspace's on-chain identity. The authority can add or remove workspace members, transfer control to a new wallet, and manage delegations. The authority is the root of trust for a workspace -- if you control the authority wallet, you control the workspace's on-chain operations. Set during wallet onboarding and recorded on the `CodeQuillWorkspaceRegistry` contract.

---

**Claim**
An on-chain evidence record that links a GitHub repository to a workspace authority. A claim establishes who is authorized to speak for a repository within CodeQuill. It does not assert ownership, copyright, or authorship -- it records an association. A repository can only be claimed once. See [Claims](/concepts/claims).

---

**Content Root**
A SHA-256 hash of the concatenated, sorted file content hashes in a snapshot. The content root provides a second integrity check alongside the Merkle root. It allows verification that the set of file contents in a manifest matches the expected state without requiring Merkle tree reconstruction.

---

**Context ID**
A `keccak256` hash of a workspace's internal identifier. The context ID scopes all on-chain operations to a specific workspace. It appears in smart contract calls for claims, snapshots, releases, attestations, and preservations, ensuring that records are associated with the correct organizational boundary.

---

**Delegation**
A time-bound, scoped permission granted by a workspace member to CodeQuill's relayer, allowing the relayer to submit on-chain transactions on the member's behalf. Delegations are authorized via EIP-712 signed messages and can be scoped to specific operations (claim, snapshot, attest, release, preservation) or granted for all operations. Delegations are revocable at any time. This enables gasless operation for workspace members.

---

**Evidence Infrastructure**
The collective term for the systems and records that CodeQuill produces: on-chain anchors, IPFS manifests, Merkle proofs, encrypted preservations, and the cryptographic relationships between them. Evidence infrastructure is designed to be durable (surviving platform changes), inspectable (verifiable by any party), and independent (not reliant on CodeQuill's continued availability).

---

**Governance**
The process by which a release is approved or rejected after being anchored on-chain. Governance is performed by the designated governance authority or, if configured, by a DAO executor address. Governance decisions (accept, reject, revoke) are recorded on-chain. Attestations can only be created against releases that have been accepted through governance.

---

**Merkle Root**
The root hash of a Merkle tree constructed from a snapshot's file entries. Each leaf in the tree is `keccak256(0x00 || path_hash || file_hash)`. The Merkle root is a single hash that represents the entire file tree. It is anchored on-chain and serves as the primary identifier for a snapshot's content. Changing any file, or any file's path, produces a different Merkle root.

---

**Preservation**
An encrypted archive of a repository's full source code, tied to a specific published snapshot. Preservations use the `codequill-envelope:v1` encryption scheme (AES-256-GCM with X25519-wrapped DEK) and are stored on IPFS. The plaintext hash is anchored on-chain. Decryption requires the workspace authority's passkey. Preservations are optional and serve long-term evidence durability use cases. See [Preservations](/concepts/preservations).

---

**Proof**
A cryptographic Merkle proof-of-inclusion demonstrating that a specific file was part of a published snapshot. A proof provides the minimal set of sibling hashes needed to reconstruct the Merkle root from a specific leaf. Proof generation requires workspace authority (to resolve salted path hashes); proof verification is public and can be performed offline by anyone. See [Proofs](/concepts/proofs).

---

**Release**
A named, versioned designation of a specific snapshot as an official version of the software. Releases add intent to evidence: they represent a deliberate decision that a particular source state should be treated as a version. Releases follow a lifecycle (draft, published, accepted/rejected, revoked, superseded) and are subject to governance. Only accepted releases can be attested. See [Releases](/concepts/releases).

---

**Snapshot**
A deterministic cryptographic fingerprint of a repository's source code at a specific git commit. The CLI reads every tracked file, computes per-file hashes, builds a Merkle tree, and produces a manifest. The Merkle root is anchored on-chain. No source code leaves the local machine during snapshot creation. Snapshots are the foundational evidence record in CodeQuill -- releases, attestations, and preservations all reference them. See [Snapshots](/concepts/snapshots).

---

**Snapshot Manifest**
A JSON document (following the `codequill-snapshot:v1` schema) that contains the per-file data composing a snapshot: salted path hashes, content hashes, file sizes, the Merkle root, content root, repository name, and commit hash. Manifests are compressed and stored on IPFS. Anyone can retrieve a manifest by its CID and independently verify the Merkle tree against the on-chain root.

---

**Workspace**
The top-level organizational unit in CodeQuill. A workspace maps to a GitHub organization or user account and defines the boundary for authority, collaboration, and on-chain identity. All CodeQuill operations -- claims, snapshots, releases, attestations, preservations -- occur within a workspace context. See [Workspaces](/concepts/workspaces).

---

**Zero Custody**
A design property of CodeQuill's encryption model. Zero custody means that CodeQuill never possesses the keys needed to decrypt preserved source code. The decryption key is derived from the user's passkey via the WebAuthn PRF extension and never leaves the user's device. There is no admin backdoor, no key escrow, and no recovery mechanism. If the passkey is lost, the preserved data is unrecoverable. See [Encryption Model](/security/encryption-model).
