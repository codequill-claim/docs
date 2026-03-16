---
title: DAO Governance
order: 2
---

# DAO Governance

CodeQuill supports integration with decentralized autonomous organizations (DAOs) and multisig wallets for release governance. When configured, a workspace can require that release approval decisions come from a DAO executor contract rather than an individual governance authority.

This enables governance pipelines where release acceptance is contingent on collective decision-making -- a DAO vote, a multisig threshold, or any on-chain governance mechanism that resolves to a single executor address.

## Configuring the DAO Executor

The DAO executor address is configured at the workspace level. Navigate to **Settings > DAO** in the web application.

[Screenshot: DAO executor configuration page showing the executor address field, current on-chain value, and Save button]

Enter the Ethereum address of the DAO executor contract. This is the address that will be authorized to accept or reject DAO-only releases on behalf of the workspace.

When you save the configuration, the web application calls `setDaoExecutor()` on the `CodeQuillReleaseRegistry` smart contract, recording the executor address on-chain. This is a transaction that requires confirmation.

### What Address to Use

The DAO executor address should be the address that your governance system uses to execute approved proposals. Common examples:

- **Aragon OS:** The Aragon DAO's executor contract address. When a vote passes in the Aragon DAO, the executor contract carries out the approved action -- in this case, calling `accept()` or `reject()` on the CodeQuill release registry.
- **Governor contracts (OpenZeppelin Governor, Compound Governor):** The Governor contract's executor or timelock address. After a proposal passes and the timelock period elapses, the executor calls the target function.
- **Multisig wallets (Gnosis Safe / Safe):** The Safe contract address. When the required number of signers approve, the Safe executes the transaction.

Any contract or externally owned account (EOA) can serve as a DAO executor. CodeQuill does not enforce any particular governance framework -- it only checks that the caller of `accept()` or `reject()` matches the stored executor address.

### Clearing the DAO Executor

To remove the DAO executor configuration, clear the address field and save. This sets the on-chain executor address to `0x0000000000000000000000000000000000000000`, which effectively disables DAO-only governance. Releases created with the DAO-only flag after clearing the executor will have no valid executor, and no address will be able to accept or reject them through the DAO path.

## Creating a DAO-Only Release

When creating a release (see [Release Lifecycle](/docs/releases/release-lifecycle)), the creation form includes a **DAO-Only Governance** toggle. This corresponds to the `gouvernance_dao_only` flag in the release configuration.

When this flag is enabled:

- **Only the DAO executor address** can accept or reject the release. The workspace's regular governance authority cannot act on this release.
- The DAO executor address is read from the on-chain registry at the time of governance action. If the executor address is changed between release creation and governance decision, the current executor at the time of the decision is the one that must act.

When this flag is not enabled, the standard governance authority handles acceptance and rejection. The DAO executor is not involved.

## The DAO Governance Flow

The complete flow from configuration through deployment follows this sequence:

### 1. Configure the DAO Executor

A workspace administrator sets the DAO executor address in **Settings > DAO**. This is a one-time configuration step (unless the DAO executor contract changes).

### 2. Create a DAO-Only Release

A team member creates a release in the web application with the DAO-only governance flag enabled. The release references a published snapshot and includes the standard metadata (name, notes, supersession target if applicable).

### 3. Anchor the Release

The release is anchored on-chain. The `anchorRelease()` transaction records the release with its DAO-only governance configuration. At this point, a GitHub Issue is created by `codequill-authorship[bot]`, which can trigger CI/CD build processes.

### 4. DAO Votes or Approves

The governance process occurs outside CodeQuill. The team submits a proposal to the DAO (via Aragon, Governor, Safe, or whatever governance system is in use). The proposal, when executed, should call `accept()` on the `CodeQuillReleaseRegistry` contract with the release ID.

How the proposal is structured and voted on depends entirely on the DAO's governance framework. CodeQuill does not participate in or observe the voting process -- it only verifies that the `accept()` or `reject()` call comes from the registered executor address.

### 5. DAO Executor Calls Accept

When the DAO's governance process resolves (vote passes, multisig threshold met, timelock elapses), the DAO executor contract executes the `accept()` call on the CodeQuill release registry. The contract verifies that the caller matches the stored DAO executor address for the workspace and that the release is configured as DAO-only.

If the DAO rejects the release, the executor calls `reject()` instead.

### 6. Acceptance Triggers Downstream Automation

Upon acceptance, a second GitHub Issue is created by `codequill-authorship[bot]`. CI/CD workflows listening for this event can then:

- Build the artifact from the approved source snapshot.
- Run `codequill attest` to create an attestation linking the artifact to the accepted release.
- Deploy the attested artifact to production.

### End-to-End Pipeline

The result is a governance pipeline with the following structure:

```
DAO executor configured
        ↓
DAO-only release created and anchored
        ↓
GitHub Issue created → CI builds artifact
        ↓
DAO votes to approve
        ↓
DAO executor calls accept()
        ↓
Release status: ACCEPTED
        ↓
GitHub Issue created → CI runs attestation → deploys to production
```

This pipeline ensures that no release reaches production without explicit DAO approval. The governance decision is recorded on-chain, the attestation links the artifact to the governed source, and the entire chain of evidence is independently verifiable.

## Security Considerations

- **Executor address changes.** If the DAO executor address is changed after a DAO-only release is anchored but before governance is resolved, the new executor address is the one that must act. The release does not retain a reference to the executor address at the time of anchoring -- it references the workspace's current executor at the time of the governance call.
- **Zero address.** If the DAO executor is cleared (set to the zero address) while DAO-only releases are pending, those releases become ungovernable through the DAO path. No address can accept or reject them as the DAO executor. Ensure pending DAO-only releases are resolved before clearing the executor.
- **Contract upgrades.** If the DAO executor contract is upgraded or migrated to a new address, update the workspace configuration before any pending DAO-only releases require governance action.
