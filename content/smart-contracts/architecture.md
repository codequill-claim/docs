---
title: Architecture
description: "Seven Solidity contracts form a four-tier DAG deployed on EVM-compatible chains. No admin keys, no pause mechanism, fully permissionless."
order: 1
---

# Architecture

CodeQuill's on-chain layer consists of seven Solidity contracts deployed on EVM-compatible chains. All contracts target Solidity ^0.8.24 and are compiled with 0.8.28. They are MIT-licensed.

The development framework is Hardhat with Ignition for deterministic deployment. The sole external dependency is OpenZeppelin Contracts v5.4.0, used for `ECDSA` signature recovery and `EIP712` structured-data hashing.

## Dependency DAG

The contracts form a strict four-tier directed acyclic graph. Each tier depends only on tiers below it; no circular references exist.

### Tier 1 -- Identity & Access

| Contract | Role |
|---|---|
| `CodeQuillDelegation` | Context-scoped delegation with granular permission scopes and expiry |
| `CodeQuillWorkspaceRegistry` | Workspace context management and membership |

These contracts have no CodeQuill contract dependencies. They are deployed first and provide the identity and authorization primitives consumed by every tier above.

### Tier 2 -- Repository & Snapshots

| Contract | Depends on |
|---|---|
| `CodeQuillRepositoryRegistry` | Delegation, WorkspaceRegistry |
| `CodeQuillSnapshotRegistry` | RepositoryRegistry, WorkspaceRegistry, Delegation |

Tier 2 introduces data registration: binding repositories to owners and anchoring cryptographic snapshots of source code.

### Tier 3 -- Releases & Preservations

| Contract | Depends on |
|---|---|
| `CodeQuillReleaseRegistry` | RepositoryRegistry, WorkspaceRegistry, Delegation, SnapshotRegistry |
| `CodeQuillPreservationRegistry` | RepositoryRegistry, WorkspaceRegistry, Delegation, SnapshotRegistry |

Tier 3 builds on snapshot data to support release lifecycle governance and encrypted archive anchoring.

### Tier 4 -- Trust & Verification

| Contract | Depends on |
|---|---|
| `CodeQuillAttestationRegistry` | WorkspaceRegistry, Delegation, ReleaseRegistry |

The attestation registry sits at the top of the dependency graph. It records supply-chain attestations bound to accepted releases.

### Deployment order

Contracts are deployed in tier order: Tier 1 first, then Tier 2, Tier 3, and finally Tier 4. Each contract receives the addresses of its dependencies at construction time and stores them as `immutable` references.

## Design Principles

### 1. Relayer-mediated transactions

Users never need to hold ETH or submit transactions directly. Instead, users sign EIP-712 typed data off-chain, and a relayer submits the signed payload on their behalf. Every state-changing function that accepts user intent has a `WithSig` variant that verifies the recovered signer.

### 2. Multi-tenant isolation

All operations are scoped to a `contextId` representing a workspace. A repository claim, snapshot, release, or attestation always belongs to exactly one context. Cross-context references are not possible.

### 3. Separation of concerns

Identity, authorization, data registration, integrity anchoring, and archival preservation are handled by distinct contracts. No single contract accumulates unrelated responsibilities.

### 4. Immutable cross-references

Each contract stores the addresses of its dependencies as Solidity `immutable` state variables, set once at construction. There are no upgradeable proxies, no admin-controlled address slots, and no registry indirection. The dependency graph is fixed at deployment time.

### 5. No admin keys

There is no `owner`, no `admin` role, no `pause` function. Once deployed, the contracts are fully permissionless. No privileged address can alter behavior, freeze state, or upgrade logic.

### 6. Append-only with soft revocation

Records are append-only. Snapshots, releases, attestations, and preservations can never be deleted from contract storage. Where lifecycle transitions are needed -- revoking a release, superseding it with a newer version, or revoking an attestation -- the contract marks the record's status without removing the underlying data.

## Delegation Scopes

The delegation system uses a bitmask to grant fine-grained permissions. Each scope corresponds to a single bit:

| Scope | Value | Description |
|---|---|---|
| `SCOPE_CLAIM` | `1 << 0` | Claim and transfer repositories |
| `SCOPE_SNAPSHOT` | `1 << 1` | Create snapshots |
| `SCOPE_ATTEST` | `1 << 2` | Create and revoke attestations |
| `SCOPE_PRESERVATION` | `1 << 3` | Anchor preservation records |
| `SCOPE_RELEASE` | `1 << 4` | Anchor and manage releases |
| `SCOPE_ALL` | `type(uint256).max` | All current and future scopes |

A delegation can combine multiple scopes with bitwise OR. For example, granting snapshot and release permissions uses `SCOPE_SNAPSHOT | SCOPE_RELEASE`. The `SCOPE_ALL` value sets every bit, granting unrestricted access within the delegated context.
