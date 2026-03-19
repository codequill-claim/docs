---
title: Workspaces
description: "Workspaces are the top-level organizational unit in CodeQuill, binding wallets, collaborators, and repositories under a single on-chain identity."
order: 1
---

# Workspaces

A workspace is the top-level organizational unit in CodeQuill. It maps to a GitHub organization or user account and serves as the boundary for authority, collaboration, and on-chain identity.

## What a Workspace Represents

Every action in CodeQuill -- claiming a repository, creating a snapshot, anchoring a release, or attesting an artifact -- happens within the context of a workspace. The workspace defines:

- **Who has authority** -- Which wallets are authorized to act on behalf of this workspace.
- **Who can collaborate** -- Which team members have access, and at what level.
- **What chain to use** -- The default blockchain network for on-chain operations.
- **What plan is active** -- Quotas for claims, snapshots, attestations, and collaborators.
- **How governance works** -- Whether a DAO executor controls release approvals.

## On-Chain Identity

Each workspace has a **context ID** -- a `keccak256` hash of the workspace's internal identifier. This context ID is used across all smart contracts to scope operations to the correct workspace.

The on-chain workspace model has two roles:

- **Authority** -- A single wallet address that controls the workspace. The authority can add or remove members, transfer authority to a new wallet, and manage delegations. The authority is set during initial wallet onboarding and is recorded on the `CodeQuillWorkspaceRegistry` contract.
- **Members** -- Additional wallet addresses that can perform operations on behalf of the workspace. Members are added by the authority via EIP-712 signed messages.

The authority is the root of trust. If you control the authority wallet, you control the workspace's on-chain identity.

## Delegation

To avoid requiring users to pay gas or be online for every operation, CodeQuill uses a **delegation system**. Workspace members sign an EIP-712 message granting the platform's relayer permission to submit transactions on their behalf.

Delegations are:

- **Scoped** -- You can grant permission for specific operations (claim, snapshot, attest, release, preservation) or all operations.
- **Time-bound** -- Every delegation has an expiry timestamp.
- **Revocable** -- Delegations can be revoked at any time by the owner.

This means the CodeQuill platform can submit on-chain transactions for you (gasless from your perspective), but only within the scope and timeframe you authorized.

## Workspace Settings

Workspaces are configured through the web application at [app.codequill.xyz](https://app.codequill.xyz). Key settings include:

- **Profile** -- Workspace name, default blockchain network, notification preferences.
- **Wallets** -- Connect wallets via Sign-In with Ethereum (SIWE), manage authority, extend delegations.
- **Collaborators** -- Invite team members, assign workspace-level roles (owner, admin, member) and repository-level roles (viewer, publisher, attester, maintainer).
- **DAO Executor** -- Set an external DAO executor address for governance-controlled releases. See [DAO Governance](/releases/dao-governance).
- **Encryption** -- Register passkeys for workspace encryption (used for preservations and proofs).
- **Plan & Quota** -- View your subscription tier and current usage.

## Multi-Workspace Support

Users can belong to multiple workspaces (one per GitHub organization or user account). The web application allows switching between workspaces, and each workspace maintains its own independent set of wallets, claims, snapshots, releases, and quotas.

## Public Workspace Profiles

Every workspace has a public profile page accessible at `app.codequill.xyz/w/{login}`. This page shows:

- Claimed repositories
- Published snapshots
- Attestations
- Activity timeline

Public profiles allow anyone to inspect the evidence trail associated with a workspace without needing an account.
