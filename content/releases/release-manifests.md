---
title: Release Manifests
order: 3
---

# Release Manifests

Every anchored release has a manifest -- a structured document that records the full metadata of the release at the time it was published. Manifests are the primary evidence artifact for releases: they are stored on decentralized storage, content-addressed, and independently retrievable by anyone.

## Schema

Release manifests conform to the `codequill-release:v1` schema. This schema is versioned to allow future evolution while preserving backward compatibility with existing manifests.

## Storage

Manifests are stored as gzip-compressed JSON on IPFS, pinned to Filecoin via Lighthouse for long-term persistence. Each manifest is identified by its CID (Content Identifier) -- a cryptographic hash of the compressed content. The CID is recorded on-chain as part of the `anchorRelease()` transaction, creating a permanent link between the on-chain release record and its off-chain manifest.

Because CIDs are content-addressed, the integrity of a manifest is self-verifying: if the content changes, the CID changes. A manifest retrieved by CID either matches its expected content exactly or is corrupt.

## Manifest Structure

A `codequill-release:v1` manifest contains the following sections:

### Repository Information

| Field | Type | Description |
|---|---|---|
| `repository.name` | string | The repository name as configured in CodeQuill. |
| `repository.github_id` | integer | The GitHub repository ID, linking the release to a specific GitHub repository. |

### Release Metadata

| Field | Type | Description |
|---|---|---|
| `release.name` | string | The human-readable release name (e.g. `v1.2.0`). |
| `release.notes` | string | Free-text notes provided by the release author. May be empty. |
| `release.timestamp` | string | ISO 8601 timestamp of when the release was anchored. |
| `release.id` | string | The unique release identifier. |

### Authority

| Field | Type | Description |
|---|---|---|
| `authority.address` | string | The Ethereum address of the governance authority for this release. This is the address authorized to accept or reject the release (or the DAO executor, if DAO-only governance is configured). |

### Source Snapshot

| Field | Type | Description |
|---|---|---|
| `snapshot.merkle_root` | string | The Merkle root of the snapshot's file tree. This is the same value recorded on-chain in the `CodeQuillSnapshotRegistry`. |
| `snapshot.commit_hash` | string | The git commit hash that the snapshot was taken at. |
| `snapshot.manifest_cid` | string | The IPFS CID of the snapshot manifest. This allows anyone to retrieve the full snapshot manifest and verify the file tree independently. |

### Supersession

| Field | Type | Description |
|---|---|---|
| `supersedes.release_id` | string or null | The release ID that this release supersedes, if any. Null if this release does not supersede another. |
| `supersedes.reason` | string or null | An optional reason for supersession. |

The supersession fields are only populated when the release was created with a supersession target (a previously revoked release). See [Release Lifecycle](/docs/releases/release-lifecycle) for details on supersession.

## Downloading Manifests

Release manifests can be downloaded from the CodeQuill web application. Navigate to the release detail page and select **Download Manifest**. The downloaded file is the raw gzip-compressed JSON as stored on IPFS.

[Screenshot: Release detail page showing the Download Manifest button and manifest CID]

You can also retrieve the manifest directly from IPFS using the CID. Any IPFS gateway or local IPFS node can serve the content:

```
https://gateway.lighthouse.storage/ipfs/<manifest-cid>
```

After retrieving the compressed file, decompress it to obtain the JSON manifest:

```bash
gunzip manifest.json.gz
```

## Verification Process

The value of a release manifest lies in its verifiability. Anyone with the manifest CID can independently verify the release's claims without trusting CodeQuill or the release author.

### Step 1: Retrieve the Manifest

Fetch the manifest from IPFS using its CID. The CID is recorded on-chain in the `CodeQuillReleaseRegistry` as part of the release record, so the starting point for verification can be the on-chain data itself.

```bash
# Retrieve from any IPFS gateway
curl -o manifest.json.gz https://gateway.lighthouse.storage/ipfs/<manifest-cid>
gunzip manifest.json.gz
```

### Step 2: Verify CID Integrity

The CID is a content hash. If you retrieved the manifest by CID and the IPFS client or gateway returned content, the content matches the CID by definition. If you obtained the manifest through other means (direct download, email, etc.), you can compute the CID of the file and compare it to the on-chain record.

### Step 3: Verify the Snapshot Merkle Root

The manifest contains a `snapshot.merkle_root` value. Compare this value against the on-chain record in the `CodeQuillSnapshotRegistry`:

1. Query the `CodeQuillSnapshotRegistry` contract for the snapshot associated with the repository and commit hash referenced in the manifest.
2. Confirm that the Merkle root stored on-chain matches the `snapshot.merkle_root` in the manifest.

If the values match, the manifest correctly references a snapshot that was independently anchored on-chain. If they do not match, the manifest is inconsistent with the on-chain evidence.

### Step 4: Verify the Release Anchor

Query the `CodeQuillReleaseRegistry` contract for the release ID referenced in the manifest:

1. Confirm that the on-chain release record exists and references the same manifest CID.
2. Confirm that the on-chain authority address matches the `authority.address` in the manifest.
3. Confirm that the on-chain Merkle root matches the `snapshot.merkle_root` in the manifest.

This establishes that the release was anchored by the claimed authority, references the claimed snapshot, and that the manifest stored on IPFS is the same one recorded on-chain.

### Step 5: Verify the Snapshot Contents (Optional)

For deeper verification, retrieve the snapshot manifest using the `snapshot.manifest_cid` from the release manifest. The snapshot manifest contains per-file hashes that constitute the Merkle tree. You can:

1. Recompute the Merkle tree from the individual file hashes in the snapshot manifest.
2. Confirm the resulting root matches the `snapshot.merkle_root`.
3. If you have access to the source files, hash each file and compare against the snapshot manifest entries.

This level of verification confirms not just that the release references a valid snapshot, but that the snapshot itself is internally consistent and (if you have the source files) that the files match.

## What Manifests Do Not Contain

Release manifests deliberately exclude certain information:

- **Source code.** Manifests contain hashes, not file contents. Source code is never stored in or transmitted through the manifest.
- **Build artifacts.** Manifests describe source state, not compiled output. Artifacts are linked to releases through [Attestations](/docs/concepts/attestations), not manifests.
- **Governance decisions.** The manifest records the state at the time of anchoring. Whether the release was later accepted, rejected, or revoked is recorded on-chain, not in the manifest. The manifest is immutable; governance state changes over time.

A manifest is a snapshot of intent: "This authority released this source state, with this metadata, at this time." Everything that happens after anchoring is recorded elsewhere.
