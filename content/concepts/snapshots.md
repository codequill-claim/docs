---
title: Snapshots
description: "Snapshots are deterministic cryptographic fingerprints of your source code at a specific commit. Learn how they work and how to publish them."
order: 3
---

# Snapshots

A snapshot is a deterministic cryptographic fingerprint of a repository's source code at a specific git commit. It is the foundational building block of CodeQuill -- releases, attestations, and preservations all reference snapshots.

## Why Snapshots Exist

Source code is mutable. Files change, commits are amended, branches are rebased, repositories are deleted. When you need to answer "what source code existed at this moment?", the answer often depends on systems that may no longer be available.

A CodeQuill snapshot captures the state of a repository at a precise point in time and produces a **Merkle root** -- a single cryptographic hash that represents the entire file tree. This Merkle root can be verified independently by anyone with access to the same files.

## How Snapshots Work

### Step 1: Local Manifest Creation

When you run `codequill snapshot`, the CLI:

1. Reads every tracked file in the repository at the specified commit (defaults to HEAD).
2. Computes a `keccak256` hash of each file's contents.
3. Computes a salted hash of each file's path (for privacy -- see below).
4. Builds a **Merkle tree** from the leaf hashes (each leaf = `keccak256(0x00 || path_hash || file_hash)`).
5. Writes the resulting manifest to `.codequill/snapshots/snapshot-<commit>.json`.

No source code leaves your machine. The manifest contains only hashes and metadata.

### Step 2: Publishing

When you run `codequill publish`, the CLI:

1. Compresses the manifest and uploads it to decentralized storage (IPFS via Lighthouse).
2. Submits the Merkle root, commit hash, and manifest CID to the `CodeQuillSnapshotRegistry` smart contract.

The result is a durable, timestamped record: "This Merkle root, representing this file tree, was anchored at this time by this authority."

## Privacy Model

File paths in a snapshot are **salted** before hashing. The salt is derived from the workspace's encryption key, which means:

- The manifest reveals **file content hashes** but not file paths in plaintext.
- Path verification requires knowledge of the salt (which requires workspace authority).
- This allows selective disclosure: you can prove a specific file was included (via [Proofs](/concepts/proofs)) without revealing the full directory structure.

## Using the CLI

### Create a Snapshot

```bash
codequill snapshot
```

This creates a local manifest for the current HEAD commit.

### Snapshot Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--commit <hash>` | string | HEAD | Commit hash to snapshot |
| `--concurrency <n>` | integer | 8 | Number of concurrent file reads |
| `--salt <hex>` | string | auto | Custom salt for path hashing (64 hex characters) |
| `--print-salt` | boolean | false | Display the salt in output (security risk in shared environments) |

### Publish a Snapshot

```bash
codequill publish
```

Publishes the snapshot for the current HEAD. You can also specify a commit:

```bash
codequill publish a1b2c3d
```

### Publish Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--no-confirm` | boolean | false | Skip confirmation prompt |
| `--confirmations <n>` | integer | 1 | Wait for N on-chain confirmations |
| `--timeout <ms>` | integer | 300000 | Timeout for confirmation (milliseconds) |
| `--no-wait` | boolean | false | Submit and return immediately |
| `--json` | boolean | false | Output in JSON format |

### View Snapshot History

```bash
codequill log
```

Displays the snapshot history for the current repository, ordered by published time. Optionally limit results:

```bash
codequill log --limit 10
```

### Download Published Snapshots

```bash
codequill pull
```

Downloads all published snapshot manifests for the current repository into `.codequill/snapshots/`. This is useful when working on a new machine or after cloning a repository.

## Snapshot Manifests

A snapshot manifest follows the `codequill-snapshot:v1` schema. It contains:

- Repository name and commit hash
- Merkle root and content root
- File count
- Per-file entries: path hash, content hash, and file size
- The salt used for path hashing (encrypted)

Manifests are stored as gzip-compressed JSON on IPFS. Anyone can retrieve a manifest using its CID and independently verify the Merkle tree.

## What Snapshots Do Not Do

Snapshots record **what existed**, not **how it was used**:

- They do not prove how the code was built or compiled.
- They do not guarantee the code is correct, safe, or complete.
- They do not assert anything about the code's origin or authorship (that is what [Claims](/concepts/claims) are for).
- They do not prevent the code from being modified after the snapshot is taken.

A snapshot is a receipt. It says: "This exact file tree existed at this moment, as attested by this authority."
