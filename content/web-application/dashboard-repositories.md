---
title: Dashboard & Repositories
order: 1
---

# Dashboard & Repositories

The CodeQuill web application at [app.codequill.xyz](https://app.codequill.xyz) provides the primary interface for managing repositories, reviewing evidence records, and configuring workspace settings. This page covers the dashboard and repository management surfaces.

## Dashboard

The dashboard is the first screen after authentication. It serves two purposes: situational awareness and onboarding guidance.

### Activity Timeline

The main panel displays a reverse-chronological timeline of recent activity across the workspace. Each entry corresponds to a discrete event:

- **Snapshot recorded** -- A new snapshot was anchored on-chain for a repository.
- **Attestation published** -- A build artifact attestation was registered.
- **Preservation completed** -- An encrypted source archive was stored.
- **Claim registered** -- A repository was claimed on-chain.
- **Release lifecycle event** -- A release was drafted, published, accepted, revoked, or superseded.

Each timeline entry includes the repository name, the actor (wallet or collaborator), the chain, and a timestamp. Clicking an entry navigates to the relevant detail page.

[Screenshot: Dashboard activity timeline showing recent snapshots and attestations]

### Workspace Stats

A summary panel displays aggregate counts for the current billing period: total repositories synced, active claims, snapshots recorded, attestations published, and preservations completed. These figures reflect usage against the workspace's subscription quota.

### Getting Started Checklist

For new workspaces, the dashboard displays a five-milestone checklist that tracks initial setup progress:

1. **Repositories** -- At least one GitHub repository has been synced.
2. **Releases** -- At least one release has been created.
3. **Snapshots** -- At least one snapshot has been recorded on-chain.
4. **Attestations** -- At least one attestation has been published.
5. **Preservations** -- At least one encrypted archive has been stored.

Each milestone shows its completion state. The checklist disappears once all five milestones are met, or it can be dismissed manually.

[Screenshot: Getting started checklist with two of five milestones completed]

## Repository Management

### Syncing Repositories

Repositories are synced into CodeQuill via the GitHub App. Once the app is installed on a GitHub account or organization, repositories become available in the workspace. CodeQuill does not clone or store source code -- syncing imports repository metadata (name, ID, default branch, visibility) and establishes the link required for claiming and evidence production.

New repositories appear automatically as they are created on GitHub, provided the GitHub App has access.

### Repository List

The repository list displays all synced repositories with pagination. Each entry shows:

- Repository name and GitHub organization
- Claim status (unclaimed, claimed, or claimed by another workspace)
- Most recent snapshot timestamp
- Repository visibility (public or private)

Repositories can be filtered by claim status and sorted by name or recent activity.

[Screenshot: Repository list showing claimed and unclaimed repositories with pagination controls]

### Repository Detail Pages

Selecting a repository opens a detail view with the following sub-pages:

#### Overview

The overview page displays the repository's claim status and, if claimed, the on-chain owner address. It shows the workspace authority that holds the claim, the chain on which the claim was registered, and the transaction hash. For unclaimed repositories, a prompt to initiate claiming is displayed.

[Screenshot: Repository overview showing claim status and on-chain owner]

#### Snapshots

A table of all snapshots recorded for the repository, ordered by commit date. Each row includes the commit hash, snapshot hash, chain, block number, and timestamp. Snapshot details link to the on-chain transaction.

#### Attestations

A table of attestations associated with the repository's published releases. Each attestation includes the artifact identifier, the release it references, the attesting wallet, and the chain record.

#### Preservations

A list of encrypted source archives tied to specific snapshots. Each entry shows the snapshot reference, encryption status, archive size, and storage timestamp. Decryption requires the workspace's registered passkey.

#### Releases

The releases sub-page shows the full release lifecycle for the repository. Releases progress through defined states: draft, published, accepted, revoked, or superseded. Each release references one or more snapshots and includes version metadata.

#### CI/CD

The CI/CD page provides integration tooling for GitHub Actions. CodeQuill generates a YAML workflow file tailored to the repository, pre-configured with the correct workspace and repository identifiers. The generated workflow can be copied directly into the repository's `.github/workflows/` directory.

[Screenshot: CI/CD page with generated GitHub Actions YAML configuration]

#### Badges

The badges page provides embeddable SVG badges for the repository. Two badge types are available:

- **Claim badge** -- Displays whether the repository has been claimed on-chain.
- **Snapshot badge** -- Displays the most recent snapshot status.

Badges follow the [shields.io](https://shields.io) format and can be embedded in README files, documentation sites, or any surface that renders images. Each badge includes a copyable markdown snippet and a direct image URL.

[Screenshot: Badge configuration page showing claim and snapshot badges with embed code]

#### Settings

Repository-level settings include visibility preferences, collaborator role assignments for the specific repository, and the option to unclaim (release the on-chain claim).

## Claiming from the Web Application

While the CLI is the primary interface for claiming repositories, claims can also be initiated directly from the web application. The process is identical in effect: a transaction is submitted to the `CodeQuillRepositoryRegistry` contract, linking the repository ID to the workspace's on-chain authority.

To claim from the web application:

1. Navigate to the repository's overview page.
2. Click **Claim Repository**.
3. Confirm the transaction in your connected wallet.
4. Wait for on-chain confirmation.

The claim requires a connected wallet with an active delegation and membership in the workspace's on-chain context. If these prerequisites are not met, the interface displays the specific requirement that is missing.

[Screenshot: Claim confirmation dialog showing repository, workspace, and chain details]

## Public Workspace Profiles

Every workspace has a public profile accessible at `app.codequill.xyz/w/{login}`, where `{login}` is the workspace's GitHub login. Public profiles display:

- **Repositories** -- All claimed public repositories in the workspace.
- **Snapshots** -- A feed of recent public snapshots across all repositories.
- **Attestations** -- Recent public attestations.
- **Activity** -- A public subset of the workspace's activity timeline.

Public profiles serve as a verifiable, external-facing record of a workspace's evidence history. They require no authentication to view.

[Screenshot: Public workspace profile showing claimed repositories and recent activity]

## Badges

Badges are available at the workspace level as well as the repository level. Workspace-level badges aggregate status across all claimed repositories. All badges are served as SVG images and update automatically as new evidence is recorded.

Badge URLs follow a predictable pattern:

```
https://app.codequill.xyz/badge/{workspace}/{repo}/claim.svg
https://app.codequill.xyz/badge/{workspace}/{repo}/snapshot.svg
```

Embedding in a markdown README:

```markdown
![Claim Status](https://app.codequill.xyz/badge/my-org/my-repo/claim.svg)
![Snapshot Status](https://app.codequill.xyz/badge/my-org/my-repo/snapshot.svg)
```
