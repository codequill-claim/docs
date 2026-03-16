---
title: Settings & Wallets
order: 2
---

# Settings & Wallets

Workspace settings control identity, authorization, collaboration, encryption, and network configuration. This page covers the settings surfaces available at [app.codequill.xyz](https://app.codequill.xyz).

## Settings Overview

The settings page is organized into the following sections:

- **Profile** -- Workspace display name, avatar, and description.
- **Default Chain** -- The Ethereum network used by default for on-chain operations within the workspace.
- **Notifications** -- Telegram notification preferences for workspace events (snapshots, attestations, claims, collaborator activity).

[Screenshot: Settings overview page showing profile, default chain, and notification preferences]

## Wallet Management

Wallets are the foundation of on-chain identity in CodeQuill. Every on-chain action -- claiming, snapshotting, attesting, preserving -- is ultimately authorized by a wallet. Wallet management in the web application handles connection, authority initialization, membership, delegation, and identity resolution.

### Connecting a Wallet

Wallets are connected via **Sign-In with Ethereum (SIWE)**. The flow is:

1. Click **Connect Wallet** in the settings panel.
2. Select a wallet provider (MetaMask, WalletConnect, or any injected EIP-1193 provider).
3. Sign the SIWE message presented by the application.
4. The wallet address is verified and saved to the workspace.

SIWE verification confirms that the user controls the private key for the address. No tokens are transferred and no on-chain transaction occurs during connection itself.

### Authority and Membership

The first wallet connected to a workspace becomes the **on-chain authority**. This address is registered on the `CodeQuillWorkspaceRegistry` contract as the workspace's root identity. The authority wallet has ultimate control: it can add members, manage claims, and configure governance.

Subsequent wallets are added as **members**. Adding a member requires a signature from the existing authority wallet using the **EIP-712** typed data standard. The EIP-712 message includes the new member's address and the workspace context, ensuring the authority explicitly consents to each addition.

[Screenshot: Wallet management panel showing authority wallet and two member wallets]

### Delegation Registration

After a wallet is connected and its role established (authority or member), a **delegation** is registered. Delegations are scoped, time-limited authorizations that allow the CodeQuill backend to submit transactions on behalf of the wallet within defined boundaries.

Delegation parameters include:

- **Scope** -- Which contract functions the delegation covers (e.g., snapshot registration, attestation publishing).
- **Expiry** -- A timestamp after which the delegation is no longer valid.
- **Caveats** -- Additional constraints specific to the delegation type.

Delegations can be revoked at any time from the wallet management panel. Expired delegations must be re-registered.

### ENS Resolution

For wallets with registered ENS names, CodeQuill resolves and displays:

- **ENS name** -- The primary name associated with the address (e.g., `alice.eth`).
- **ENS avatar** -- The avatar record, if set, displayed alongside the wallet address throughout the interface.

ENS resolution is performed at connection time and refreshed periodically. It is purely cosmetic -- all on-chain operations reference the raw Ethereum address.

### Identicon Generation

Wallets without an ENS avatar are assigned a deterministic **identicon** -- a unique visual pattern derived from the wallet address. Identicons provide visual distinction when multiple wallets are connected to a workspace.

## Wallet Onboarding Flow

When a user connects their first wallet, a multi-step onboarding sequence ensures all necessary on-chain primitives are established:

1. **SIWE Verification** -- The wallet signs a SIWE message to prove ownership.
2. **Save Wallet** -- The verified address is persisted to the workspace record.
3. **Initialize On-Chain Authority** -- If this is the first wallet, a transaction is submitted to register the workspace authority on-chain. If the workspace already has an authority, the wallet is added as a member (requiring the authority's EIP-712 signature).
4. **Register Delegation** -- A delegation is created and registered, enabling the wallet to authorize on-chain actions through the CodeQuill backend.

Each step must complete before the next begins. If any step fails (e.g., the user rejects a signature request), the flow pauses and can be resumed from the point of failure.

[Screenshot: Wallet onboarding flow at the delegation registration step]

## Collaborators

Workspaces support multiple collaborators with scoped permissions.

### Inviting Collaborators

Collaborators are invited by email. The invited user receives a link to join the workspace. Upon accepting, they authenticate via GitHub and are added to the workspace with the assigned role.

Invitations have an approval flow: the workspace owner or admin sends the invitation, and the recipient must explicitly accept. Pending invitations can be revoked before acceptance.

### Workspace Roles

Workspace-level roles define broad access:

| Role | Description |
|---|---|
| **Owner** | Full control over workspace settings, billing, collaborators, and all repositories. One per workspace. |
| **Admin** | Can manage collaborators, repository settings, and on-chain operations. Cannot modify billing or transfer ownership. |
| **Member** | Can view repositories and activity. On-chain operation access depends on repository-level roles. |

### Repository-Level Roles

Fine-grained access is controlled per repository:

| Role | Description |
|---|---|
| **Viewer** | Read-only access to repository evidence (snapshots, attestations, preservations). |
| **Publisher** | Can create and publish releases. |
| **Attester** | Can publish attestations for the repository's releases. |
| **Maintainer** | Full repository access including claiming, settings, and CI/CD configuration. |

Repository roles are additive. A collaborator with the **Member** workspace role and the **Attester** repository role can view all workspace repositories and publish attestations for the specific repository where the attester role is assigned.

[Screenshot: Collaborator management page showing invitations and role assignments]

## Encryption Settings

Preservations and proofs require client-side encryption. CodeQuill never accesses plaintext source code. Encryption is bound to **WebAuthn passkeys** registered to the workspace.

### Passkey Registration

To enable encryption:

1. Navigate to **Settings > Encryption**.
2. Click **Register Passkey**.
3. Authenticate using a platform authenticator (Touch ID, Windows Hello, or a hardware security key).
4. The passkey is registered and associated with the workspace.

### PRF Key Derivation

Encryption keys are derived using the **PRF (Pseudo-Random Function) extension** of WebAuthn. When a preservation or proof operation requires encryption, the passkey is invoked to derive a deterministic key specific to the operation context. This ensures:

- Encryption keys are never stored on CodeQuill servers.
- Key material is bound to the physical authenticator.
- Different operations produce different derived keys.

If a passkey is lost, encrypted preservations created with that passkey cannot be decrypted. There is no recovery mechanism by design -- CodeQuill does not hold key material.

[Screenshot: Encryption settings showing registered passkeys]

## DAO Executor Configuration

Workspaces that operate under DAO governance can configure a **DAO executor address**. The executor is a smart contract (typically a Gnosis Safe, Governor, or custom multisig) authorized to perform on-chain operations on behalf of the workspace.

To set or change the DAO executor:

1. Navigate to **Settings > Governance**.
2. Enter the executor contract address.
3. Confirm the change with the authority wallet's signature.

The DAO executor address is recorded on-chain. Once set, the executor can submit transactions for the workspace according to whatever governance process the DAO defines. See the [DAO Governance](/docs/web-application/dao-governance) page for details on governance workflows.

[Screenshot: DAO executor configuration panel]

## Network Switching

CodeQuill supports multiple Ethereum-compatible networks. The workspace default chain is set in settings and applies to all new on-chain operations unless overridden.

### Per-Session Chain Selection

Users can switch the active chain for their current session without changing the workspace default. This is useful when reviewing evidence recorded on a different network or when temporarily operating on a testnet.

### Workspace Default Chain

The workspace default chain determines which network is used when:

- New claims are registered.
- Snapshots are recorded from the web application.
- Delegations are created.
- Any on-chain operation is initiated without an explicit chain override.

Changing the workspace default does not affect existing on-chain records. Records on other chains remain accessible and visible in the interface.

## Transaction History

The transaction history page displays all on-chain transactions submitted by the workspace, across all chains. Each entry includes:

- Transaction hash (linked to the relevant block explorer)
- Operation type (claim, snapshot, attestation, preservation, release lifecycle)
- Chain and block number
- Submitting wallet
- Timestamp
- Confirmation status

Transactions can be filtered by operation type, chain, and date range.

[Screenshot: Transaction history showing recent on-chain operations across multiple chains]
