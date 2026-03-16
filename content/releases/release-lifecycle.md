---
title: Release Lifecycle
order: 1
---

# Release Lifecycle

Releases are the governed coordination layer between source snapshots and downstream processes such as attestations and deployment. Every release follows a defined lifecycle, from draft creation through governance resolution.

Releases are created exclusively in the CodeQuill web application. The CLI is not involved in release creation -- its role ends at snapshot publishing. Once a snapshot has been published, the web application takes over for release management.

## Lifecycle Overview

```
Draft  →  Published (Anchored)  →  PENDING  →  Accepted  →  (Attestation eligible)
                                             →  Rejected
                                             →  Revoked  →  Superseded
```

Each transition is either an explicit user action or a governance decision. Once a release is anchored, its core metadata is immutable.

## Creating a Draft

To create a release, navigate to the Releases section in the web application and select **New Release**.

[Screenshot: Release creation form with repository dropdown, snapshot selector, name and notes fields, and governance options]

The creation form requires:

| Field | Required | Description |
|---|---|---|
| Repository | Yes | The repository this release is for. Only repositories with at least one published snapshot are listed. |
| Snapshot | Yes | The published snapshot to associate with this release. The dropdown shows available snapshots with their commit hashes and publish dates. |
| Release Name | Yes | A human-readable name for the release (e.g. `v1.2.0`, `2026-03-security-patch`). |
| Notes | No | Free-text notes describing the release. These are included in the release manifest and stored on IPFS. |
| DAO-Only Governance | No | When enabled, only the workspace's DAO executor address can accept or reject this release. See [DAO Governance](/docs/releases/dao-governance). |
| Supersedes Release | No | If this release replaces a previously revoked release, select the revoked release here. This creates an explicit on-chain link between the old and new release. Only revoked releases appear in this list. |

Draft releases are not yet on-chain. They exist only in the CodeQuill web application and can be freely modified or deleted.

## Editing Drafts

Before anchoring, a draft release can be edited. You can change the name, notes, governance configuration, and supersession target. The repository and snapshot selection can also be changed while the release remains in draft state.

[Screenshot: Draft release editing view showing editable fields and the Anchor button]

Once you are satisfied with the release configuration, you proceed to anchoring. This is an irreversible action -- review the details carefully before proceeding.

## Anchoring (Publishing)

Anchoring a release is the transition from draft to on-chain record. When you anchor a release, the following sequence occurs:

1. **Manifest construction.** The web application builds a release manifest conforming to the `codequill-release:v1` schema. This manifest contains the repository information, release metadata, authority address, source snapshot data, and supersession information. See [Release Manifests](/docs/releases/release-manifests) for the full schema.

2. **IPFS upload.** The manifest is gzip-compressed and uploaded to IPFS via Lighthouse (pinned to Filecoin for long-term persistence). The resulting CID becomes the permanent, content-addressed identifier for this manifest.

3. **On-chain anchoring.** The web application calls `anchorRelease()` on the `CodeQuillReleaseRegistry` smart contract. This transaction records the release ID, manifest CID, release name, author address, governance authority, repository ID, and the snapshot's Merkle root.

Once the transaction is confirmed, the release is immutable. Its name, associated snapshot, metadata, and governance configuration cannot be changed.

[Screenshot: Anchoring confirmation dialog showing transaction details and gas estimate]

### GitHub Issue on Anchor

When a release is successfully anchored, CodeQuill triggers a GitHub Issue on the associated repository via `codequill-authorship[bot]`. This issue contains the release metadata, manifest CID, and on-chain transaction reference.

This issue serves as an integration point. CI/CD workflows can listen for issues created by `codequill-authorship[bot]` with specific labels to trigger build processes, artifact generation, or other automation. The issue provides all the information needed for downstream systems to identify and process the release.

## Governance Phase

After anchoring, the release enters the governance phase with status **PENDING**. The release remains in this state until a governance decision is made.

The designated governance authority can:

- **Accept** the release -- confirming it as the approved version for this repository. Acceptance is recorded on-chain via the `accept()` function on the `CodeQuillReleaseRegistry`.
- **Reject** the release -- declining it. Rejection is recorded on-chain via the `reject()` function.

Who qualifies as the governance authority depends on the release's configuration:

- **Standard governance:** The workspace's governance authority address can accept or reject.
- **DAO-only governance:** Only the workspace's DAO executor address can accept or reject. The regular governance authority cannot act on DAO-only releases. See [DAO Governance](/docs/releases/dao-governance).

Governance decisions are final. A release that has been accepted cannot later be rejected (though it can be revoked). A rejected release cannot be accepted.

### GitHub Issue on Acceptance

When a release is accepted, a second GitHub Issue is created by `codequill-authorship[bot]`. This issue signals that the release has passed governance and is now eligible for attestation.

CI/CD workflows can use this acceptance issue to trigger attestation processes -- for example, building the artifact from the approved source snapshot and running `codequill attest` against the accepted release. This enables a fully automated pipeline from governance approval through attestation to deployment.

### Attestation Eligibility

Attestations can **only** be created against releases with status ACCEPTED. This is enforced by the `CodeQuillAttestationRegistry` smart contract. A release that is pending, rejected, or revoked cannot have attestations created against it.

This constraint ensures that every attestation is backed by an explicit governance approval. There is no way to bypass this requirement.

## Revoking a Release

An accepted or pending release can be revoked by the author or an authorized delegate. Revocation is recorded on-chain and marks the release as no longer valid.

[Screenshot: Revocation confirmation dialog with release details]

Revocation has the following effects:

- The release status changes to REVOKED.
- No new attestations can be created against the release.
- Existing attestations remain on-chain but reference a revoked release, which downstream consumers should treat as a warning signal.
- The release becomes eligible to be superseded by a new release.

Revocation is a deliberate act. It indicates that the release should no longer be treated as authoritative -- whether due to a discovered vulnerability, a build error, or any other reason.

## Superseding a Release

When creating a new release, you can optionally designate it as superseding a previously revoked release. This creates an explicit on-chain link from the revoked release to its replacement.

Supersession provides a clear upgrade path. Systems or users that hold a reference to the revoked release can follow the supersession link to find the designated replacement. Only revoked releases can be superseded -- you cannot supersede an active or pending release.

The supersession relationship is recorded in both the release manifest (stored on IPFS) and the on-chain registry, ensuring it is independently verifiable.

## Summary of Status Transitions

| From | To | Action | Actor |
|---|---|---|---|
| Draft | Anchored (PENDING) | Anchor | Release author |
| PENDING | ACCEPTED | Accept | Governance authority or DAO executor |
| PENDING | REJECTED | Reject | Governance authority or DAO executor |
| PENDING | REVOKED | Revoke | Author or delegate |
| ACCEPTED | REVOKED | Revoke | Author or delegate |
| REVOKED | SUPERSEDED | Superseded by new release | New release author |
