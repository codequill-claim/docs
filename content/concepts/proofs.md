---
title: Proofs
order: 7
---

# Proofs

A proof is a cryptographic Merkle proof-of-inclusion that demonstrates a specific file was part of a published snapshot. Proofs enable selective disclosure: you can prove the existence and content of individual files without revealing the full file tree.

## Why Proofs Exist

Snapshots anchor a Merkle root representing the entire repository state. But sometimes you need to demonstrate that a specific file -- a license, a configuration, a critical module -- was present in a specific version, without disclosing everything else.

Proofs let you answer the question: "Was this exact file, with this exact content, included in this snapshot?" The answer is cryptographically verifiable by anyone, without needing access to the full snapshot or the CodeQuill platform.

## How Proofs Work

A Merkle proof-of-inclusion works by providing the minimal set of sibling hashes needed to reconstruct the root from a specific leaf:

1. The **leaf** is computed: `keccak256(0x00 || path_hash || file_hash)`
2. The proof provides the **sibling nodes** at each level of the tree.
3. A verifier folds the proof (hashing pairs at each level) to reconstruct the Merkle root.
4. If the reconstructed root matches the on-chain Merkle root, the file was provably included.

## Privacy Considerations

Because file paths are salted before hashing, generating a proof requires knowledge of the salt. The salt is derived from the workspace encryption key, which means:

- **Proof generation requires authority** -- You must be a workspace member with passkey access.
- **Proof verification is public** -- Once generated, anyone can verify the proof without any authentication.

When generating a proof, the CLI initiates a device-approval flow (similar to the login flow) where you approve the path resolution using your passkey in the browser. This ensures the salt is never exposed to the CLI directly.

### The `--disclose` Option

By default, proofs do not include the plaintext file path. They contain only the salted path hash, which is sufficient for cryptographic verification but does not reveal which file was proven.

If you want the proof to include the plaintext path (for human readability), use the `--disclose` flag. This is a privacy tradeoff -- the proof becomes more informative but reveals the file's location in the repository.

## Using the CLI

### Generate a Proof

```bash
codequill prove <file> <snapshot-id>
```

**Arguments:**

| Argument | Required | Description |
|---|---|---|
| `<file>` | Yes | Path to the file to prove inclusion for |
| `<snapshot-id>` | Yes | The snapshot ID to prove against |

**Options:**

| Option | Type | Default | Description |
|---|---|---|---|
| `--disclose` | boolean | false | Include the plaintext path in the proof |
| `--out <file>` | string | `.codequill/proofs/proof-<hash>.json` | Output path for the proof file |

### Example: Generate a Proof

```bash
codequill prove src/main.ts snap_abc123
```

The CLI will:

1. Read and hash the target file (`keccak256`).
2. Fetch the snapshot manifest from the backend.
3. Initiate a device-approval flow to resolve the salted path hash.
4. Locate the file in the manifest.
5. Build the Merkle proof.
6. Write the proof to `.codequill/proofs/proof-<hash>.json`.

### Example: Proof with Path Disclosure

```bash
codequill prove src/main.ts snap_abc123 --disclose
```

### Example: Custom Output Path

```bash
codequill prove LICENSE snap_abc123 --out evidence/license-proof.json
```

## Verify a Proof

Proof verification is a separate, **offline** operation that requires no authentication:

```bash
codequill verify-proof <proof-file>
```

**Arguments:**

| Argument | Required | Description |
|---|---|---|
| `<proof-file>` | Yes | Path to the proof JSON file |

The verification process:

1. Reads the proof file (must follow `codequill-proof:v1` schema).
2. Recomputes the leaf hash from the path hash and file hash.
3. Folds the Merkle proof using the sibling nodes.
4. Compares the computed root against the Merkle root in the proof.

```bash
codequill verify-proof .codequill/proofs/proof-abc123.json
```

```
Proof verification: VALID

  Merkle Root: 0x789abc...
  File Hash:   0xdef012...
  Proof Steps: 12
```

If the proof is valid, it cryptographically demonstrates that the file was included in the snapshot identified by the Merkle root. The verifier can then cross-reference the Merkle root against the on-chain record to confirm the snapshot was anchored at a specific time by a specific authority.

## Proof File Format

Proofs follow the `codequill-proof:v1` schema:

```json
{
  "version": "codequill-proof:v1",
  "merkle_root": "0x...",
  "file": {
    "path_hash": "0x...",
    "hash": "0x...",
    "path": "src/main.ts"
  },
  "proof": [
    { "hash": "0x...", "position": "left" },
    { "hash": "0x...", "position": "right" }
  ]
}
```

The `file.path` field is only present if `--disclose` was used during generation.

## Auditor Context

For auditors: a verified proof demonstrates that a file with a specific content hash was included in a snapshot with a specific Merkle root. Combined with the on-chain anchor of that Merkle root, this constitutes evidence that the file existed at the time of anchoring. See [Interpreting Evidence](/reference/interpreting-evidence).
