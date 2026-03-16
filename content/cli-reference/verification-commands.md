---
title: Verification Commands
order: 3
---

# Verification Commands

These commands create and verify cryptographic evidence artifacts: attestations linking builds to releases, Merkle proofs of file inclusion, proof verification, and encrypted source preservation.

---

## codequill attest

Create an attestation that links a build artifact to a published release. An attestation is a signed, on-chain record declaring that a specific artifact was produced from a specific release, forming a verifiable lineage from source code to distributed binary.

### Syntax

```bash
codequill attest <build> <releaseId> [options]
```

### Arguments

| Argument | Required | Description |
|---|---|---|
| `build` | yes | Path to the build artifact (e.g. a binary, archive, or container image tarball) |
| `releaseId` | yes | The release ID to associate the artifact with |

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--subject-name <name>` | string | _(none)_ | Human-readable name for the artifact subject |
| `--subject-version <version>` | string | _(none)_ | Version string for the artifact subject |
| `--upstream <purl>` | string | _(none)_ | Package URL (purl) of an upstream dependency. **Repeatable** -- pass multiple times to declare multiple upstreams. |
| `--confirmations <n>` | integer | `1` | Number of on-chain confirmations to wait for |
| `--timeout <ms>` | integer | `300000` | Maximum time in milliseconds to wait for confirmation |
| `--no-wait` | boolean | `false` | Submit the transaction and return immediately without waiting for confirmation |
| `--no-confirm` | boolean | `false` | Skip the interactive confirmation prompt |
| `--json` | boolean | `false` | Output the result in machine-readable JSON |

### Example

```bash
codequill attest ./dist/myapp-linux-amd64 rel_abc123 \
  --subject-name myapp \
  --subject-version 1.2.0
```

```
Hashing artifact: ./dist/myapp-linux-amd64
Subject: myapp@1.2.0
Release: rel_abc123

Proceed? (y/N) y

Transaction submitted: 0xaaa111...
Waiting for confirmation...

Attestation created successfully.
  Attestation ID: att_xyz789
  Artifact hash: sha256:e3b0c44298fc...
  Explorer: https://sepolia.etherscan.io/tx/0xaaa111...
```

#### Attest with upstream dependencies in CI

```bash
codequill attest ./dist/myapp-linux-amd64 rel_abc123 \
  --subject-name myapp \
  --subject-version 1.2.0 \
  --upstream "pkg:npm/%40codequill/sdk@0.5.0" \
  --upstream "pkg:golang/github.com/ethereum/go-ethereum@1.13.0" \
  --no-confirm --json
```

### Notes

- The build artifact is hashed locally. The artifact itself is **not** uploaded to CodeQuill or any remote storage.
- The `--upstream` flag is repeatable. Each invocation adds one Package URL to the attestation's dependency list, creating a supply-chain lineage graph.
- Attestations are anchored on-chain and reference the release's snapshot Merkle root, forming an unbroken chain from source to artifact.
- Use `--no-confirm` and `--json` for non-interactive CI/CD workflows.

---

## codequill prove

Generate a Merkle proof-of-inclusion for a specific file within a published snapshot. This proves that a file was part of the snapshot's source tree without revealing the rest of the tree. Requires passkey approval to derive the necessary cryptographic material.

### Syntax

```bash
codequill prove <file> <snapshotId> [options]
```

### Arguments

| Argument | Required | Description |
|---|---|---|
| `file` | yes | Path to the file to prove inclusion for (relative to the repository root) |
| `snapshotId` | yes | The snapshot ID that the file should be proven against |

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--disclose` | boolean | `false` | Include the plaintext file path in the proof manifest. When `false`, only the hashed path is included. |
| `--out <file>` | string | `.codequill/proofs/proof-<hash>.json` | Output file path for the proof manifest |

### Example

```bash
codequill prove src/index.ts snap_abc123
```

```
Generating proof for: src/index.ts
Snapshot: snap_abc123

Passkey approval required...
[Browser prompt for passkey verification]

Proof generated.
  Output: .codequill/proofs/proof-7f3a2b.json
  Leaf hash: 0xabc...
  Merkle root: 0x789abc...def0
```

#### Generate a proof with the file path disclosed

```bash
codequill prove src/index.ts snap_abc123 --disclose --out ./my-proof.json
```

### Notes

- This command requires passkey approval. Your browser will prompt you to verify with your registered passkey (biometric, hardware key, or platform authenticator). The passkey is used to derive the decryption keys needed to access the snapshot's salt.
- By default, the proof does **not** include the plaintext file path. This means a verifier can confirm that *some* file with a specific content hash was included in the snapshot, but cannot determine which file it was. Use `--disclose` to include the path when transparency is preferred over privacy.
- The proof manifest is a self-contained JSON file that can be shared with any third party. It contains the Merkle proof siblings, the leaf hash, and the root -- everything needed for independent verification.
- Verification of the proof does not require authentication or contact with CodeQuill servers. See `codequill verify-proof`.

---

## codequill verify-proof

Verify a CodeQuill proof manifest. This checks that the Merkle proof is mathematically valid: that the leaf, sibling hashes, and path reconstruct the claimed Merkle root. Verification is entirely local and requires no authentication or server contact.

### Syntax

```bash
codequill verify-proof <proofFile>
```

### Arguments

| Argument | Required | Description |
|---|---|---|
| `proofFile` | yes | Path to the proof manifest JSON file to verify |

### Options

This command has no options.

### Example

```bash
codequill verify-proof .codequill/proofs/proof-7f3a2b.json
```

```
Verifying proof: .codequill/proofs/proof-7f3a2b.json

Leaf hash:   0xabc...
Merkle root: 0x789abc...def0
Proof depth: 14

Verification: PASSED
```

#### Verify a proof received from a third party

```bash
codequill verify-proof ./received-proof.json
```

### Notes

- This is a **pure local verification**. No network requests are made, no authentication is required, and no data is sent to any server. Anyone with the CLI installed can verify a proof.
- The command checks that the Merkle proof siblings, combined with the leaf hash along the specified path, reconstruct the Merkle root declared in the manifest.
- It does **not** verify that the Merkle root itself is anchored on-chain. To confirm on-chain anchoring, cross-reference the root with the snapshot's on-chain record using a block explorer or `codequill log`.
- A failing verification indicates that the proof is invalid -- either the file was not part of the snapshot, or the proof has been tampered with.

---

## codequill preserve

Create an encrypted preservation of the source state for a published snapshot. A preservation is an encrypted archive of the repository's tracked files at the snapshot's commit, stored durably so the source can be recovered in the future even if the original repository is lost.

### Syntax

```bash
codequill preserve <snapshotId> [options]
```

### Arguments

| Argument | Required | Description |
|---|---|---|
| `snapshotId` | yes | The snapshot ID to create a preservation for |

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--no-confirm` | boolean | `false` | Skip the interactive confirmation prompt |
| `--confirmations <n>` | integer | `1` | Number of on-chain confirmations to wait for |
| `--timeout <ms>` | integer | `300000` | Maximum time in milliseconds to wait for confirmation |
| `--no-wait` | boolean | `false` | Submit the transaction and return immediately without waiting for confirmation |
| `--json` | boolean | `false` | Output the result in machine-readable JSON |

### Example

```bash
codequill preserve snap_abc123
```

```
Creating preservation for snapshot: snap_abc123
Encrypting 312 files...
Uploading encrypted archive...
Anchoring on-chain...

Proceed? (y/N) y

Transaction submitted: 0xbbb222...
Waiting for confirmation...

Preservation created successfully.
  Preservation ID: pres_def456
  Archive CID: bafybeig...
  Explorer: https://sepolia.etherscan.io/tx/0xbbb222...
```

#### Non-interactive preservation

```bash
codequill preserve snap_abc123 --no-confirm --json
```

### Notes

- The source files are encrypted locally before upload. CodeQuill servers never see plaintext source code.
- Encryption keys are derived from your registered passkey. Only workspace members with the appropriate passkey can decrypt the preservation.
- The encrypted archive is uploaded to decentralized storage (IPFS) and an on-chain record is created linking the preservation to the snapshot.
- Preservations count against your workspace quota. Use `codequill quota` to check available capacity.
