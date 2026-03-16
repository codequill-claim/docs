---
title: Contract Reference
order: 2
---

# Contract Reference

This page documents each of the seven CodeQuill smart contracts: their purpose, key functions, and emitted events.

All state-changing functions that accept user intent have `WithSig` variants. These variants accept an EIP-712 signature and recover the signer on-chain, allowing a relayer to submit the transaction on the user's behalf.

---

## CodeQuillDelegation

Context-scoped delegation system. An owner signs an EIP-712 message granting a relayer (or any delegate) permission to act on their behalf within a specific workspace context. Delegations carry granular scope bitmasks and an expiry timestamp.

### Functions

| Function | Description |
|---|---|
| `registerDelegationWithSig()` | Registers a new delegation from a signed EIP-712 payload. Records the delegator, delegate, context, scopes, and expiry. |
| `revoke()` | Revokes a delegation. Called directly by the delegator. |
| `revokeWithSig()` | Revokes a delegation via a signed EIP-712 payload, allowing relayer-mediated revocation. |
| `isAuthorized()` | Returns whether a given delegate holds an active, non-expired delegation with the required scope for a specific context. |

### Events

| Event | Description |
|---|---|
| `Delegated` | Emitted when a new delegation is registered. |
| `Revoked` | Emitted when a delegation is revoked. |

---

## CodeQuillWorkspaceRegistry

Binds wallet addresses to workspace contexts. Each workspace has a single authority address that controls membership. Members are added or removed by the authority through signed messages.

### Functions

| Function | Description |
|---|---|
| `initAuthority()` | Sets the initial authority for a workspace context. Can only be called once per context. |
| `setAuthorityWithSig()` | Transfers authority to a new address via a signed EIP-712 payload. Only the current authority can sign this. |
| `setMemberWithSig()` | Adds or removes a member from a workspace context via a signed payload from the authority. |
| `leave()` | Allows a member to voluntarily leave a workspace context. Called directly by the member. |

### Events

| Event | Description |
|---|---|
| `AuthoritySet` | Emitted when a workspace authority is initialized or transferred. |
| `MemberSet` | Emitted when a member is added to or removed from a workspace. |

---

## CodeQuillRepositoryRegistry

Maps repository identifiers to owner wallet addresses and workspace contexts. A repository must be claimed before it can be referenced by snapshots, releases, or other downstream contracts.

**Depends on:** CodeQuillDelegation, CodeQuillWorkspaceRegistry

### Functions

| Function | Description |
|---|---|
| `claimRepo()` | Claims a repository within a workspace context, binding it to the caller's address. |
| `transferRepo()` | Transfers ownership of a claimed repository to a different address. |
| `isClaimed()` | Returns whether a given repository identifier has been claimed. |
| `repoOwners()` | Returns the current owner address for a repository. |
| `getReposByOwner()` | Returns all repository identifiers owned by a given address within a context. |

### Events

| Event | Description |
|---|---|
| `RepoClaimed` | Emitted when a repository is claimed. |
| `RepoTransferred` | Emitted when repository ownership is transferred. |

---

## CodeQuillSnapshotRegistry

On-chain snapshot anchoring. Each snapshot records a commit hash, a Merkle root of the repository's file tree, and an IPFS content identifier. Snapshots are the foundational data structure referenced by releases, preservations, and attestations.

**Depends on:** CodeQuillRepositoryRegistry, CodeQuillWorkspaceRegistry, CodeQuillDelegation

### Functions

| Function | Description |
|---|---|
| `createSnapshot()` | Anchors a new snapshot for a claimed repository. Records the commit hash, Merkle root, and IPFS CID. |
| `getSnapshotsCount()` | Returns the total number of snapshots for a given repository. |
| `getSnapshot()` | Retrieves a snapshot by its index within a repository's snapshot list. |
| `getSnapshotByRoot()` | Looks up a snapshot by its Merkle root. |

### Events

| Event | Description |
|---|---|
| `SnapshotCreated` | Emitted when a new snapshot is anchored. |

---

## CodeQuillReleaseRegistry

Release anchoring with a governance lifecycle. A release references a snapshot and carries a status that can progress through acceptance, rejection, revocation, or supersession. Supports DAO executor addresses for governance-controlled status transitions.

**Depends on:** CodeQuillRepositoryRegistry, CodeQuillWorkspaceRegistry, CodeQuillDelegation, CodeQuillSnapshotRegistry

### Functions

| Function | Description |
|---|---|
| `anchorRelease()` | Anchors a new release referencing a specific snapshot. |
| `accept()` | Marks a release as accepted. |
| `reject()` | Marks a release as rejected. |
| `revokeRelease()` | Revokes a previously accepted release. |
| `supersedeRelease()` | Marks a release as superseded by a newer release. |
| `setDaoExecutor()` | Sets or updates the DAO executor address authorized to perform governance transitions for a context. |
| `getReleaseById()` | Retrieves the full release record by its identifier. |
| `getGouvernanceStatus()` | Returns the current governance status of a release. |

### Events

| Event | Description |
|---|---|
| `ReleaseAnchored` | Emitted when a new release is anchored. |
| `ReleaseSuperseded` | Emitted when a release is marked as superseded. |
| `ReleaseRevoked` | Emitted when a release is revoked. |
| `GouvernanceStatusChanged` | Emitted when the governance status of a release changes (accepted, rejected, revoked, or superseded). |
| `DaoExecutorSet` | Emitted when a DAO executor address is set or updated. |

---

## CodeQuillPreservationRegistry

Anchors existence proofs of encrypted source-code archives. Each preservation record is linked to a specific snapshot and contains the metadata needed to verify that an encrypted archive existed at a given point in time.

**Depends on:** CodeQuillRepositoryRegistry, CodeQuillWorkspaceRegistry, CodeQuillDelegation, CodeQuillSnapshotRegistry

### Functions

| Function | Description |
|---|---|
| `anchorPreservation()` | Anchors a new preservation record linked to a snapshot. |
| `hasPreservation()` | Returns whether a preservation record exists for a given snapshot. |
| `getPreservation()` | Retrieves the full preservation record for a given snapshot. |

### Events

| Event | Description |
|---|---|
| `PreservationAnchored` | Emitted when a new preservation record is anchored. |

---

## CodeQuillAttestationRegistry

Records supply-chain attestations containing SHA-256 artifact digests, bound to releases that have reached `ACCEPTED` status. Attestations provide a verifiable link between a release and the artifacts produced from it.

**Depends on:** CodeQuillWorkspaceRegistry, CodeQuillDelegation, CodeQuillReleaseRegistry

### Functions

| Function | Description |
|---|---|
| `createAttestation()` | Creates a new attestation linked to an accepted release. Records the artifact digest and attestation metadata. |
| `revokeAttestation()` | Revokes an existing attestation. The attestation data remains on-chain but is marked as revoked. |
| `isRevoked()` | Returns whether a specific attestation has been revoked. |
| `getAttestationsCount()` | Returns the total number of attestations for a given release. |
| `getAttestation()` | Retrieves an attestation by its index within a release's attestation list. |
| `getAttestationByDigest()` | Looks up an attestation by its SHA-256 artifact digest. |

### Events

| Event | Description |
|---|---|
| `AttestationCreated` | Emitted when a new attestation is recorded. |
| `AttestationRevoked` | Emitted when an attestation is revoked. |
