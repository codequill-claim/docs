---
title: Preservations
description: "Preservations are encrypted, zero-custody archives of source code tied to published snapshots for long-term survival and recovery."
order: 6
---

# Preservations

A preservation is an encrypted archive of a repository's full source code, tied to a specific published snapshot. It enables long-term source survival without requiring trust in any custodian -- not even CodeQuill.

## Why Preservations Exist

Snapshots record **hashes** of source files, not the files themselves. This is by design: the snapshot proves what existed without requiring disclosure. But there are scenarios where having the actual source code available decades later matters: legal discovery, audit obligations, disaster recovery, or historical research.

Preservations solve this by encrypting the full source archive and storing it on decentralized storage (IPFS via Lighthouse). The encryption uses keys that only the workspace authority can access, ensuring zero-custody: CodeQuill never sees the plaintext.

The term "preservation" is deliberate. This is not a backup in the traditional sense (you control your own backups). It is a **durable, encrypted evidence artifact** tied to a cryptographic proof of existence.

## Encryption Model

Preservations use the `codequill-envelope:v1` encryption scheme:

1. The CLI creates a deterministic `git archive` of the commit (tar + gzip).
2. A SHA-256 hash of the plaintext archive is computed.
3. A random **Data Encryption Key (DEK)** and **IV** are generated.
4. The archive is encrypted with **AES-256-GCM** using the DEK and IV.
5. The DEK is wrapped using an **X25519 sealed box** (libsodium `crypto_box_seal`) to the workspace's public encryption key.
6. The encrypted payload, wrapped DEK, and IV are uploaded to decentralized storage.
7. The SHA-256 of the plaintext archive (computed before encryption) is anchored on-chain.

To decrypt, the workspace authority uses their passkey to derive the X25519 private key, unwrap the DEK, and decrypt the archive.

Key properties:

- **Zero-custody** -- CodeQuill never possesses the decryption key.
- **Passkey-bound** -- The encryption key is derived from a WebAuthn passkey using the PRF extension.
- **No admin backdoor** -- There is no recovery mechanism. If the passkey is lost, the preservation cannot be decrypted.
- **Snapshot-bound** -- Each preservation is linked to a specific snapshot, with the content root verified before encryption.

For full technical details, see [Encryption Model](/security/encryption-model).

## Using the CLI

### Create a Preservation

```bash
codequill preserve <snapshot-id>
```

**Arguments:**

| Argument | Required | Description |
|---|---|---|
| `<snapshot-id>` | Yes | The ID of a published snapshot to preserve |

**Options:**

| Option | Type | Default | Description |
|---|---|---|---|
| `--no-confirm` | boolean | false | Skip confirmation prompt |
| `--confirmations <n>` | integer | 1 | On-chain confirmations to wait for |
| `--timeout <ms>` | integer | 300000 | Timeout for confirmation |
| `--no-wait` | boolean | false | Submit and return immediately |
| `--json` | boolean | false | Output in JSON format |

### Prerequisites

Before creating a preservation:

1. The snapshot must be **published** and anchored on-chain.
2. Your local repository must match the snapshot's content root (the CLI verifies this before proceeding).
3. Your workspace must have encryption set up (passkey registered).

### Example

```bash
codequill preserve snap_abc123
```

The CLI will:

1. Fetch the published snapshot information.
2. Verify your local repository matches the snapshot.
3. Create a deterministic archive via `git archive`.
4. Encrypt the archive with your workspace's encryption key.
5. Upload to decentralized storage.
6. Anchor the archive hash on-chain.

```
Preserving snapshot snap_abc123...
Verifying content root...
Creating archive...
Encrypting (AES-256-GCM)...
Uploading to IPFS...
Anchoring on-chain...

Transaction submitted: 0xabc123...
Waiting for confirmation...

Preservation complete.
```

## On-Chain Record

The `CodeQuillPreservationRegistry` smart contract stores:

- **Snapshot Merkle Root** -- Links the preservation to a specific snapshot.
- **Archive SHA-256** -- Hash of the plaintext archive (before encryption), proving content integrity.
- **Metadata SHA-256** -- Hash of the preservation metadata.
- **Preservation CID** -- IPFS CID of the encrypted payload.
- **Author and timestamp**.

The on-chain record proves that an encrypted archive with a specific plaintext hash was stored at a specific time. It does not reveal the archive contents.

## Auditor Context

For auditors: a preservation proves that an encrypted archive was created and stored at a specific time, bound to a specific snapshot. The plaintext hash allows verification that a decrypted archive matches the on-chain record. However, decryption requires the workspace authority's cooperation. See [Interpreting Evidence](/reference/interpreting-evidence).
