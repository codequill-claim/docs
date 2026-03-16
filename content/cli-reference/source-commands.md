---
title: Source Commands
order: 2
---

# Source Commands

These commands manage the core source-code lifecycle: claiming repository authorship, creating snapshots, publishing them on-chain, and inspecting history.

---

## codequill claim

Claim authorship of the current git repository. This records an on-chain evidence statement linking your workspace authority to the repository. The transaction is gasless and relayed through the CodeQuill infrastructure.

### Syntax

```bash
codequill claim [options]
```

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
codequill claim
```

```
Claiming repository: my-org/my-project
Workspace: my-org
Chain: Ethereum

Proceed? (y/N) y

Transaction submitted: 0xabc123...
Waiting for confirmation...
Claimed successfully.
```

#### Non-interactive usage

```bash
codequill claim --no-confirm --json
```

### Notes

- The CLI auto-detects the repository from the git remote origin.
- A repository can only be claimed once. If it is already claimed by another workspace, the command will fail.
- The claim transaction is gasless -- it is relayed through CodeQuill's infrastructure, so you do not need ETH in your wallet to claim.
- Use `--no-confirm` and `--json` together for CI/CD pipelines where no interactive prompt is available.

---

## codequill snapshot

Create a local snapshot manifest for the current repository. A snapshot produces a deterministic Merkle root over all tracked files at a given commit. No data is uploaded; everything runs locally.

### Syntax

```bash
codequill snapshot [options]
```

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--commit <hash>` | string | `HEAD` | Git commit hash to snapshot |
| `--concurrency <n>` | integer | `8` | Number of concurrent file-hashing workers |
| `--salt <saltHex>` | string | _(none)_ | A 64-character hex string used as the salt for hashing. If not provided, one is generated automatically. |
| `--print-salt` | boolean | `false` | Print the salt value used for the snapshot to stdout |

### Example

```bash
codequill snapshot
```

```
Snapshotting commit a1b2c3d...
Hashing 312 files (concurrency: 8)...
Merkle root: 0x789abc...

Manifest written to .codequill/snapshots/a1b2c3d.json
```

#### Snapshot a specific commit with a custom salt

```bash
codequill snapshot --commit 9f3e2a1 --salt a1b2c3d4e5f6...0000 --print-salt
```

### Notes

- The snapshot reads every tracked file in the repository at the specified commit, hashes each file individually, and constructs a Merkle tree.
- The resulting manifest is written to `.codequill/snapshots/` and contains file hashes and the tree structure -- not the file contents themselves.
- The `--salt` option accepts exactly 64 hexadecimal characters. This salt is incorporated into the leaf hashes, making the Merkle root unpredictable to anyone who does not know the salt. If omitted, a random salt is generated.
- Use `--print-salt` to display the generated or provided salt. This is important if you plan to generate proofs later, as the salt is required for proof construction.
- The `--concurrency` option controls parallelism during file hashing. Increase it on machines with fast I/O; decrease it if you encounter resource limits.

---

## codequill publish

Publish a local snapshot manifest to IPFS and anchor the Merkle root on-chain. This creates a durable, timestamped, and independently verifiable evidence record for the source state at a given commit.

### Syntax

```bash
codequill publish [commit] [options]
```

### Arguments

| Argument | Required | Default | Description |
|---|---|---|---|
| `commit` | no | `HEAD` | The git commit hash to publish. If a snapshot manifest already exists for this commit, it is used directly. Otherwise, a snapshot is created first. |

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
codequill publish
```

```
Publishing snapshot for commit a1b2c3d...
Uploading manifest to IPFS...
Anchoring on-chain...

Transaction submitted: 0xdef456...
Waiting for confirmation...

Published successfully.
  Snapshot ID: snap_abc123
  Merkle Root: 0x789...
  Manifest CID: bafybeig...
  Explorer: https://sepolia.etherscan.io/tx/0xdef456...
```

#### Publish a specific commit non-interactively

```bash
codequill publish 9f3e2a1 --no-confirm --json
```

### Notes

- If no local snapshot exists for the target commit, the CLI creates one automatically before uploading.
- The manifest is uploaded to IPFS via Lighthouse for decentralized, content-addressed storage.
- The Merkle root is then recorded on the CodeQuill smart contract, creating an immutable on-chain timestamp.
- Use `--no-wait` if you want to submit the transaction and handle confirmation tracking separately (e.g. with `codequill wait`).

---

## codequill pull

Download all published snapshot manifests for the current repository into the local `.codequill/snapshots` directory. This synchronizes your local snapshot index with the records stored on IPFS and the backend.

### Syntax

```bash
codequill pull
```

### Options

This command has no options.

### Example

```bash
codequill pull
```

```
Fetching snapshot index...
Downloading 7 manifests...
  snap_abc123 -> .codequill/snapshots/a1b2c3d.json
  snap_def456 -> .codequill/snapshots/e4f5a6b.json
  ...

Pull complete. 7 manifests synced.
```

### Notes

- This command requires authentication and a network connection.
- Manifests are stored locally so that subsequent commands like `codequill log` and `codequill prove` can operate without network access.
- If a manifest already exists locally and matches the remote version, it is skipped.

---

## codequill log

Display the snapshot history for the current repository. Each entry shows the snapshot ID, commit hash, Merkle root, and publication timestamp.

### Syntax

```bash
codequill log [options]
```

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--limit <n>` | integer | _(none)_ | Maximum number of rows to display |

### Example

```bash
codequill log
```

```
SNAPSHOT ID    COMMIT    MERKLE ROOT         PUBLISHED
snap_abc123    a1b2c3d   0x789abc...def0    2025-01-15T10:32:00Z
snap_def456    e4f5a6b   0x456def...1234    2025-01-14T08:15:00Z
snap_ghi789    c7d8e9f   0x123ghi...5678    2025-01-12T14:48:00Z
```

#### Limit output to the 5 most recent snapshots

```bash
codequill log --limit 5
```

### Notes

- This command works offline using the local snapshot index in `.codequill/snapshots/`. Run `codequill pull` first to ensure your local index is up to date.
- If you are logged in and have network connectivity, the CLI enriches local data with additional information from the backend (such as publication timestamps and on-chain confirmation status).
- If no snapshots have been published for the repository, the output will be empty.

---

## codequill status

Show the CodeQuill status for the current repository. This provides an overview of the repository's claim state, most recent snapshot, and publication status.

### Syntax

```bash
codequill status
```

### Options

This command has no options.

### Example

```bash
codequill status
```

```
Repository: my-org/my-project
Claimed: yes
Latest snapshot: snap_abc123 (a1b2c3d)
Published: yes
Last published: 2025-01-15T10:32:00Z
```

### Notes

- This command must be run inside a git repository with a GitHub remote origin.
- It reads both local state (`.codequill/` directory) and remote state (if authenticated) to present a complete picture.
- If the repository has not been claimed, the command will indicate that and suggest running `codequill claim`.
