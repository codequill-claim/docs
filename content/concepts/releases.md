---
title: Releases
order: 4
---

# Releases

A release is a named, versioned unit of software that ties together one or more source snapshots into a coherent, governed package. Releases are the coordination layer between raw evidence (snapshots) and downstream processes (attestations, governance, deployment).

## Why Releases Exist

Snapshots capture what source code existed at a moment. But software is not shipped as snapshots -- it is shipped as releases. A release represents a deliberate act: someone decided that this particular source state, at this particular time, should be treated as a version.

Releases add **intent** to evidence. They say: "This snapshot is not just a point-in-time capture. It is the selected source state for version X, and it carries governance and approval processes."

## Release Lifecycle

Every release follows a defined lifecycle:

```
Draft  →  Published (Anchored)  →  Accepted
                                 →  Rejected
                                 →  Revoked  →  Superseded
```

### Draft

A release starts as a draft. Drafts are created in the web application by selecting a repository and a published snapshot. You can add a name, notes, and configure governance options.

[Screenshot: Release creation form showing repository selection, snapshot selection, and governance options]

### Published (Anchored)

When a draft is published, it is **anchored on-chain**. This involves:

1. Building a release manifest (JSON with the `codequill-release:v1` schema).
2. Compressing and uploading the manifest to IPFS via Lighthouse.
3. Calling `anchorRelease()` on the `CodeQuillReleaseRegistry` smart contract with the release ID, manifest CID, name, author, governance authority, repository ID, and Merkle root.

Once anchored, a release is immutable. Its name, associated snapshot, and metadata cannot be changed.

### Governance: Accepted or Rejected

After anchoring, the release enters a **governance phase** with status `PENDING`. The designated governance authority (or DAO executor, if configured) can then:

- **Accept** the release -- confirming it as the approved version.
- **Reject** the release -- declining it.

Governance decisions are recorded on-chain via the `accept()` or `reject()` functions on the `CodeQuillReleaseRegistry`.

Importantly: **attestations can only be created against accepted releases.** This means governance approval is a prerequisite for attesting build artifacts.

### Revoked

An accepted or pending release can be **revoked** by the author or a delegate. Revocation is recorded on-chain and marks the release as no longer valid. Revoked releases cannot be accepted or attested.

### Superseded

A revoked release can be **superseded** by a new release. This creates an explicit on-chain link from the old release to its replacement, providing a clear upgrade path.

## DAO-Only Governance

Releases can be configured as **DAO-only**. When this flag is set, only the workspace's DAO executor address can accept or reject the release. This enables integration with external governance systems like Aragon, Governor contracts, or multisig wallets.

For detailed information about DAO governance setup, see [DAO Governance](/docs/releases/dao-governance).

## Release Manifests

Each published release has a manifest stored on IPFS. The manifest follows the `codequill-release:v1` schema and contains:

- Repository information (name, GitHub ID)
- Release metadata (name, notes, timestamp)
- Authority address (the governance authority)
- Source snapshot data (Merkle root, commit hash, manifest CID)
- Supersession information (if replacing a revoked release)

For more details, see [Release Manifests](/docs/releases/release-manifests).

## CI/CD Integration

When a release is anchored or accepted, CodeQuill can trigger GitHub Actions workflows via the bot `codequill-authorship[bot]`. This enables powerful automation pipelines:

1. Release is anchored → GitHub Issue created → CI can build the artifact.
2. Release is accepted by governance → GitHub Issue created → CI runs attestation → deploys to production.

See [CI/CD Integration](/docs/ci-cd/overview) for the complete workflow.
